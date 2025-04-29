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
require_once '../includes/header.php';

// Get owner name for welcome message
$owner_name = $_SESSION['employee_name'];

try {
    // Get total number of orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM `Order`");
    $stmt->execute();
    $total_orders = $stmt->fetch()['total'];

    // Get total number of customers
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM Customer");
    $stmt->execute();
    $total_customers = $stmt->fetch()['total'];

    // Get total revenue
    $stmt = $pdo->prepare("SELECT SUM(OrderTotal) as total FROM `Order` WHERE Status != 'Cancelled'");
    $stmt->execute();
    $total_revenue = $stmt->fetch()['total'] ?? 0;

    // Get low stock products (less than 10 items)
    $stmt = $pdo->prepare("
        SELECT * FROM Product 
        WHERE StockQuantity < 10 
        ORDER BY StockQuantity ASC
    ");
    $stmt->execute();
    $low_stock_products = $stmt->fetchAll();

    // Get recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, c.Name as CustomerName 
        FROM `Order` o 
        JOIN Customer c ON o.CustomerID = c.CustomerID 
        ORDER BY o.OrderDate DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1>Welcome, <?php echo htmlspecialchars($owner_name); ?>!</h1>

    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Dashboard Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
            <h3>Total Orders</h3>
            <p style="font-size: 24px;"><?php echo number_format($total_orders); ?></p>
        </div>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
            <h3>Total Customers</h3>
            <p style="font-size: 24px;"><?php echo number_format($total_customers); ?></p>
        </div>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
            <h3>Total Revenue</h3>
            <p style="font-size: 24px;">$<?php echo number_format($total_revenue, 2); ?></p>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div style="margin-top: 30px;">
        <h2>Low Stock Products</h2>
        <?php if ($low_stock_products): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; text-align: left;">Product</th>
                        <th style="padding: 10px; text-align: left;">Category</th>
                        <th style="padding: 10px; text-align: right;">Stock</th>
                        <th style="padding: 10px; text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock_products as $product): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;"><?php echo htmlspecialchars($product['Name']); ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($product['Category']); ?></td>
                            <td style="padding: 10px; text-align: right; color: <?php echo $product['StockQuantity'] == 0 ? 'red' : 'orange'; ?>;">
                                <?php echo $product['StockQuantity']; ?>
                            </td>
                            <td style="padding: 10px; text-align: right;">$<?php echo number_format($product['Price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No products are low in stock.</p>
        <?php endif; ?>
    </div>

    <!-- Recent Orders -->
    <div style="margin-top: 30px;">
        <h2>Recent Orders</h2>
        <?php if ($recent_orders): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; text-align: left;">Order ID</th>
                        <th style="padding: 10px; text-align: left;">Customer</th>
                        <th style="padding: 10px; text-align: left;">Date</th>
                        <th style="padding: 10px; text-align: left;">Status</th>
                        <th style="padding: 10px; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;">
                                <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>">#<?php echo $order['OrderID']; ?></a>
                            </td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                            <td style="padding: 10px;"><?php echo date('M j, Y g:i A', strtotime($order['OrderDate'])); ?></td>
                            <td style="padding: 10px;">
                                <?php
                                $status_color = '';
                                switch ($order['Status']) {
                                    case 'Processing':
                                        $status_color = 'orange';
                                        break;
                                    case 'Shipped':
                                        $status_color = 'blue';
                                        break;
                                    case 'Delivered':
                                        $status_color = 'green';
                                        break;
                                    case 'Cancelled':
                                        $status_color = 'red';
                                        break;
                                }
                                ?>
                                <span style="color: <?php echo $status_color; ?>;">
                                    <?php echo $order['Status']; ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: right;">$<?php echo number_format($order['OrderTotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No recent orders.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 