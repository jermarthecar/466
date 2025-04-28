<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include auth functions first to start session
require_once '../includes/auth.php';

// Check if user is logged in as employee
if (!isEmployeeLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once 'header.php';

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Welcome, <?php echo htmlspecialchars($employee_name); ?></h1>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
        <!-- Orders Section -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 15px;">Orders</h2>
            <p style="margin-bottom: 20px;">Manage customer orders and shipments</p>
            <a href="orders.php" style="display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                View Orders
            </a>
        </div>

        <!-- Dashboard Section -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 15px;">Dashboard</h2>
            <p style="margin-bottom: 20px;">View store statistics and analytics</p>
            <a href="dashboard.php" style="display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                View Dashboard
            </a>
        </div>

        <!-- Quick Actions Section -->
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 15px;">Quick Actions</h2>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="orders.php?status=Processing" style="display: inline-block; background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                    Process New Orders
                </a>
                <a href="orders.php?status=Shipped" style="display: inline-block; background: #FF9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                    Track Shipments
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div style="margin-top: 40px;">
        <h2 style="margin-bottom: 20px;">Recent Orders</h2>
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT o.OrderID, o.OrderDate, o.Status, c.Name as CustomerName
                FROM `Order` o
                JOIN Customer c ON o.CustomerID = c.CustomerID
                ORDER BY o.OrderDate DESC
                LIMIT 5
            ");
            $stmt->execute();
            $recent_orders = $stmt->fetchAll();

            if ($recent_orders) {
                echo '<div style="overflow-x: auto;">';
                echo '<table style="width: 100%; border-collapse: collapse;">';
                echo '<thead><tr style="background: #f5f5f5;">';
                echo '<th style="padding: 10px; text-align: left;">Order ID</th>';
                echo '<th style="padding: 10px; text-align: left;">Customer</th>';
                echo '<th style="padding: 10px; text-align: left;">Date</th>';
                echo '<th style="padding: 10px; text-align: left;">Status</th>';
                echo '<th style="padding: 10px; text-align: left;">Action</th>';
                echo '</tr></thead><tbody>';

                foreach ($recent_orders as $order) {
                    echo '<tr style="border-bottom: 1px solid #ddd;">';
                    echo '<td style="padding: 10px;">#' . htmlspecialchars($order['OrderID']) . '</td>';
                    echo '<td style="padding: 10px;">' . htmlspecialchars($order['CustomerName']) . '</td>';
                    echo '<td style="padding: 10px;">' . htmlspecialchars($order['OrderDate']) . '</td>';
                    echo '<td style="padding: 10px;">' . htmlspecialchars($order['Status']) . '</td>';
                    echo '<td style="padding: 10px;">';
                    echo '<a href="order_detail.php?id=' . $order['OrderID'] . '" style="color: #4CAF50; text-decoration: none;">View Details</a>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo '<p>No recent orders found.</p>';
            }
        } catch (PDOException $e) {
            echo '<p style="color: red;">Error loading recent orders: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 