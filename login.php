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
            background: linear-gradient(120deg, #232526 0%, #1abc9c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Source Sans Pro', Arial, sans-serif;
            margin: 0;
        }
        .login-glass {
            background: rgba(255,255,255,0.13);
            border-radius: 22px;
            box-shadow: 0 8px 32px 0 rgba(44, 62, 80, 0.18);
            border: 1.5px solid rgba(255,255,255,0.18);
            padding: 2.7rem 2.5rem 2.2rem 2.5rem;
            min-width: 350px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            position: relative;
            backdrop-filter: blur(7px);
            animation: fadeIn 0.8s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #28a745 60%, #1abc9c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.1rem auto;
            box-shadow: 0 2px 12px #28a74533;
        }
        .login-logo i {
            color: #fff;
            font-size: 2.2rem;
        }
        .login-title {
            font-size: 2.1rem;
            font-weight: 800;
            color: #232526;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }
        .login-subtitle {
            color: #444;
            font-size: 1.08rem;
            margin-bottom: 1.7rem;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .form-group label {
            font-weight: 600;
            color: #232526;
            margin-bottom: 0.3rem;
            display: block;
            font-size: 1.08rem;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem 1.1rem;
            border-radius: 10px;
            border: 1.5px solid #e0e0e0;
            font-size: 1.13rem;
            background: rgba(255,255,255,0.7);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: #1abc9c;
            outline: none;
            box-shadow: 0 0 0 2px #1abc9c33;
        }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(90deg, #28a745 60%, #1abc9c 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1.18rem;
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
            border-radius: 7px;
            padding: 0.8rem 1.1rem;
            margin-bottom: 1.1rem;
            font-size: 1.08rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border: 1px solid #e74c3c22;
        }
        .login-error i {
            font-size: 1.3rem;
        }
        @media (max-width: 480px) {
            .login-glass { padding: 1.2rem 0.5rem; min-width: 0; }
        }
    </style>
</head>
<body>
    <form method="post" class="login-glass">
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
            <input type="text" name="phone" id="phone" class="form-control" required autofocus autocomplete="tel">
        </div>
        <button type="submit" class="btn-login">Accedi</button>
    </form>
</body>
</html>
