<?php
session_start();

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

// Establish database connection
require_once '../db_connect.php';

// Include header
require_once '../includes/header.php';

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];
?>

<!-- HTML for Employee Dashboard -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Employee Dashboard</h1>

    <div style="display: flex; justify-content: space-around; margin: 20px 0;">
        <div style="text-align: center;">
            <h3>Pending Orders</h3>
            <?php
            // Ensure $pdo is available
             if (isset($pdo)) {
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `Order` WHERE Status = 'Processing'");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    echo '<p style="font-size: 24px;">' . ($count ?? 0) . '</p>';
                 } 
                 catch (PDOException $e) {
                    echo '<p style="color:red;">Error</p>';
                    error_log("DB Error in employee dashboard (Pending Orders): " . $e->getMessage());
                 }
             } 
             else {
                echo '<p style="color:red;">DB Error</p>';
             }

            ?>
            <a href="orders.php?status=Processing" class="button">View</a>
        </div>

        <div style="text-align: center;">
            <h3>Low Stock</h3>
             <?php
             // Ensure $pdo is available
              if (isset($pdo)) {
                 try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Product WHERE StockQuantity < 5");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    echo '<p style="font-size: 24px;">' . ($count ?? 0) . '</p>';
                 } 
                 catch (PDOException $e) {
                    echo '<p style="color:red;">Error</p>';
                    error_log("DB Error in employee dashboard (Low Stock): " . $e->getMessage());
                 }
              } 
              else {
                echo '<p style="color:red;">DB Error</p>';
              }
             ?>
            <a href="products.php?low_stock=1" class="button">View</a>
        </div>
    </div>

    <!-- Recent Orders and Low Stock Items -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
        <div>
            <h3>Recent Orders</h3>
            <?php
            // Ensure $pdo is available
            if (isset($pdo)) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT o.OrderID, o.OrderDate, o.Status, o.OrderTotal, c.Name AS CustomerName
                        FROM `Order` o
                        JOIN Customer c ON o.CustomerID = c.CustomerID
                        ORDER BY o.OrderDate DESC
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $orders = $stmt->fetchAll();
                    
                    // Display recent orders in a table
                    if (count($orders) > 0): ?>
                        <table>
                            <thead> <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                             </thead>
                             <!-- Table Body -->
                             <tbody> <?php foreach ($orders as $order): ?>
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
                             </tbody>
                        </table>
                    <?php else: ?>
                        <p>No recent orders found.</p>
                    <?php endif;
                } catch (PDOException $e) {
                    echo '<p style="color:red;">Error loading orders.</p>';
                    error_log("DB Error in employee dashboard (Recent Orders): " . $e->getMessage());
                }
            } else {
                 echo '<p style="color:red;">DB Error loading orders.</p>';
            }
            ?>
        </div>

        <div>
            <h3>Low Stock Items</h3>
            <?php
             // Ensure $pdo is available
            if (isset($pdo)) {
                 try {
                    $stmt = $pdo->prepare("
                        SELECT Name, Category, Price, StockQuantity
                        FROM Product
                        WHERE StockQuantity < 5
                        ORDER BY StockQuantity ASC
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $products = $stmt->fetchAll();
                    
                    // Display low stock items in a table
                    if (count($products) > 0): ?>
                        <table>
                             <thead> <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                            </thead>
                            <!-- Table Body -->
                            <tbody> <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['Name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['Category'] ?? 'N/A');?></td>
                                    <td>$<?php echo number_format($product['Price'], 2); ?></td>
                                    <td><?php echo $product['StockQuantity']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                             </tbody>
                        </table>
                    <?php else: ?>
                        <p>No low stock items found.</p>
                    <?php endif;
                 } 
                 catch (PDOException $e) {
                    echo '<p style="color:red;">Error loading low stock items.</p>';
                    error_log("DB Error in employee dashboard (Low Stock Items): " . $e->getMessage());
                 }
            } 
            else {
                echo '<p style="color:red;">DB Error loading low stock items.</p>';
            }
            ?>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <h3>Quick Actions</h3>
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
            <a href="orders.php" class="button">Manage Orders</a>
            <a href="products.php" class="button">View Products</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>