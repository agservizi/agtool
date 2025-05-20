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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #28a745 0%, #1abc9c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Source Sans Pro', Arial, sans-serif;
            animation: fadeInBg 1s;
        }
        @keyframes fadeInBg {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .login-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px 0 rgba(44, 62, 80, 0.18);
            padding: 2.5rem 2.5rem 2rem 2.5rem;
            min-width: 340px;
            max-width: 370px;
            width: 100%;
            text-align: center;
            position: relative;
            animation: fadeIn 0.7s;
        }
        .login-logo {
            font-size: 2.7rem;
            color: #28a745;
            margin-bottom: 0.5rem;
        }
        .login-title {
            font-size: 1.7rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 0.7rem;
            letter-spacing: 1px;
        }
        .login-subtitle {
            color: #888;
            font-size: 1.05rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.3rem;
            text-align: left;
        }
        .form-group label {
            font-weight: 600;
            color: #222;
            margin-bottom: 0.3rem;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            font-size: 1.1rem;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #28a745;
            outline: none;
        }
        .btn-login {
            width: 100%;
            padding: 0.7rem;
            background: linear-gradient(90deg, #28a745 60%, #1abc9c 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px #28a74522;
            transition: background 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #1abc9c 0%, #28a745 100%);
            box-shadow: 0 4px 16px #28a74533;
        }
        .login-error {
            color: #e74c3c;
            background: #fdecea;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .login-error i {
            font-size: 1.2rem;
        }
        @media (max-width: 480px) {
            .login-card { padding: 1.2rem 0.5rem; min-width: 0; }
        }
    </style>
</head>
<body>
    <form method="post" class="login-card">
        <div class="login-logo">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="login-title">AGTool Finance</div>
        <div class="login-subtitle">Accedi con il tuo numero di cellulare</div>
        <?php if ($login_error): ?>
            <div class="login-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $login_error; ?> </div>
        <?php endif; ?>
        <div class="form-group">
            <label for="phone"><i class="fas fa-mobile-alt"></i> Numero di cellulare</label>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="3773798570" required autofocus>
        </div>
        <button type="submit" class="btn-login">Accedi</button>
    </form>
</body>
</html>
