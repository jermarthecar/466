<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php'; // Starts session if not started

// Check if user is logged in as owner using the function
if (!isOwnerLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Establish database connection
require_once '../db_connect.php';

// Include header
require_once '../includes/header.php';

// Get owner name for welcome message
$owner_name = $_SESSION['employee_name'];

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

if (!$order_id) {
    header('Location: index.php'); // Redirect to owner dashboard if no ID
    exit();
}

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, c.Name as CustomerName, c.Email as CustomerEmail, c.ShippingAddress,
               s.TrackingNum, s.DateShipped, s.Notes as ShippingNotes
        FROM `Order` o
        JOIN Customer c ON o.CustomerID = c.CustomerID
        LEFT JOIN Shipment s ON o.OrderID = s.OrderID
        WHERE o.OrderID = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: index.php'); // Redirect if order not found
        exit();
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.Name as ProductName, p.Description as ProductDescription, p.Price as ProductPrice
        FROM OrderItem oi
        JOIN Product p ON oi.ProductID = p.ProductID
        WHERE oi.OrderID = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
} 
catch (PDOException $e) {
    $error = "Error loading order details: " . $e->getMessage();
}
?>

<!-- HTML and CSS for the order details page -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Order Details</h1>

    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1; background: #f9f9f9; padding: 20px; border-radius: 5px;">
                <h2>Order Information</h2>
                <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['OrderID']); ?></p>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['OrderDate']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($order['Status']); ?></p>
                <p><strong>Total:</strong> $<?php echo number_format($order['OrderTotal'], 2); ?></p>

                <h3 style="margin-top: 20px;">Customer Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['CustomerName']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['CustomerEmail']); ?></p>

                <h3 style="margin-top: 20px;">Shipping Information</h3>
                <p><strong>Address:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($order['ShippingAddress'])); ?></p>

                <?php if ($order['TrackingNum']): ?>
                    <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['TrackingNum']); ?></p>
                    <p><strong>Date Shipped:</strong> <?php echo htmlspecialchars($order['DateShipped']); ?></p>
                    <?php if ($order['ShippingNotes']): ?>
                        <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['ShippingNotes']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div style="flex: 1; background: #f9f9f9; padding: 20px; border-radius: 5px;">
                <h2>Order Items</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 10px; text-align: left;">Product</th>
                            <th style="padding: 10px; text-align: left;">Quantity</th>
                            <th style="padding: 10px; text-align: left;">Price</th>
                            <th style="padding: 10px; text-align: left;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($item['ProductName']); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($item['Quantity']); ?></td>
                                <td style="padding: 10px;">$<?php echo number_format($item['ProductPrice'], 2); ?></td>
                                <td style="padding: 10px;">$<?php echo number_format($item['Subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="text-align: right; margin-top: 20px;">
                    <p><strong>Subtotal:</strong> $<?php echo number_format($order['OrderTotal'], 2); ?></p>
                    <p><strong>Tax:</strong> $<?php echo number_format($order['Tax'] ?? 0, 2); ?></p>
                    <p><strong>Shipping:</strong> $<?php echo number_format($order['ShippingCost'] ?? 0, 2); ?></p>
                    <p><strong>Total:</strong> $<?php echo number_format(($order['OrderTotal'] + ($order['Tax'] ?? 0) + ($order['ShippingCost'] ?? 0)), 2); ?></p>
                 </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" class="button">Back to Dashboard</a>
             <a href="orders.php" class="button">Back to Orders</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>