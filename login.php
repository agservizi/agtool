<?php
require_once 'inc/config.php';

// Gestione login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    if ($phone === '') {
        $login_error = 'Inserisci il numero di cellulare.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            session_start();
            $_SESSION['user_phone'] = $phone;
            header('Location: index.php');
            exit;
        } else {
            $login_error = 'Numero non riconosciuto.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - AGTool Finance</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="display:flex;align-items:center;justify-content:center;height:100vh;background:#f4f6f9;">
    <form method="post" style="background:#fff;padding:2rem 2.5rem;border-radius:8px;box-shadow:0 2px 8px #0001;min-width:300px;">
        <h2 style="margin-bottom:1.5rem;">Login</h2>
        <?php if ($login_error): ?>
            <div style="color:red;margin-bottom:1rem;"> <?php echo $login_error; ?> </div>
        <?php endif; ?>
        <div class="form-group" style="margin-bottom:1rem;">
            <label for="phone">Numero di cellulare</label>
            <input type="text" name="phone" id="phone" class="form-control" style="width:100%;padding:0.5rem;" placeholder="3773798570" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:0.5rem;">Accedi</button>
    </form>
</body>
</html>
