<?php
/**
 * Configurazione del database
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'u427445037_agtool');
define('DB_PASS', 'Giogiu2123@');
define('DB_NAME', 'u427445037_agtool');

// Connessione diretta al database specificato
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Verifica la connessione
if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}

// Funzione per proteggere i dati inseriti
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Funzione per formattare le date
function format_date($date) {
    return date('d/m/Y', strtotime($date));
}

// Funzione per formattare valute
function format_currency($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}
?>
