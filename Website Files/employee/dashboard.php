<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php';

// Check if user is logged in as employee
if (!isset($_SESSION['employee_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: ../login.php');
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once '../includes/header.php';

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Employee Dashboard</h1>
    
    <div style="display: flex; justify-content: space-around; margin: 20px 0;">
        <div style="text-align: center;">
            <h3>Pending Orders</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `Order` WHERE Status = 'Processing'");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            ?>
            <p style="font-size: 24px;"><?php echo $count; ?></p>
            <a href="orders.php?status=Processing" class="button">View</a>
        </div>
        
        <div style="text-align: center;">
            <h3>Low Stock</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Product WHERE StockQuantity < 5");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            ?>
            <p style="font-size: 24px;"><?php echo $count; ?></p>
            <a href="products.php?low_stock=1" class="button">View</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
        <!-- Recent Orders -->
        <div>
            <h3>Recent Orders</h3>
            <?php
            $stmt = $pdo->prepare("
                SELECT o.OrderID, o.OrderDate, o.Status, o.OrderTotal, c.Name AS CustomerName 
                FROM `Order` o 
                JOIN Customer c ON o.CustomerID = c.CustomerID 
                ORDER BY o.OrderDate DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $orders = $stmt->fetchAll();
            ?>

            <?php if (count($orders) > 0): ?>
                <table>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['OrderID']); ?></td>
                            <td><?php echo htmlspecialchars($order['OrderDate']); ?></td>
                            <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                            <td><?php echo htmlspecialchars($order['Status']); ?></td>
                            <td>$<?php echo number_format($order['OrderTotal'], 2); ?></td>
                            <td>
                                <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>" class="button">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No recent orders found.</p>
            <?php endif; ?>
        </div>

        <!-- Low Stock Items -->
        <div>
            <h3>Low Stock Items</h3>
            <?php
            $stmt = $pdo->prepare("
                SELECT Name, Category, Price, StockQuantity
                FROM Product
                WHERE StockQuantity < 5
                ORDER BY StockQuantity ASC
                LIMIT 5
            ");
            $stmt->execute();
            $products = $stmt->fetchAll();
            ?>

            <?php if (count($products) > 0): ?>
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                    </tr>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['Name']); ?></td>
                            <td><?php echo htmlspecialchars($product['Category']); ?></td>
                            <td>$<?php echo number_format($product['Price'], 2); ?></td>
                            <td><?php echo $product['StockQuantity']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No low stock items found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin-top: 30px; text-align: center;">
        <h3>Quick Actions</h3>
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
            <a href="orders.php" class="button">Manage Orders</a>
            <a href="products.php" class="button">View Products</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>