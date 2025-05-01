<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in as employee
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../db_connect.php';

// Get product ID from URL
$product_id = $_GET['id'] ?? 0;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if product exists and get its details
    $stmt = $pdo->prepare("SELECT * FROM Product WHERE ProductID = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Product not found.');
    }

    // Check if product is in any active orders
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM OrderItem oi 
        JOIN `Order` o ON oi.OrderID = o.OrderID 
        WHERE oi.ProductID = ? AND o.Status IN ('Processing', 'Shipped')
    ");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        throw new Exception('Cannot delete product as it is part of active orders.');
    }

    // Delete product
    $stmt = $pdo->prepare("DELETE FROM Product WHERE ProductID = ?");
    $stmt->execute([$product_id]);

    // Commit transaction
    $pdo->commit();

    // Redirect with success message
    header('Location: products.php?success=Product deleted successfully');
    exit();

} 
catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Redirect with error message
    header('Location: products.php?error=' . urlencode($e->getMessage()));
    exit();
}
?> 