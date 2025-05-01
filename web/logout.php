<?php
// enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit();
?>