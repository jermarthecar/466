<?php
// Enable error reporting
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

// Get past orders for the logged-in customer
$stmt = $pdo->prepare("
    SELECT o.OrderID, o.OrderDate, o.Status, o.OrderTotal 
    FROM `Order` o 
    WHERE o.CustomerID = ? 
    ORDER BY o.OrderDate DESC
");
$stmt->execute([$_SESSION['customer_id']]);
$orders = $stmt->fetchAll();
?>

<h2>My Orders</h2>

<!-- Diplay past orders -->
<?php if (count($orders) > 0): ?>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Status</th>
            <th>Total</th>
            <th>Action</th>
        </tr>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><?php echo $order['OrderID']; ?></td>
            <td><?php echo $order['OrderDate']; ?></td>
            <td><?php echo $order['Status']; ?></td>
            <td>$<?php echo number_format($order['OrderTotal'], 2); ?></td>
            <td>
                <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>You have no orders yet.</p>
    <a href="products.php" class="button">Start Shopping</a>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>