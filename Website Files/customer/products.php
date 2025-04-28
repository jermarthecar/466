<?php
// ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and check authentication first
session_start();
require_once '../includes/auth.php';
require_once '../db_connect.php';

// Check if user is logged in as customer
if (!isCustomerLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Include header after authentication check
require_once '../includes/header.php';
?>

<h2>Products</h2>

<?php
$stmt = $pdo->prepare("SELECT * FROM Product WHERE StockQuantity > 0");
$stmt->execute();
$products = $stmt->fetchAll();

if (count($products) > 0): ?>
    <table>
        <tr>
            <th>Name</th>
            <th>Price</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Action</th>
        </tr>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?php echo htmlspecialchars($product['Name']); ?></td>
            <td>$<?php echo number_format($product['Price'], 2); ?></td>
            <td><?php echo htmlspecialchars($product['Category']); ?></td>
            <td><?php echo $product['StockQuantity']; ?></td>
            <td>
                <a href="product_detail.php?id=<?php echo $product['ProductID']; ?>">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>No products available.</p>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>