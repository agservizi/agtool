<?php
require_once 'inc/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Raccogli i dati dal form
    $type = clean_input($_POST['type']);
    $amount = floatval($_POST['amount']);
    $description = clean_input($_POST['description']);
    $category = clean_input($_POST['category']);
    $date = clean_input($_POST['date']);
    
    // Verifica che i dati siano validi
    if (empty($description) || $amount <= 0 || empty($date)) {
        echo json_encode(['status' => 'error', 'message' => 'Tutti i campi sono obbligatori e l\'importo deve essere maggiore di zero']);
        exit;
    }
    
    if ($type != 'entrata' && $type != 'uscita') {
        echo json_encode(['status' => 'error', 'message' => 'Tipo di transazione non valido']);
        exit;
    }
    
    // Inserisci la transazione nel database
    $sql = "INSERT INTO transactions (description, amount, type, category, date) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsss", $description, $amount, $type, $category, $date);
    
    if ($stmt->execute()) {
        // Se è un obiettivo di risparmio, aggiorna l'importo attuale
        if ($type == 'entrata' && isset($_POST['goal_id']) && !empty($_POST['goal_id'])) {
            $goal_id = intval($_POST['goal_id']);
            
            // Aggiorna l'importo attuale dell'obiettivo
            $update_goal = "UPDATE savings_goals 
                           SET current_amount = current_amount + ? 
                           WHERE id = ?";
            
            $update_stmt = $conn->prepare($update_goal);
            $update_stmt->bind_param("di", $amount, $goal_id);
            $update_stmt->execute();
        }
        
        // Transazione salvata con successo
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['status' => 'success', 'message' => 'Transazione salvata con successo']);
        } else {
            header("Location: index.php?status=success&message=" . urlencode('Transazione salvata con successo'));
        }
    } else {
        // Errore nel salvataggio della transazione
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['status' => 'error', 'message' => 'Errore nel salvataggio della transazione: ' . $stmt->error]);
        } else {
            header("Location: index.php?status=error&message=" . urlencode('Errore nel salvataggio della transazione: ' . $stmt->error));
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
