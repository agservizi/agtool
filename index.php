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
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

// Calcoli principali
$balance = 0; $income = 0; $expense = 0; $monthly_savings = 0;
$sql = "SELECT COALESCE(SUM(CASE WHEN type='entrata' THEN amount ELSE 0 END),0) as income, COALESCE(SUM(CASE WHEN type='uscita' THEN amount ELSE 0 END),0) as expense FROM transactions WHERE user_phone = '".$conn->real_escape_string($phone)."'";
$res = $conn->query($sql);
if($res && $row = $res->fetch_assoc()) {
    $income = floatval($row['income']);
    $expense = floatval($row['expense']);
    $balance = $income - $expense;
}
$sql = "SELECT COALESCE(SUM(CASE WHEN type='entrata' THEN amount ELSE 0 END),0) as income, COALESCE(SUM(CASE WHEN type='uscita' THEN amount ELSE 0 END),0) as expense FROM transactions WHERE user_phone = '".$conn->real_escape_string($phone)."' AND MONTH(date)=MONTH(CURDATE()) AND YEAR(date)=YEAR(CURDATE())";
$res = $conn->query($sql);
if($res && $row = $res->fetch_assoc()) {
    $monthly_savings = floatval($row['income']) - floatval($row['expense']);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGTool Finance - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed bg-gray-100">
<div class="wrapper">
    <?php include 'header.php'; ?>
    <div class="content-wrapper" style="min-height:100vh;">
        <section class="content pt-4 pb-4">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 col-md-8">
                        <h1 class="font-bold text-3xl mb-2 text-gray-800">Dashboard</h1>
                        <p class="text-gray-600 mb-4">Benvenuto su AGTool Finance! Qui puoi monitorare la tua situazione finanziaria in tempo reale.</p>
                    </div>
                    <div class="col-12 col-md-4 flex items-center justify-end">
                        <a href="process_transaction.php" class="btn btn-primary mr-2"><i class="fas fa-plus"></i> Nuova Transazione</a>
                        <a href="savings.php" class="btn btn-success mr-2"><i class="fas fa-piggy-bank"></i> Nuovo Obiettivo</a>
                        <a href="reports.php" class="btn btn-info"><i class="fas fa-chart-pie"></i> Reportistica</a>
                    </div>
                </div>
                <!-- Statistiche principali -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="small-box bg-info shadow">
                            <div class="inner">
                                <h3><?php echo format_currency($balance); ?></h3>
                                <p>Bilancio Totale</p>
                            </div>
                            <div class="icon"><i class="fas fa-wallet"></i></div>
                            <a href="transactions" class="small-box-footer">Dettagli <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="small-box bg-success shadow">
                            <div class="inner">
                                <h3><?php echo format_currency($income); ?></h3>
                                <p>Entrate Totali</p>
                            </div>
                            <div class="icon"><i class="fas fa-arrow-up"></i></div>
                            <a href="transactions?type=entrata" class="small-box-footer">Dettagli <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="small-box bg-danger shadow">
                            <div class="inner">
                                <h3><?php echo format_currency($expense); ?></h3>
                                <p>Uscite Totali</p>
                            </div>
                            <div class="icon"><i class="fas fa-arrow-down"></i></div>
                            <a href="transactions?type=uscita" class="small-box-footer">Dettagli <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="small-box bg-warning shadow">
                            <div class="inner">
                                <h3><?php echo format_currency($monthly_savings); ?></h3>
                                <p>Risparmio del Mese</p>
                            </div>
                            <div class="icon"><i class="fas fa-piggy-bank"></i></div>
                            <a href="reports?view=monthly" class="small-box-footer">Dettagli <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
                <!-- Grafici principali -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Entrate vs Uscite (ultimi 6 mesi)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="income-expense-chart" height="90"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="card border-primary shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-chart-area"></i> Risparmio 12 mesi</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="savings-trend-chart" height="90"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Widget KPI -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card border-success shadow-sm">
                            <div class="card-header bg-success text-white"><i class="fas fa-percentage"></i> Tasso di Risparmio</div>
                            <div class="card-body text-center">
                                <?php
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
                    <div class="col-md-4 mb-3">
                        <div class="card border-info shadow-sm">
                            <div class="card-header bg-info text-white"><i class="fas fa-bolt"></i> Spesa Media Giornaliera</div>
                            <div class="card-body text-center">
                                <?php
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
                    <div class="col-md-4 mb-3">
                        <div class="card border-warning shadow-sm">
                            <div class="card-header bg-warning text-white"><i class="fas fa-trophy"></i> Categoria Top Spesa</div>
                            <div class="card-body text-center">
                                <?php
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
                <!-- Widget personalizzati -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary shadow-sm">
                            <div class="card-header bg-primary text-white"><i class="fas fa-bell"></i> Notifiche Attive</div>
                            <div class="card-body text-center">
                                <?php
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
                    <div class="col-md-4 mb-3">
                        <div class="card border-secondary shadow-sm">
                            <div class="card-header bg-secondary text-white"><i class="fas fa-calendar-alt"></i> Prossima Scadenza Ricorrente</div>
                            <div class="card-body text-center">
                                <?php
                                $sql = "SELECT description, date FROM transactions WHERE type='uscita' AND user_phone='".$conn->real_escape_string($phone)."' AND date >= CURDATE() ORDER BY date ASC LIMIT 1";
                                $res = $conn->query($sql);
                                if($res && $row = $res->fetch_assoc()) {
                                    echo '<span class="badge badge-info">'.date('d/m/Y', strtotime($row['date'])).'</span>';
                                } else {
                                    echo '<span class="text-muted">Nessuna scadenza imminente</span>';
                                }
                                ?>
                                <div class="text-muted">Ricorrenza più vicina</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card border-dark shadow-sm">
                            <div class="card-header bg-dark text-white"><i class="fas fa-star"></i> Consiglio del Giorno</div>
                            <div class="card-body text-center">
                                <?php
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
                <!-- Tabelle -->
                <div class="row mb-4">
                    <div class="col-lg-7 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="card-title mb-0"><i class="fas fa-list"></i> Transazioni Recenti</h5>
                            </div>
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
                                            $date = date('d/m/Y', strtotime($row['date']));
                                            $category_color = $row['color'] ?? '#3498db';
                                            $category_name = $row['category_name'] ?? $row['category'];
                                            echo "<tr>";
                                            echo "<td>{$date}</td>";
                                            echo "<td>".htmlspecialchars($row['description'])."</td>";
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
                    <div class="col-lg-5 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="card-title mb-0"><i class="fas fa-bullseye"></i> Obiettivi di Risparmio</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php
                                $sql = "SELECT * FROM savings_goals WHERE user_id = ? ORDER BY target_date ASC";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result && $result->num_rows > 0) {
                                    while($goal = $result->fetch_assoc()) {
                                        $percentage = $goal['target_amount'] > 0 ? min(100, ($goal['current_amount']/$goal['target_amount'])*100) : 0;
                                        $date_text = $goal['target_date'] ? 'entro '.date('d/m/Y', strtotime($goal['target_date'])) : 'senza scadenza';
                                        echo '<div class="p-3">';
                                        echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                                        echo '<span class="font-weight-bold">'.htmlspecialchars($goal['name']).'</span>';
                                        echo '<span class="badge badge-secondary">'.format_currency($goal['current_amount']).' / '.format_currency($goal['target_amount']).'</span>';
                                        echo '</div>';
                                        echo '<div class="progress mb-1" style="height: 20px;">';
                                        echo '<div class="progress-bar bg-success" role="progressbar" style="width: '.$percentage.'%" aria-valuenow="'.$percentage.'" aria-valuemin="0" aria-valuemax="100">'.round($percentage, 1).'%</div>';
                                        echo '</div>';
                                        echo '<small class="text-muted">'.$date_text.'</small>';
                                        echo '</div>';
                                        echo '<div class="dropdown-divider"></div>';
                                    }
                                } else {
                                    echo '<div class="p-3 text-center">Nessun obiettivo di risparmio. <a href="savings.php">Aggiungi il tuo primo obiettivo!</a></div>';
                                }
                                $stmt->close();
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php include 'footer.php'; ?>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/main.js"></script>
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
    // Grafico risparmio 12 mesi
    fetch('get_chart_data.php?type=savings_trend')
        .then(response => response.json())
        .then(data => {
            new Chart(document.getElementById('savings-trend-chart').getContext('2d'), {
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
});
</script>
</body>
</html>
