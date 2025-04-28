<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php';

// Check if user is logged in as owner
if (!isOwnerLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once 'header.php';

// Get owner name for welcome message
$owner_name = $_SESSION['employee_name'];

try {
    // Get inventory summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(StockQuantity) as total_stock,
            SUM(CASE WHEN StockQuantity = 0 THEN 1 ELSE 0 END) as out_of_stock
        FROM Product
    ");
    $stmt->execute();
    $inventory_summary = $stmt->fetch();

    // Get recent orders
    $stmt = $pdo->prepare("
        SELECT o.OrderID, o.OrderDate, o.Status, o.OrderTotal,
               c.Name as CustomerName, COUNT(oi.OrderItemID) as ItemCount
        FROM `Order` o
        JOIN Customer c ON o.CustomerID = c.CustomerID
        JOIN OrderItem oi ON o.OrderID = oi.OrderID
        GROUP BY o.OrderID
        ORDER BY o.OrderDate DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();

    // Get low stock items
    $stmt = $pdo->prepare("
        SELECT p.ProductID, p.Name, p.StockQuantity, p.Price
        FROM Product p
        WHERE p.StockQuantity < 10
        ORDER BY p.StockQuantity ASC
        LIMIT 5
    ");
    $stmt->execute();
    $low_stock = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Owner Dashboard</h1>
    <p style="text-align: center; margin-bottom: 30px;">Welcome, <?php echo htmlspecialchars($owner_name); ?>!</p>

    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
            <h3>Total Products</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo $inventory_summary['total_products']; ?></p>
            <a href="../owner/products.php" class="button">Manage Products</a>
        </div>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
            <h3>Total Stock</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo $inventory_summary['total_stock']; ?></p>
        </div>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
            <h3>Out of Stock</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo $inventory_summary['out_of_stock']; ?></p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
            <h2>Recent Orders</h2>
            <?php if ($recent_orders): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 10px; text-align: left;">Order ID</th>
                            <th style="padding: 10px; text-align: left;">Date</th>
                            <th style="padding: 10px; text-align: left;">Customer</th>
                            <th style="padding: 10px; text-align: left;">Status</th>
                            <th style="padding: 10px; text-align: left;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;">
                                    <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>">
                                        #<?php echo $order['OrderID']; ?>
                                    </a>
                                </td>
                                <td style="padding: 10px;"><?php echo $order['OrderDate']; ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                                <td style="padding: 10px;"><?php echo $order['Status']; ?></td>
                                <td style="padding: 10px;">$<?php echo number_format($order['OrderTotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="../owner/orders.php" class="button">View All Orders</a>
                </div>
            <?php else: ?>
                <p>No recent orders found.</p>
            <?php endif; ?>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
            <h2>Low Stock Items</h2>
            <?php if ($low_stock): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 10px; text-align: left;">Product</th>
                            <th style="padding: 10px; text-align: left;">Stock</th>
                            <th style="padding: 10px; text-align: left;">Price</th>
                            <th style="padding: 10px; text-align: left;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $product): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px;">
                                    <a href="../owner/product_detail.php?id=<?php echo $product['ProductID']; ?>">
                                        <?php echo htmlspecialchars($product['Name']); ?>
                                    </a>
                                </td>
                                <td style="padding: 10px;"><?php echo $product['StockQuantity']; ?></td>
                                <td style="padding: 10px;">$<?php echo number_format($product['Price'], 2); ?></td>
                                <td style="padding: 10px;">
                                    <a href="../owner/edit_product.php?id=<?php echo $product['ProductID']; ?>" class="button">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No low stock items found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 