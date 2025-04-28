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

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once '../includes/header.php';

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, c.Name as CustomerName, c.Email as CustomerEmail
        FROM `Order` o
        JOIN Customer c ON o.CustomerID = c.CustomerID
        WHERE o.OrderID = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: orders.php');
        exit();
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.Name as ProductName
        FROM OrderItem oi
        JOIN Product p ON oi.ProductID = p.ProductID
        WHERE oi.OrderID = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading order details: " . $e->getMessage();
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Order Details</h1>
    
    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h2>Order Information</h2>
            <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['OrderID']); ?></p>
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['CustomerName']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['CustomerEmail']); ?></p>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['OrderDate']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['Status']); ?></p>
            <p><strong>Total:</strong> $<?php echo number_format($order['OrderTotal'], 2); ?></p>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
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
                            <td style="padding: 10px;">$<?php echo number_format($item['PriceAtOrderTime'], 2); ?></td>
                            <td style="padding: 10px;">$<?php echo number_format($item['Subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="orders.php" class="button" style="display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                Back to Orders
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
