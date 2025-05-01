<?php
// enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$username = "z1978803";
$password = "2004Sep07";

try { // if something goes wrong, an exception is thrown
    $dsn = "mysql:host=courses;dbname=z1978803";
    $pdo = new PDO($dsn, $username, $password);
}
catch(PDOexception $e) { // handle that exception
    echo "Connection to database failed: " . $e->getMessage();
    }
?>