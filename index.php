<?php
session_start();
require_once 'inc/config.php';

if (!isset($_SESSION['user_phone'])) {
    header('Location: login');
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
    header('Location: login');
    exit;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGTool Finance - Gestione Finanze Personali</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AdminLTE Style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="index" class="nav-link">Home</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="#" class="nav-link" data-toggle="modal" data-target="#addTransactionModal">Nuova Transazione</a>
                </li>
            </ul>
            <!-- Pulsante Logout a destra -->
            <ul class="navbar-nav ml-auto">
                <?php
                if (isset($_SESSION['user_phone'])) {
                    $phone = $_SESSION['user_phone'];
                    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                    $stmt->bind_param('s', $phone);
                    $stmt->execute();
                    $stmt->bind_result($user_id);
                    $stmt->fetch();
                    $stmt->close();
                    $notif_stmt = $conn->prepare("SELECT title, message, status, scheduled_at FROM notifications WHERE user_id = ? ORDER BY scheduled_at DESC, created_at DESC LIMIT 5");
                    $notif_stmt->bind_param('i', $user_id);
                    $notif_stmt->execute();
                    $notif_result = $notif_stmt->get_result();
                    $unread_count = 0;
                    $notifiche = [];
                    while($row = $notif_result->fetch_assoc()) {
                        if ($row['status'] === 'pending') $unread_count++;
                        $notifiche[] = $row;
                    }
                    $notif_stmt->close();
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Notifiche">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge badge-warning navbar-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right p-0" style="min-width:320px;max-width:350px;">
                        <span class="dropdown-header"><?php echo $unread_count > 0 ? $unread_count.' nuove notifiche' : 'Nessuna nuova notifica'; ?></span>
                        <div class="dropdown-divider"></div>
                        <?php if (count($notifiche) > 0): foreach($notifiche as $n): ?>
                            <a href="notifications" class="dropdown-item">
                                <i class="fas fa-info-circle mr-2"></i> <strong><?php echo htmlspecialchars($n['title']); ?></strong><br>
                                <span style="font-size:0.95em;color:#666;"><?php echo htmlspecialchars($n['message']); ?></span>
                                <span class="float-right text-muted text-sm"><?php echo $n['scheduled_at'] ? date('d/m/Y', strtotime($n['scheduled_at'])) : ''; ?></span>
                            </a>
                            <div class="dropdown-divider"></div>
                        <?php endforeach; else: ?>
                            <span class="dropdown-item text-center text-muted">Nessuna notifica recente</span>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="notifications" class="dropdown-item dropdown-footer">Vedi tutte le notifiche</a>
                    </div>
                </li>
                <?php } ?>
                <li class="nav-item">
                    <a href="logout" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="index" class="brand-link">
                <i class="fas fa-wallet ml-3 mr-2"></i>
                <span class="brand-text font-weight-light">AGTool Finance</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="index" class="nav-link active">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="transactions" class="nav-link">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p>Transazioni</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="recurring" class="nav-link">
                                <i class="nav-icon fas fa-redo"></i>
                                <p>Ricorrenti</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="categories" class="nav-link">
                                <i class="nav-icon fas fa-tags"></i>
                                <p>Categorie</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="savings" class="nav-link">
                                <i class="nav-icon fas fa-piggy-bank"></i>
                                <p>Obiettivi di Risparmio</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports" class="nav-link">
                                <i class="nav-icon fas fa-chart-pie"></i>
                                <p>Reportistica</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="advisor" class="nav-link">
                                <i class="nav-icon fas fa-lightbulb"></i>
                                <p>Consulente</p>
                            </a>
                        </li>
                        <!-- Nuove voci di menu per Notifiche e Impostazioni -->
                        <li class="nav-item">
                            <a href="notifications" class="nav-link">
                                <i class="nav-icon fas fa-bell"></i>
                                <p>Notifiche</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="settings" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>Impostazioni</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0 text-dark">Dashboard</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
    <div class="container-fluid">
        <!-- Statistiche principali -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo format_currency($balance); ?></h3>
                        <p>Bilancio Totale</p>
                    </div>
                    <div class="icon"><i class="fas fa-wallet"></i></div>
                    <a href="transactions" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo format_currency($income); ?></h3>
                        <p>Entrate Totali</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-up"></i></div>
                    <a href="transactions?type=entrata" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo format_currency($expense); ?></h3>
                        <p>Uscite Totali</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-down"></i></div>
                    <a href="transactions?type=uscita" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo format_currency($monthly_savings); ?></h3>
                        <p>Risparmio del Mese</p>
                    </div>
                    <div class="icon"><i class="fas fa-piggy-bank"></i></div>
                    <a href="reports?view=monthly" class="small-box-footer">Maggiori dettagli <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
        <!-- /.row -->

        <!-- Sezione azioni rapide -->
        <div class="mb-4">
            <a href="transactions.php" class="btn btn-primary mr-2"><i class="fas fa-plus"></i> Nuova Transazione</a>
            <a href="reports.php" class="btn btn-info mr-2"><i class="fas fa-chart-pie"></i> Reportistica</a>
            <a href="savings.php" class="btn btn-success"><i class="fas fa-piggy-bank"></i> Nuovo Obiettivo</a>
        </div>

        <!-- Grafico Entrate vs Uscite -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Entrate vs Uscite (ultimi 6 mesi)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="income-expense-chart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget grafico: andamento risparmio ultimi 12 mesi -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-area"></i> Andamento Risparmio (ultimi 12 mesi)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="savings-trend-chart" height="90"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('savings-trend-chart');
            if (ctx) {
                fetch('get_chart_data.php?type=savings_trend')
                    .then(response => response.json())
                    .then(data => {
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: 'Risparmio',
                                    data: data.savings,
                                    borderColor: '#007bff',
                                    backgroundColor: 'rgba(0,123,255,0.1)',
                                    fill: true,
                                    tension: 0.3,
                                    pointRadius: 4,
                                    pointBackgroundColor: '#007bff',
                                    pointBorderColor: '#fff',
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true, position: 'top' },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return 'Risparmio: ' + context.raw + ' €';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) { return value + ' €'; }
                                        }
                                    }
                                }
                            }
                        });
                    });
            }
        });
        </script>
        <!-- Fine widget grafico risparmio -->

        <!-- Tabelle e obiettivi -->
        <div class="row">
            <div class="col-md-7">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="card-title mb-0">Transazioni Recenti</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrizione</th>
                                    <th>Categoria</th>
                                    <th>Importo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT t.*, c.name as category_name, c.color FROM transactions t LEFT JOIN categories c ON t.category = c.name AND t.type = c.type WHERE t.user_phone='".$conn->real_escape_string($phone)."' ORDER BY t.date DESC LIMIT 10";
                                $result = $conn->query($sql);
                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        $color_class = ($row['type'] == 'entrata') ? 'text-success' : 'text-danger';
                                        $amount_sign = ($row['type'] == 'entrata') ? '+' : '-';
                                        $amount = $amount_sign . ' ' . format_currency($row['amount']);
                                        $date = format_date($row['date']);
                                        $category_color = $row['color'] ?? '#3498db';
                                        $category_name = $row['category_name'] ?? $row['category'];
                                        echo "<tr>";
                                        echo "<td>{$date}</td>";
                                        echo "<td>{$row['description']}</td>";
                                        echo "<td><span class='badge' style='background-color: {$category_color}'>{$category_name}</span></td>";
                                        echo "<td class='{$color_class}'>{$amount}</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>Nessuna transazione trovata</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="card-title mb-0">Obiettivi di Risparmio</h5></div>
                    <div class="card-body p-0">
                        <?php
                        $sql = "SELECT * FROM savings_goals WHERE user_id = ? ORDER BY target_date ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $percentage = ($row['target_amount'] > 0) ? ($row['current_amount'] / $row['target_amount']) * 100 : 0;
                                $percentage = min(100, $percentage);
                                $date_text = $row['target_date'] ? 'entro il ' . format_date($row['target_date']) : '';
                        ?>
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h4 class="m-0"><?php echo $row['name']; ?></h4>
                                <span><?php echo format_currency($row['current_amount']); ?> / <?php echo format_currency($row['target_amount']); ?></span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($percentage, 1); ?>%
                                </div>
                            </div>
                            <small class="text-muted"><?php echo $date_text; ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <?php
                            }
                        } else {
                            echo '<div class="p-3 text-center">Nessun obiettivo di risparmio. Aggiungi il tuo primo obiettivo!</div>';
                        }
                        $stmt->close();
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fine tabelle -->

        <!-- Nuovi widget dashboard -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-percentage"></i> Tasso di Risparmio</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        // Calcolo tasso di risparmio mensile
                        $income = 0; $expense = 0; $savings_rate = 0;
                        $sql = "SELECT SUM(CASE WHEN type='entrata' THEN amount ELSE 0 END) as income, SUM(CASE WHEN type='uscita' THEN amount ELSE 0 END) as expense FROM transactions WHERE MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE()) AND user_phone='".$conn->real_escape_string($phone)."'";
                        $res = $conn->query($sql);
                        if($res && $row = $res->fetch_assoc()) {
                            $income = floatval($row['income']);
                            $expense = floatval($row['expense']);
                            if($income > 0) $savings_rate = round((($income-$expense)/$income)*100,1);
                        }
                        ?>
                        <span style="font-size:2.2em;font-weight:bold;"> <?php echo $savings_rate; ?>% </span>
                        <div class="text-muted">Risparmio su entrate mese</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt"></i> Spesa Media Giornaliera</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        // Calcolo spesa media giornaliera mese corrente
                        $sql = "SELECT SUM(amount) as total FROM transactions WHERE type='uscita' AND MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE()) AND user_phone='".$conn->real_escape_string($phone)."'";
                        $res = $conn->query($sql);
                        $total = ($res && $row = $res->fetch_assoc()) ? floatval($row['total']) : 0;
                        $days = date('j');
                        $media = $days > 0 ? $total/$days : 0;
                        ?>
                        <span style="font-size:2.2em;font-weight:bold;"> <?php echo format_currency($media); ?> </span>
                        <div class="text-muted">Spesa media/giorno (mese)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-trophy"></i> Categoria Top Spesa</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        // Categoria con più spesa mese corrente
                        $sql = "SELECT category, SUM(amount) as tot FROM transactions WHERE type='uscita' AND MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE()) AND user_phone='".$conn->real_escape_string($phone)."' GROUP BY category ORDER BY tot DESC LIMIT 1";
                        $res = $conn->query($sql);
                        if($res && $row = $res->fetch_assoc()) {
                            echo '<span style="font-size:1.5em;font-weight:bold;">'.htmlspecialchars($row['category']).'</span><br><span class="badge badge-danger">'.format_currency($row['tot']).'</span>';
                        } else {
                            echo '<span class="text-muted">Nessuna spesa registrata</span>';
                        }
                        ?>
                        <div class="text-muted">Categoria più costosa (mese)</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fine nuovi widget dashboard -->

        <!-- Widget aggiuntivi personalizzati -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-bell"></i> Notifiche Attive</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        // Conta notifiche non lette
                        $notif_count = 0;
                        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                        $stmt->bind_param('s', $phone);
                        $stmt->execute();
                        $stmt->bind_result($user_id);
                        $stmt->fetch();
                        $stmt->close();
                        $notif_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'pending'");
                        $notif_stmt->bind_param('i', $user_id);
                        $notif_stmt->execute();
                        $notif_stmt->bind_result($notif_count);
                        $notif_stmt->fetch();
                        $notif_stmt->close();
                        ?>
                        <span style="font-size:2.2em;font-weight:bold;"> <?php echo $notif_count; ?> </span>
                        <div class="text-muted">Notifiche non lette</div>
                        <a href="notifications.php" class="btn btn-outline-primary btn-sm mt-2">Vedi tutte</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-alt"></i> Prossima Scadenza Ricorrente</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        // Prossima scadenza ricorrente
                        $sql = "SELECT description, date FROM transactions WHERE type='uscita' AND user_phone='".$conn->real_escape_string($phone)."' AND date >= CURDATE() ORDER BY date ASC LIMIT 1";
                        $res = $conn->query($sql);
                        if($res && $row = $res->fetch_assoc()) {
                            echo '<span style="font-size:1.1em;font-weight:bold;">'.htmlspecialchars($row['description']).'</span><br>';
                            echo '<span class="badge badge-info">'.date('d/m/Y', strtotime($row['date'])).'</span>';
                        } else {
                            echo '<span class="text-muted">Nessuna scadenza imminente</span>';
                        }
                        ?>
                        <div class="text-muted">Ricorrenza più vicina</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-dark">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-star"></i> Consiglio del Giorno</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        // Consiglio random dal DB
                        $tip = '';
                        $sql = "SELECT title, description FROM financial_tips WHERE is_active=1 ORDER BY RAND() LIMIT 1";
                        $res = $conn->query($sql);
                        if($res && $row = $res->fetch_assoc()) {
                            $tip = '<strong>'.htmlspecialchars($row['title']).'</strong><br><span style=\'font-size:0.95em;\'>'.htmlspecialchars($row['description']).'</span>';
                        } else {
                            $tip = 'Nessun consiglio disponibile.';
                        }
                        echo $tip;
                        ?>
                        <div class="text-muted mt-2">Suggerimento finanziario</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fine widget aggiuntivi personalizzati -->

        <!-- Pulsanti rapidi -->
        <div class="mb-4">
            <a href="transactions.php" class="btn btn-outline-primary mr-2"><i class="fas fa-plus"></i> Nuova Transazione</a>
            <a href="reports.php" class="btn btn-outline-info mr-2"><i class="fas fa-chart-pie"></i> Reportistica</a>
            <a href="savings.php" class="btn btn-outline-success"><i class="fas fa-piggy-bank"></i> Nuovo Obiettivo</a>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grafico Entrate vs Uscite
    fetch('get_chart_data.php?type=income_expense')
        .then(response => response.json())
        .then(data => {
            if (data && data.labels) {
                new Chart(document.getElementById('income-expense-chart').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Entrate',
                                data: data.income,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40,167,69,0.1)',
                                fill: true
                            },
                            {
                                label: 'Uscite',
                                data: data.expense,
                                borderColor: '#dc3545',
                                backgroundColor: 'rgba(220,53,69,0.1)',
                                fill: true
                            },
                            {
                                label: 'Risparmio',
                                data: data.savings,
                                borderColor: '#007bff',
                                backgroundColor: 'rgba(0,123,255,0.1)',
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'top' }
                        }
                    }
                });
            }
        });
});
</script>
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <strong>AGTool Finance &copy; 2025</strong> - Gestione Finanze Personali
            <div class="float-right d-none d-sm-inline-block">
                <b>Versione</b> 1.0.0
            </div>
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- Modal Aggiungi Transazione -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1" role="dialog" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">Aggiungi Transazione</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="process_transaction.php" method="post" id="transaction-form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="transaction-type">Tipo</label>
                            <select id="transaction-type" name="type" class="form-control" required>
                                <option value="entrata">Entrata</option>
                                <option value="uscita">Uscita</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transaction-amount">Importo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">€</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" id="transaction-amount" name="amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="transaction-description">Descrizione</label>
                            <input type="text" id="transaction-description" name="description" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="transaction-category">Categoria</label>
                            <select id="transaction-category" name="category" class="form-control" required>
                                <!-- Le categorie saranno caricate dinamicamente in base al tipo selezionato -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transaction-date">Data</label>
                            <input type="date" id="transaction-date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Aggiungi Obiettivo -->
    <div class="modal fade" id="addGoalModal" tabindex="-1" role="dialog" aria-labelledby="addGoalModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGoalModalLabel">Nuovo Obiettivo di Risparmio</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="process_goal" method="post" id="goal-form">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="goal-name">Nome dell'obiettivo</label>
                            <input type="text" id="goal-name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="goal-amount">Importo obiettivo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">€</span>
                                </div>
                                <input type="number" step="0.01" min="0.01" id="goal-amount" name="target_amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="goal-current">Importo già risparmiato (opzionale)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">€</span>
                                </div>
                                <input type="number" step="0.01" min="0" id="goal-current" name="current_amount" class="form-control" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="goal-date">Data obiettivo (opzionale)</label>
                            <input type="date" id="goal-date" name="target_date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- ChartJS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>
