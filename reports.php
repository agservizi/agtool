<?php
session_start();
require_once 'inc/config.php';

// Includiamo subito le dipendenze necessarie
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Verifichiamo l'autenticazione
if (!isset($_SESSION['user_phone'])) {
    header('Location: login.php');
    exit;
}
$phone = $_SESSION['user_phone'];
$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
$stmt->close();

// --- ESPORTAZIONE DATI REPORT (PRIMA DI QUALSIASI OUTPUT) ---
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $export_view = $_GET['view'] ?? 'monthly';
    $export_month = intval($_GET['month'] ?? date('m'));
    $export_year = intval($_GET['year'] ?? date('Y'));
    $export_category = $_GET['category'] ?? '';
    // Recupera user_id
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();
    $where = "WHERE user_id = $user_id";
    if ($export_view == 'monthly') {
        $where .= " AND MONTH(date) = $export_month AND YEAR(date) = $export_year";
    } elseif ($export_view == 'yearly') {
        $where .= " AND YEAR(date) = $export_year";
    } elseif ($export_view == 'category') {
        $where .= " AND YEAR(date) = $export_year";
        if (!empty($export_category)) {
            $where .= " AND category = '" . $conn->real_escape_string($export_category) . "'";
        }
    }
    $sql = "SELECT date, description, category, type, amount FROM transactions $where ORDER BY date DESC";
    $result = $conn->query($sql);
    $rows = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $exports_dir = __DIR__ . '/exports';
    if (!is_dir($exports_dir)) {
        mkdir($exports_dir, 0775, true);
    }    function save_exported_report($conn, $user_id, $export_type, $export_view, $export_month, $export_year, $export_category, $file_name, $file_path, $download_url) {
        try {
            // Verifica che tutti i parametri necessari siano presenti
            if (empty($user_id) || empty($export_type) || empty($file_name)) {
                error_log("Errore parametri mancanti in save_exported_report");
                return false;
            }
            
            $stmt = $conn->prepare("INSERT INTO exported_reports (user_id, export_type, export_view, export_month, export_year, export_category, file_name, file_path, download_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issiiisss', $user_id, $export_type, $export_view, $export_month, $export_year, $export_category, $file_name, $file_path, $download_url);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Errore nel salvataggio del report esportato: " . $e->getMessage());
            return false;
        }
    }
    if ($export_type == 'csv') {
        $file_name = "report-".date('Ymd-His').".csv";
        $file_path = "{$exports_dir}/{$file_name}";
        $download_url = 'exports/' . $file_name;
        $out = fopen($file_path, 'w');
        fputcsv($out, ['Data','Descrizione','Categoria','Tipo','Importo']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['date'],$r['description'],$r['category'],$r['type'],$r['amount']]);
        }
        fclose($out);
        save_exported_report($conn, $user_id, 'csv', $export_view, $export_month, $export_year, $export_category, $file_name, $file_path, $download_url);
        if (file_exists($file_path)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.$file_name.'"');
            readfile($file_path);
            exit;
        } else {
            http_response_code(500);
            echo 'Errore nella generazione del file CSV.';
            exit;
        }
    }
    if ($export_type == 'excel') {
        $file_name = "report-".date('Ymd-His').".xls";
        $file_path = $exports_dir . "/$file_name";
        $download_url = 'exports/' . $file_name;
        $out = fopen($file_path, 'w');
        fputcsv($out, ['Data','Descrizione','Categoria','Tipo','Importo'], "\t");
        foreach ($rows as $r) {
            fputcsv($out, [$r['date'],$r['description'],$r['category'],$r['type'],$r['amount']], "\t");
        }
        fclose($out);
        save_exported_report($conn, $user_id, 'excel', $export_view, $export_month, $export_year, $export_category, $file_name, $file_path, $download_url);
        if (file_exists($file_path)) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="'.$file_name.'"');
            readfile($file_path);
            exit;
        } else {
            http_response_code(500);
            echo 'Errore nella generazione del file Excel.';
            exit;
        }
    }    if ($export_type == 'pdf') {
        // Assicuriamoci che autoload e FPDF siano inclusi correttamente
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/vendor/setasign/fpdf/fpdf.php';
        
        // Controllo più sicuro della disponibilità di FPDF
        if (!class_exists('FPDF')) {
            http_response_code(500);
            echo 'Errore: libreria FPDF non disponibile. Verifica l\'installazione con composer require setasign/fpdf';
            exit;
        }        // Definizione della classe per il report PDF con compatibilità PHP
        // Import the FPDF namespace
        use setasign\Fpdf\Fpdf;

        class PDFReport extends Fpdf {
            // Header della pagina
            public function Header() {
                $this->SetFillColor(0,123,255);
                $this->Rect(0,0,210,30,'F');
                if (file_exists(__DIR__.'/assets/img/logo-192.png')) {
                    $this->Image(__DIR__.'/assets/img/logo-192.png',10,7,16);
                }
                $this->SetFont('Arial','B',18);
                $this->SetTextColor(255,255,255);
                $this->Cell(0,15,'AGTool Finance - Report Proforma',0,1,'C');
                $this->Ln(2);
            public function Footer() {
            
            // Footer della pagina
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->SetTextColor(150,150,150);
                $this->Cell(0,10,'Generato il '.date('d/m/Y H:i').' - Pagina '.$this->PageNo().'/{nb}',0,0,'C');
            public function TableHeader() {
            
            // Intestazione della tabella
            function TableHeader() {
                $this->SetFont('Arial','B',11);
                $this->SetFillColor(230,230,230);
                $this->SetTextColor(0,0,0);
                $this->Cell(30,10,'Data',1,0,'C',true);
                $this->Cell(60,10,'Descrizione',1,0,'C',true);
                $this->Cell(40,10,'Categoria',1,0,'C',true);
                $this->Cell(20,10,'Tipo',1,0,'C',true);
                $this->Cell(30,10,'Importo',1,1,'C',true);
            public function TableRow($row) {
            
            // Riga della tabella
            function TableRow($row) {
                $this->SetFont('Arial','',10);
                $this->SetTextColor(0,0,0);
                $this->Cell(30,9,$row['date'],1,0,'C');
                // Convertiamo i caratteri per evitare problemi di encoding
                $description = mb_convert_encoding($row['description'], 'ISO-8859-1', 'UTF-8');
                $category = mb_convert_encoding($row['category'], 'ISO-8859-1', 'UTF-8');
                
                switch ($row['type']) {
                    case 'entrata':
                        $this->SetTextColor(40,167,69);
                        break;
                    default:
                        $this->SetTextColor(220,53,69);
                        break;
                }
                } else {
                    $this->SetTextColor(220,53,69);
                }
                $this->Cell(20,9,ucfirst($row['type']),1,0,'C');
                $this->SetTextColor(0,0,0);
                $this->Cell(30,9,number_format($row['amount'],2,',','.'),1,1,'R');
            }
        }        // Preparazione del file PDF
        $file_name = "report-".date('Ymd-His').".pdf";
        $file_path = $exports_dir . "/$file_name";
        $download_url = 'exports/' . $file_name;
        
        try {
            // Creazione dell'istanza PDF
            $pdf = new PDFReport('P','mm','A4');
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->Ln(10);
            
            // Intestazione con dettagli del periodo
            $pdf->SetFont('Arial','B',12);
            $pdf->SetTextColor(0,123,255);
            $periodo = '';
            if ($export_view == 'monthly') {
                $periodo = get_month_name($export_month).' '.$export_year;
            } elseif ($export_view == 'yearly') {
                $periodo = $export_year;
            } else {
                $periodo = 'Personalizzato';
            }
            $pdf->Cell(0,8,'Periodo: '.$periodo,0,1,'L');
            
            // Mostra categoria se selezionata
            if ($export_view=='category' && !empty($export_category)) {
                $categoria_sicura = mb_convert_encoding($export_category, 'ISO-8859-1', 'UTF-8');
                $pdf->Cell(0,8,'Categoria: '.$categoria_sicura,0,1,'L');
            }
            
            $pdf->Ln(2);
            $pdf->TableHeader();
            
            // Contenuto della tabella
            if (count($rows) > 0) {
                foreach ($rows as $r) {
                    $pdf->TableRow($r);
                }
            } else {
                $pdf->SetFont('Arial','I',11);
                $pdf->Cell(180,10,'Nessun dato disponibile per il periodo selezionato.',1,1,'C');
            }
            
            // Riepilogo totali
            $pdf->Ln(5);
            $pdf->SetFont('Arial','B',12);
            $pdf->SetFillColor(240,240,240);
            $pdf->SetTextColor(0,0,0);
            
            $tot_entrate = 0; 
            $tot_uscite = 0;
            
            foreach ($rows as $r) {
                if ($r['type'] == 'entrata') {
                    $tot_entrate += $r['amount'];
                } else {
                    $tot_uscite += $r['amount'];
                }
            }
            
            // Visualizzazione totali
            $pdf->Cell(60,10,'Totale Entrate:',1,0,'R',true);
            $pdf->SetTextColor(40,167,69);
            $pdf->Cell(40,10,number_format($tot_entrate,2,',','.'),1,0,'R',true);
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell(40,10,'Totale Uscite:',1,0,'R',true);
            $pdf->SetTextColor(220,53,69);
            $pdf->Cell(40,10,number_format($tot_uscite,2,',','.'),1,1,'R',true);
            
            // Saldo netto
            $pdf->SetTextColor(0,0,0);
            $pdf->Cell(60,10,'Saldo Netto:',1,0,'R',true);
            $saldo = $tot_entrate - $tot_uscite;
            $pdf->SetTextColor($saldo >= 0 ? 40 : 220, $saldo >= 0 ? 167 : 53, $saldo >= 0 ? 69 : 69);
            $pdf->Cell(40,10,number_format($saldo,2,',','.'),1,1,'R',true);
            $pdf->SetTextColor(0,0,0);
            
            // Salvataggio del file PDF
            $pdf->Output('F', $file_path);
        } catch (Exception $e) {
            // Gestione errori durante la creazione PDF
            error_log("Errore creazione PDF: " . $e->getMessage());
            http_response_code(500);
            echo 'Errore nella generazione del file PDF: ' . $e->getMessage();
            exit;
        }
        save_exported_report($conn, $user_id, 'pdf', $export_view, $export_month, $export_year, $export_category, $file_name, $file_path, $download_url);
        if (file_exists($file_path)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$file_name.'"');
            readfile($file_path);
            exit;
        } else {
            http_response_code(500);
            echo 'Errore nella generazione del file PDF.';
            exit;
        }
    }
}

// --- FILTRI E FUNZIONI DI SUPPORTO ---
$view = isset($_GET['view']) ? htmlspecialchars($_GET['view']) : 'monthly';
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : '';

function get_month_name($month_number) {
    $months = [1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre'];
    return $months[$month_number] ?? '';
}

include 'header.php';
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Reportistica</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item active">Reportistica</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <!-- Cronologia esportazioni -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-history"></i> Cronologia Esportazioni</h5>
        </div>
        <div class="card-body p-0">
            <?php
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $stmt->close();
            $sql_exports = "SELECT * FROM exported_reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 30";
            $stmt = $conn->prepare($sql_exports);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result_exports = $stmt->get_result();
            if ($result_exports && $result_exports->num_rows > 0) {
                echo '<div class="table-responsive"><table class="table table-striped mb-0">';
                echo '<thead><tr><th>Data/Ora</th><th>Tipo</th><th>Vista</th><th>Periodo</th><th>Categoria</th><th>File</th><th>Download</th></tr></thead><tbody>';
                while($exp = $result_exports->fetch_assoc()) {
                    $badge = '';
                    if ($exp['export_type'] == 'csv') $badge = '<span class="badge badge-primary"><i class="fas fa-file-csv"></i> CSV</span>';
                    elseif ($exp['export_type'] == 'excel') $badge = '<span class="badge badge-success"><i class="fas fa-file-excel"></i> Excel</span>';
                    elseif ($exp['export_type'] == 'pdf') $badge = '<span class="badge badge-danger"><i class="fas fa-file-pdf"></i> PDF</span>';
                    $periodo = '';
                    if ($exp['export_view'] == 'monthly') $periodo = get_month_name($exp['export_month']).' '.$exp['export_year'];
                    elseif ($exp['export_view'] == 'yearly') $periodo = $exp['export_year'];
                    elseif ($exp['export_view'] == 'category') $periodo = $exp['export_year'];
                    else $periodo = '-';
                    $download = '';
                    if (!empty($exp['download_url']) && file_exists(__DIR__ . '/' . $exp['download_url'])) {
                        $download = '<button type="button" class="btn btn-sm btn-outline-primary download-ajax" data-url="'.htmlspecialchars($exp['download_url']).'" data-filename="'.htmlspecialchars($exp['file_name']).'"><i class="fas fa-download"></i></button>';
                    } else {
                        $download = '<form method="get" action="reports.php" class="d-inline">
        <input type="hidden" name="export" value="' . htmlspecialchars($exp['export_type']) . '">
        <input type="hidden" name="view" value="' . htmlspecialchars($exp['export_view']) . '">
        <input type="hidden" name="month" value="' . htmlspecialchars($exp['export_month']) . '">
        <input type="hidden" name="year" value="' . htmlspecialchars($exp['export_year']) . '">
        <input type="hidden" name="category" value="' . htmlspecialchars($exp['export_category']) . '">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
    </form>';
                    }
                    echo '<tr>';
                    echo '<td>'.date('d/m/Y H:i', strtotime($exp['created_at'] ?? $exp['timestamp'] ?? 'now')).'</td>';
                    echo '<td>'.$badge.'</td>';
                    echo '<td>'.ucfirst($exp['export_view']).'</td>';
                    echo '<td>'.$periodo.'</td>';
                    echo '<td>'.htmlspecialchars($exp['export_category']).'</td>';
                    echo '<td>'.htmlspecialchars($exp['file_name']).'</td>';
                    echo '<td>'.$download.'</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            } else {
                echo '<div class="p-3 text-center text-muted">Nessuna esportazione trovata.</div>';
            }
            $stmt->close();
            ?>
        </div>
    </div>
    <!-- Fine cronologia esportazioni -->
    
    <!-- Pulsanti esportazione -->
    <div class="mb-3">
        <form method="get" action="reports.php" class="d-inline">
            <input type="hidden" name="export" value="csv">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-file-csv"></i> Esporta CSV</button>
        </form>
        <form method="get" action="reports.php" class="d-inline">
            <input type="hidden" name="export" value="excel">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button type="submit" class="btn btn-outline-success"><i class="fas fa-file-excel"></i> Esporta Excel</button>
        </form>
        <form method="get" action="reports.php" class="d-inline">
            <input type="hidden" name="export" value="pdf">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
            <button type="submit" class="btn btn-outline-danger"><i class="fas fa-file-pdf"></i> Esporta PDF</button>
        </form>
    </div>
    <!-- Fine pulsanti esportazione -->
    
    <!-- Filtri -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
        </div>
        <div class="card-body">
            <form method="get" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Vista</label>
                        <select name="view" class="form-control" onchange="this.form.submit()">
                            <option value="monthly" <?php echo $view == 'monthly' ? 'selected' : ''; ?>>Mensile</option>
                            <option value="yearly" <?php echo $view == 'yearly' ? 'selected' : ''; ?>>Annuale</option>
                            <option value="category" <?php echo $view == 'category' ? 'selected' : ''; ?>>Per Categoria</option>
                            <option value="trend" <?php echo $view == 'trend' ? 'selected' : ''; ?>>Trend</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($view != 'trend') { ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Anno</label>
                        <select name="year" class="form-control" onchange="this.form.submit()">
                            <?php
                            // Ottieni gli anni disponibili nel database
                            $sql_years = "SELECT DISTINCT YEAR(date) as year FROM transactions ORDER BY year DESC";
                            $result_years = $conn->query($sql_years);
                            
                            if ($result_years->num_rows > 0) {
                                while($row = $result_years->fetch_assoc()) {
                                    $selected = ($year == $row['year']) ? 'selected' : '';
                                    echo "<option value='{$row['year']}' $selected>{$row['year']}</option>";
                                }
                            } else {
                                echo "<option value='" . date('Y') . "' selected>" . date('Y') . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($view == 'monthly') { ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Mese</label>
                        <select name="month" class="form-control" onchange="this.form.submit()">
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $selected = ($month == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>" . get_month_name($i) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($view == 'category') { ?>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Categoria</label>
                        <select name="category" class="form-control" onchange="this.form.submit()">
                            <option value="">Tutte</option>
                            <?php
                            // Ottieni tutte le categorie distinte presenti nelle transazioni
                            $sql_categories = "SELECT DISTINCT category FROM transactions ORDER BY category";
                            $result_categories = $conn->query($sql_categories);
                            
                            if ($result_categories->num_rows > 0) {
                                while($row = $result_categories->fetch_assoc()) {
                                    $selected = ($category == $row['category']) ? 'selected' : '';
                                    echo "<option value='".htmlspecialchars($row['category'])."' $selected>".htmlspecialchars($row['category'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <?php } ?>
            </form>
        </div>
    </div>
    <!-- Fine filtri -->
    
    <!-- Grafico trend (visibile solo se selezionata la vista "Trend") -->
    <?php if ($view == 'trend') { ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Trend Entrate/Uscite</h5>
        </div>
        <div class="card-body">
            <div id="trendChartContainer" style="position: relative; height:40vh; width:100%;"></div>
        </div>
    </div>
    <?php } ?>
    
    <!-- Tabella report (visibile solo se non è selezionata la vista "Trend") -->
    <?php if ($view != 'trend') { ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-table"></i> Dettaglio Report</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrizione</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Importo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = "WHERE 1=1";
                        if ($view == 'monthly') {
                            $where .= " AND MONTH(date) = $month AND YEAR(date) = $year";
                        } elseif ($view == 'yearly') {
                            $where .= " AND YEAR(date) = $year";
                        } elseif ($view == 'category') {
                            $where .= " AND YEAR(date) = $year";
                            if (!empty($category)) {
                                $where .= " AND category = '" . $conn->real_escape_string($category) . "'";
                            }
                        }
                        $sql = "SELECT date, description, category, type, amount FROM transactions $where ORDER BY date DESC";
                        $result = $conn->query($sql);
                        $totale_entrate = 0;
                        $totale_uscite = 0;
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $tipo_class = ($row['type'] == 'entrata') ? 'text-success' : 'text-danger';
                                $totale_entrate += ($row['type'] == 'entrata') ? $row['amount'] : 0;
                                $totale_uscite += ($row['type'] == 'uscita') ? $row['amount'] : 0;
                                echo '<tr>';
                                echo '<td>'.date('d/m/Y', strtotime($row['date'])).'</td>';
                                echo '<td>'.htmlspecialchars($row['description']).'</td>';
                                echo '<td>'.htmlspecialchars($row['category']).'</td>';
                                echo '<td class="'.$tipo_class.'">'.ucfirst($row['type']).'</td>';
                                echo '<td class="text-right">'.number_format($row['amount'], 2, ',', '.').'</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center text-muted">Nessun dato disponibile per il periodo selezionato.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="m-0">Totale Entrate: <span class="text-success"><?php echo number_format($totale_entrate, 2, ',', '.'); ?> €</span></h5>
                </div>
                <div class="col-md-6">
                    <h5 class="m-0">Totale Uscite: <span class="text-danger"><?php echo number_format($totale_uscite, 2, ',', '.'); ?> €</span></h5>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<script>
// --- GRAFICO TREND ---
<?php if ($view == 'trend') { ?>
var ctx = document.getElementById('trendChartContainer').getContext('2d');
var trendChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php
        $labels = [];
        $sql_labels = "SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month_year FROM transactions WHERE 1=1";
        if ($view == 'monthly') {
            $sql_labels .= " AND MONTH(date) = $month AND YEAR(date) = $year";
        } elseif ($view == 'yearly') {
            $sql_labels .= " AND YEAR(date) = $year";
        }
        $sql_labels .= " ORDER BY date ASC";
        $result_labels = $conn->query($sql_labels);
        if ($result_labels && $result_labels->num_rows > 0) {
            while($row_label = $result_labels->fetch_assoc()) {
                $labels[] = '"' . $row_label['month_year'] . '"';
            }
        }
        echo implode(',', $labels);
        ?>],
        datasets: [{
            label: 'Entrate',
            data: [<?php
            $data_entrate = [];
            $sql_entrate = "SELECT SUM(amount) as total FROM transactions WHERE type = 'entrata' AND 1=1";
            if ($view == 'monthly') {
                $sql_entrate .= " AND MONTH(date) = $month AND YEAR(date) = $year";
            } elseif ($view == 'yearly') {
                $sql_entrate .= " AND YEAR(date) = $year";
            }
            $sql_entrate .= " GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY date ASC";
            $result_entrate = $conn->query($sql_entrate);
            if ($result_entrate && $result_entrate->num_rows > 0) {
                while($row_entrata = $result_entrate->fetch_assoc()) {
                    $data_entrate[] = $row_entrata['total'];
                }
            }
            echo implode(',', $data_entrate);
            ?>],
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.2)',
            fill: true,
        }, {
            label: 'Uscite',
            data: [<?php
            $data_uscite = [];
            $sql_uscite = "SELECT SUM(amount) as total FROM transactions WHERE type = 'uscita' AND 1=1";
            if ($view == 'monthly') {
                $sql_uscite .= " AND MONTH(date) = $month AND YEAR(date) = $year";
            } elseif ($view == 'yearly') {
                $sql_uscite .= " AND YEAR(date) = $year";
            }
            $sql_uscite .= " GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY date ASC";
            $result_uscite = $conn->query($sql_uscite);
            if ($result_uscite && $result_uscite->num_rows > 0) {
                while($row_uscita = $result_uscite->fetch_assoc()) {
                    $data_uscite[] = $row_uscita['total'];
                }
            }
            echo implode(',', $data_uscite);
            ?>],
            borderColor: 'rgba(220, 53, 69, 1)',
            backgroundColor: 'rgba(220, 53, 69, 0.2)',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        interaction: {
            mode: 'index',
            intersect: false
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Data'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Importo (€)'
                },
                ticks: {
                    callback: function(value, index, values) {
                        return value.toString().replace('.', ',') + ' €';
                    }
                }
            }
        }
    }
});
<?php } ?>
</script>

<?php
$conn->close();
?>
