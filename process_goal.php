<?php
require_once 'inc/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Raccogli i dati dal form
    $name = clean_input($_POST['name']);
    $target_amount = floatval($_POST['target_amount']);
    $current_amount = isset($_POST['current_amount']) ? floatval($_POST['current_amount']) : 0;
    $target_date = !empty($_POST['target_date']) ? clean_input($_POST['target_date']) : NULL;
    
    // Verifica che i dati siano validi
    if (empty($name) || $target_amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Il nome e l\'importo obiettivo sono obbligatori e l\'importo deve essere maggiore di zero']);
        exit;
    }
    
    if ($current_amount < 0) {
        echo json_encode(['status' => 'error', 'message' => 'L\'importo attuale non può essere negativo']);
        exit;
    }
    
    if ($current_amount > $target_amount) {
        echo json_encode(['status' => 'error', 'message' => 'L\'importo attuale non può essere maggiore dell\'importo obiettivo']);
        exit;
    }
    
    // Inserisci l'obiettivo nel database
    $sql = "INSERT INTO savings_goals (name, target_amount, current_amount, target_date) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdds", $name, $target_amount, $current_amount, $target_date);
    
    if ($stmt->execute()) {
        // Obiettivo salvato con successo
        // Se è stata inserita una data obiettivo, crea una notifica di promemoria
        if ($target_date) {
            // Recupera l'utente attuale dalla sessione
            session_start();
            $phone = $_SESSION['user_phone'] ?? null;
            if ($phone) {
                $user_stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                $user_stmt->bind_param('s', $phone);
                $user_stmt->execute();
                $user_stmt->bind_result($user_id);
                if ($user_stmt->fetch()) {
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, scheduled_at) VALUES (?, 'email', ?, ?, ?)");
                    $title = 'Promemoria obiettivo di risparmio';
                    $msg = "Hai un obiettivo di risparmio ('" . $name . "') in scadenza il " . format_date($target_date) . ".";
                    $notif_stmt->bind_param('isss', $user_id, $title, $msg, $target_date);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
                $user_stmt->close();
            }
        }
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['status' => 'success', 'message' => 'Obiettivo di risparmio salvato con successo']);
        } else {
            header("Location: index.php?status=success&message=" . urlencode('Obiettivo di risparmio salvato con successo'));
        }
    } else {
        // Errore nel salvataggio dell'obiettivo
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['status' => 'error', 'message' => 'Errore nel salvataggio dell\'obiettivo: ' . $stmt->error]);
        } else {
            header("Location: index.php?status=error&message=" . urlencode('Errore nel salvataggio dell\'obiettivo: ' . $stmt->error));
        }
    }
    
    $stmt->close();
} else {
    // Richiesta non valida
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode(['status' => 'error', 'message' => 'Metodo di richiesta non valido']);
    } else {
        header("Location: index.php?status=error&message=" . urlencode('Metodo di richiesta non valido'));
    }
}

$conn->close();
?>
