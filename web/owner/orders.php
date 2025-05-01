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

try {
    // Get filter from URL
    $filter = $_GET['filter'] ?? 'all';

    // Base query
    $query = "
        SELECT o.*, c.Name as CustomerName,
               COUNT(oi.OrderItemID) as ItemCount,
               SUM(oi.Quantity) as TotalItems
        FROM `Order` o
        JOIN Customer c ON o.CustomerID = c.CustomerID
        JOIN OrderItem oi ON o.OrderID = oi.OrderID
    ";

    // Add filter conditions
    switch ($filter) {
        case 'processing':
            $query .= " WHERE o.Status = 'Processing'";
            break;
        case 'shipped':
            $query .= " WHERE o.Status = 'Shipped'";
            break;
        case 'delivered':
            $query .= " WHERE o.Status = 'Delivered'";
            break;
        case 'cancelled':
            $query .= " WHERE o.Status = 'Cancelled'";
            break;
    }

    $query .= " GROUP BY o.OrderID ORDER BY o.OrderDate DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll();
} 
catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!-- HTML and CSS for the page -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Order Management</h1>

    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <a href="?filter=all" class="button">All Orders</a>
        <a href="?filter=processing" class="button">Processing</a>
        <a href="?filter=shipped" class="button">Shipped</a>
        <a href="?filter=delivered" class="button">Delivered</a>
        <a href="?filter=cancelled" class="button">Cancelled</a>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left;">Order ID</th>
                <th style="padding: 10px; text-align: left;">Date</th>
                <th style="padding: 10px; text-align: left;">Customer</th>
                <th style="padding: 10px; text-align: left;">Status</th>
                <th style="padding: 10px; text-align: left;">Items</th>
                <th style="padding: 10px; text-align: left;">Total</th>
                <th style="padding: 10px; text-align: left;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Loop through orders and display them -->
            <?php foreach ($orders as $order): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;">
                        <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>">
                            #<?php echo $order['OrderID']; ?>
                        </a>
                    </td>
                    <td style="padding: 10px;"><?php echo $order['OrderDate']; ?></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                    <td style="padding: 10px;">
                        <?php
                        $status_class = '';
                        // Set status color based on order status
                        switch ($order['Status']) {
                            case 'Processing':
                                $status_class = 'color: orange;';
                                break;
                            case 'Shipped':
                                $status_class = 'color: blue;';
                                break;
                            case 'Delivered':
                                $status_class = 'color: green;';
                                break;
                            case 'Cancelled':
                                $status_class = 'color: red;';
                                break;
                        }
                        ?>
                        <span style="<?php echo $status_class; ?>">
                            <?php echo $order['Status']; ?>
                        </span>
                    </td>
                    <td style="padding: 10px;"><?php echo $order['TotalItems']; ?></td>
                    <td style="padding: 10px;">$<?php echo number_format($order['OrderTotal'], 2); ?></td>
                    <td style="padding: 10px;">
                        <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>" class="button">View Details</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>