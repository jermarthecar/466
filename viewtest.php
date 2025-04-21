<?php
// ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// INCLUDE DATABASE CONNECTION
require_once 'db_connect.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to safely display values (handles NULL)
function safeDisplay($value) {
    return ($value === null) ? 'NULL' : htmlspecialchars($value);
}

// Get all tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "<h2>Table: $table</h2>";
    
    // Show table structure
    $structure = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Structure</h3>";
    echo "<table border='1'><tr>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    foreach ($structure as $column) {
        echo "<tr>";
        echo "<td>" . safeDisplay($column['Field']) . "</td>";
        echo "<td>" . safeDisplay($column['Type']) . "</td>";
        echo "<td>" . safeDisplay($column['Null']) . "</td>";
        echo "<td>" . safeDisplay($column['Key']) . "</td>";
        echo "<td>" . safeDisplay($column['Default']) . "</td>";
        echo "<td>" . safeDisplay($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Show table data (first 10 rows)
    $data = $pdo->query("SELECT * FROM `$table` LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($data)) {
        echo "<h3>Data (first 10 rows)</h3>";
        echo "<table border='1'><tr>";
        
        // Headers
        foreach (array_keys($data[0]) as $column) {
            echo "<th>" . safeDisplay($column) . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . safeDisplay($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data found in this table.</p>";
    }
    
    echo "<hr>";
}

?>