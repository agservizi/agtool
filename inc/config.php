<?php
/**
 * Configurazione del database
 */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'u427445037_agtool');
define('DB_PASS', 'Giogiu2123@');
define('DB_NAME', 'u427445037_agtool');

// Connessione al database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);

// Verifica la connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Crea il database se non esiste
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === FALSE) {
    die("Errore nella creazione del database: " . $conn->error);
}

// Seleziona il database
$conn->select_db(DB_NAME);

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
