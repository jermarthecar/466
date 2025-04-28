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

try {
    if (!isset($_GET['id'])) {
        throw new Exception("Order ID not provided");
    }

    $order_id = $_GET['id'];

    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, c.Name AS CustomerName 
        FROM `Order` o 
        JOIN Customer c ON o.CustomerID = c.CustomerID 
        WHERE o.OrderID = ? AND o.CustomerID = ?
    ");
    $stmt->execute([$order_id, $_SESSION['customer_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Order not found");
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.Name AS ProductName 
        FROM OrderItem oi 
        JOIN Product p ON oi.ProductID = p.ProductID 
        WHERE oi.OrderID = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <?php if (isset($error)): ?>
        <div class="error-message" style="color: red; padding: 10px; margin: 10px 0; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #4CAF50;">Order Confirmation</h1>
            <p style="font-size: 18px;">Thank you for your order, <?php echo htmlspecialchars($order['CustomerName']); ?>!</p>
            <p>Your order has been received and is being processed.</p>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 30px;">
            <h2>Order Details</h2>
            <p><strong>Order Number:</strong> #<?php echo $order['OrderID']; ?></p>
            <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['OrderDate'])); ?></p>
            <p><strong>Status:</strong> <?php echo $order['Status']; ?></p>
            <p><strong>Payment Method:</strong> <?php echo $order['PaymentMethod']; ?></p>
        </div>

        <div style="margin-bottom: 30px;">
            <h2>Shipping Information</h2>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px;">
                <p><?php echo nl2br(htmlspecialchars($order['ShippingAddress'])); ?></p>
            </div>
        </div>

        <div style="margin-bottom: 30px;">
            <h2>Order Items</h2>
            <table>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
                <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
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
        </div>

        <div style="text-align: center;">
            <a href="orders.php" class="button">View All Orders</a>
            <a href="products.php" class="button">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 