<?php
session_start();
require_once 'inc/config.php';
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

include 'header.php';
?>
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Impostazioni</h1>
    </div>
</div>
<div class="container-fluid">
    <div class="card mt-3">
        <div class="card-body">
            <h5>Impostazioni generali</h5>
            <p>Qui potrai configurare le impostazioni dell'applicazione. (Funzionalità in sviluppo)</p>
            <!-- Qui andranno i form e le opzioni di configurazione globali -->
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
