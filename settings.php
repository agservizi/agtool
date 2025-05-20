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
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">Profilo Utente</div>
                <div class="card-body">
                    <form id="profile-form">
                        <div class="form-group">
                            <label>Telefono</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" value="" placeholder="(in sviluppo)" disabled>
                        </div>
                        <div class="form-group">
                            <label>Nuova Password</label>
                            <input type="password" class="form-control" name="password" placeholder="(in sviluppo)" disabled>
                        </div>
                        <button type="submit" class="btn btn-primary" disabled>Salva Profilo</button>
                    </form>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">Preferenze Generali</div>
                <div class="card-body">
                    <form id="preferences-form">
                        <div class="form-group">
                            <label>Tema</label>
                            <select class="form-control" name="theme">
                                <option value="light">Chiaro</option>
                                <option value="dark">Scuro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lingua</label>
                            <select class="form-control" name="language">
                                <option value="it">Italiano</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary">Salva Preferenze</button>
                    </form>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">Limite di Spesa Mensile</div>
                <div class="card-body">
                    <form id="limit-form">
                        <div class="form-group">
                            <label>Limite spesa mensile (€)</label>
                            <input type="number" class="form-control" name="monthly_limit" min="0" step="0.01" value="">
                        </div>
                        <button type="submit" class="btn btn-info">Salva Limite</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">Notifiche</div>
                <div class="card-body">
                    <form id="notifications-form">
                        <div class="form-group">
                            <label><input type="checkbox" name="email_notifications"> Notifiche Email</label>
                        </div>
                        <div class="form-group">
                            <label><input type="checkbox" name="sms_notifications"> Notifiche SMS</label>
                        </div>
                        <button type="submit" class="btn btn-warning">Salva Notifiche</button>
                    </form>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">Esporta Dati</div>
                <div class="card-body">
                    <form action="export.php" method="post">
                        <button type="submit" class="btn btn-success">Scarica CSV</button>
                    </form>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">Elimina Account</div>
                <div class="card-body">
                    <form id="delete-account-form">
                        <p>Questa azione è <b>irreversibile</b>. Tutti i tuoi dati saranno eliminati.</p>
                        <button type="submit" class="btn btn-danger">Elimina Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function ajaxSettings(formId, action, successMsg) {
    document.getElementById(formId).addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', action);
        fetch('save_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if(data.status==='success') showToast('success', successMsg || data.message);
            else showToast('error', data.message);
            if(action==='delete_account' && data.status==='success') setTimeout(()=>window.location='login.php',1500);
        })
        .catch(()=>showToast('error','Errore di rete'));
    });
}
ajaxSettings('preferences-form','preferences','Preferenze salvate!');
ajaxSettings('limit-form','limit','Limite salvato!');
ajaxSettings('notifications-form','notifications','Notifiche salvate!');
ajaxSettings('delete-account-form','delete_account','Account eliminato!');
</script>
<?php include 'footer.php'; ?>
