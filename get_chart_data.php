<?php
require_once 'inc/config.php';

// Verifica che sia stato fornito un tipo di grafico
if (!isset($_GET['type']) || empty($_GET['type'])) {
    echo json_encode(['error' => 'Tipo di grafico non specificato']);
    exit;
}

$chart_type = clean_input($_GET['type']);

// Ottieni i dati per il grafico entrate vs uscite
if ($chart_type === 'income_expense') {
    // Ottieni i dati degli ultimi 6 mesi
    $labels = [];
    $income_data = [];
    $expense_data = [];
    $savings_data = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('m', strtotime("-$i months"));
        $year = date('Y', strtotime("-$i months"));
        
        // Formato del mese per il label del grafico
        $month_label = date('M Y', strtotime("$year-$month-01"));
        
        // Ottieni le entrate del mese
        $sql_income = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                     WHERE type = 'entrata' 
                     AND MONTH(date) = $month 
                     AND YEAR(date) = $year";
        
        $result_income = $conn->query($sql_income);
        $income = $result_income->fetch_assoc()['total'];
        
        // Ottieni le uscite del mese
        $sql_expense = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
                      WHERE type = 'uscita' 
                      AND MONTH(date) = $month 
                      AND YEAR(date) = $year";
        
        $result_expense = $conn->query($sql_expense);
        $expense = $result_expense->fetch_assoc()['total'];
        
        // Calcola il risparmio del mese
        $savings = $income - $expense;
        
        // Aggiungi i dati agli array
        $labels[] = $month_label;
        $income_data[] = $income;
        $expense_data[] = $expense;
        $savings_data[] = $savings;
    }
    
    // Restituisci i dati in formato JSON
    echo json_encode([
        'labels' => $labels,
        'income' => $income_data,
        'expense' => $expense_data,
        'savings' => $savings_data
    ]);
    exit;
}

// Ottieni i dati per il grafico delle categorie di spesa
if ($chart_type === 'expense_categories') {
    // Ottieni il mese e l'anno corrente
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    // Ottieni le categorie di spesa per il mese selezionato
    $sql = "SELECT category, SUM(amount) as total 
            FROM transactions 
            WHERE type = 'uscita' 
            AND MONTH(date) = $month 
            AND YEAR(date) = $year 
            GROUP BY category 
            ORDER BY total DESC";
    
    $result = $conn->query($sql);
    
    $categories = [];
    $amounts = [];
    $colors = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $category = $row['category'];
            
            // Ottieni il colore della categoria
            $sql_color = "SELECT color FROM categories WHERE name = '$category' AND type = 'uscita'";
            $result_color = $conn->query($sql_color);
            
            if ($result_color->num_rows > 0) {
                $color = $result_color->fetch_assoc()['color'];
            } else {
                $color = '#' . substr(md5($category), 0, 6); // Genera un colore casuale basato sul nome della categoria
            }
            
            $categories[] = $category;
            $amounts[] = $row['total'];
            $colors[] = $color;
        }
    }
    
    // Restituisci i dati in formato JSON
    echo json_encode([
        'categories' => $categories,
        'amounts' => $amounts,
        'colors' => $colors
    ]);
    exit;
}

// Previsioni di spesa/risparmio
if ($chart_type === 'forecast') {
    $category = isset($_GET['category']) ? clean_input($_GET['category']) : '';
    $now = date('Y-m-01');
    $labels = [];
    $expense_history = [];
    $income_history = [];
    // Calcola la media degli ultimi 3 mesi
    for ($i = 3; $i >= 1; $i--) {
        $month = date('m', strtotime("-$i months"));
        $year = date('Y', strtotime("-$i months"));
        $cat_filter = $category ? " AND category='".$conn->real_escape_string($category)."'" : '';
        $sql_exp = "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='uscita' AND MONTH(date)=$month AND YEAR(date)=$year$cat_filter";
        $sql_inc = "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='entrata' AND MONTH(date)=$month AND YEAR(date)=$year$cat_filter";
        $res_exp = $conn->query($sql_exp);
        $res_inc = $conn->query($sql_inc);
        $expense_history[] = $res_exp->fetch_assoc()['total'];
        $income_history[] = $res_inc->fetch_assoc()['total'];
    }
    $avg_exp = count($expense_history) ? array_sum($expense_history)/count($expense_history) : 0;
    $avg_inc = count($income_history) ? array_sum($income_history)/count($income_history) : 0;
    // Spesa/entrata attuale mese
    $cur_month = date('m');
    $cur_year = date('Y');
    $cat_filter = $category ? " AND category='".$conn->real_escape_string($category)."'" : '';
    $sql_exp = "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='uscita' AND MONTH(date)=$cur_month AND YEAR(date)=$cur_year$cat_filter";
    $sql_inc = "SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE type='entrata' AND MONTH(date)=$cur_month AND YEAR(date)=$cur_year$cat_filter";
    $cur_exp = $conn->query($sql_exp)->fetch_assoc()['total'];
    $cur_inc = $conn->query($sql_inc)->fetch_assoc()['total'];
    // Previsione mese prossimo = media ultimi 3 mesi
    $forecast_exp = $avg_exp;
    $forecast_inc = $avg_inc;
    // Alert se la spesa attuale supera la media
    $alert = null;
    // Controllo limiti personalizzati
    if ($category) {
        $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
        if ($user_id) {
            $sql_limit = "SELECT monthly_limit FROM category_limits WHERE user_id = $user_id AND category = '".$conn->real_escape_string($category)."'";
            $res_limit = $conn->query($sql_limit);
            if ($res_limit && $row_limit = $res_limit->fetch_assoc()) {
                $limit = floatval($row_limit['monthly_limit']);
                if ($cur_exp > $limit) {
                    $alert = "Hai superato il limite mensile di spesa per la categoria '$category' (€".number_format($limit,2,',','.').")!";
                }
            }
        }
    }
    if (!$alert && $cur_exp > $avg_exp * 1.1 && $cur_exp > 0) {
        $alert = $category ? 'Attenzione: la spesa di questo mese per la categoria \''.$category.'\' supera la media!' : 'Attenzione: la spesa di questo mese supera la media degli ultimi mesi!';
    }
    echo json_encode([
        'current_expense' => $cur_exp,
        'current_income' => $cur_inc,
        'forecast_expense' => $forecast_exp,
        'forecast_income' => $forecast_inc,
        'alert' => $alert
    ]);
    exit;
}

// Se il tipo di grafico non è riconosciuto
echo json_encode(['error' => 'Tipo di grafico non valido']);
?>
