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

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];
$stmt = $pdo->prepare("
    SELECT o.*, s.TrackingNum, s.DateShipped, s.Notes 
    FROM `Order` o 
    LEFT JOIN Shipment s ON o.OrderID = s.OrderID 
    WHERE o.OrderID = ? AND o.CustomerID = ?
");
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT oi.*, p.Name 
    FROM OrderItem oi 
    JOIN Product p ON oi.ProductID = p.ProductID 
    WHERE oi.OrderID = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<h2>Order #<?php echo $order['OrderID']; ?></h2>

<p><strong>Date:</strong> <?php echo $order['OrderDate']; ?></p>
<p><strong>Status:</strong> <?php echo $order['Status']; ?></p>
<p><strong>Shipping Address:</strong> <?php echo nl2br(htmlspecialchars($order['ShippingAddress'])); ?></p>
<p><strong>Billing Address:</strong> <?php echo nl2br(htmlspecialchars($order['BillingAddress'])); ?></p>
<p><strong>Payment Method:</strong> <?php echo $order['PaymentMethod']; ?></p>

<?php if ($order['TrackingNum']): ?>
    <h3>Shipping Information</h3>
    <p><strong>Tracking Number:</strong> <?php echo $order['TrackingNum']; ?></p>
    <p><strong>Date Shipped:</strong> <?php echo $order['DateShipped']; ?></p>
    <?php if ($order['Notes']): ?>
        <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['Notes']); ?></p>
    <?php endif; ?>
<?php endif; ?>

<h3>Order Items</h3>
<table>
    <tr>
        <th>Product</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Subtotal</th>
    </tr>
    <?php foreach ($order_items as $item): ?>
    <tr>
        <td><?php echo htmlspecialchars($item['Name']); ?></td>
        <td>$<?php echo number_format($item['PriceAtOrderTime'], 2); ?></td>
        <td><?php echo $item['Quantity']; ?></td>
        <td>$<?php echo number_format($item['Subtotal'], 2); ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
        <td>$<?php echo number_format($order['OrderTotal'], 2); ?></td>
    </tr>
</table>

<a href="orders.php" class="button">Back to Orders</a>

<?php require_once '../includes/footer.php'; ?>