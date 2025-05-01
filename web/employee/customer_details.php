<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php';

// Check if user is logged in as employee
if (!isEmployeeLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Establish database connection
require_once '../db_connect.php';

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$customer_id) {
    header("Location: customers.php");
    exit();
}

try {
    // Get customer details
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            COUNT(DISTINCT o.OrderID) as total_orders,
            COALESCE(SUM(o.OrderTotal), 0) as total_spent,
            MAX(o.OrderDate) as last_order_date
        FROM Customer c
        LEFT JOIN `Order` o ON c.CustomerID = o.CustomerID
        WHERE c.CustomerID = ?
        GROUP BY c.CustomerID
    ");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        header("Location: customers.php");
        exit();
    }

    // Get customer's order history
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            COUNT(oi.OrderItemID) as item_count,
            s.TrackingNum,
            s.DateShipped,
            s.Notes as ShippingNotes
        FROM `Order` o
        LEFT JOIN OrderItem oi ON o.OrderID = oi.OrderID
        LEFT JOIN Shipment s ON o.OrderID = s.OrderID
        WHERE o.CustomerID = ?
        GROUP BY o.OrderID
        ORDER BY o.OrderDate DESC
    ");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll();

} 
catch (PDOException $e) {
    $error_message = "Error fetching customer details: " . $e->getMessage();
}

// Include header
require_once '../includes/header.php';
?>

<!-- HTML and CSS for Customer Details Page -->
<div class="container">
    <h1>Customer Details</h1>

    <?php if (isset($error_message)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Customer Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <h3>Name</h3>
                <p><?php echo htmlspecialchars($customer['Name']); ?></p>
            </div>
            <div class="info-item">
                <h3>Email</h3>
                <p><?php echo htmlspecialchars($customer['Email']); ?></p>
            </div>
            <div class="info-item">
                <h3>Total Orders</h3>
                <p><?php echo number_format($customer['total_orders']); ?></p>
            </div>
            <div class="info-item">
                <h3>Total Spent</h3>
                <p>$<?php echo number_format($customer['total_spent'], 2); ?></p>
            </div>
            <div class="info-item">
                <h3>Last Order</h3>
                <p><?php echo $customer['last_order_date'] ? date('M d, Y', strtotime($customer['last_order_date'])) : 'Never'; ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Shipping Address</h2>
        <p><?php echo nl2br(htmlspecialchars($customer['ShippingAddress'])); ?></p>
    </div>

    <div class="card">
        <h2>Order History</h2>
        <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Tracking</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loop through orders and display them -->
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['OrderID']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                            <td>
                                <?php
                                $status_class = '';
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
                                    <?php echo htmlspecialchars($order['Status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($order['item_count']); ?></td>
                            <td>$<?php echo number_format($order['OrderTotal'], 2); ?></td>
                            <td>
                                <!-- Display tracking number if available -->
                                <?php if ($order['TrackingNum']): ?>
                                    <?php echo htmlspecialchars($order['TrackingNum']); ?>
                                    <br>
                                    <small>Shipped: <?php echo date('M d, Y', strtotime($order['DateShipped'])); ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>" class="button">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found for this customer.</p>
        <?php endif; ?>
    </div>

    <div class="actions">
        <a href="customers.php" class="button">Back to Customers</a>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.info-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    min-width: 0; /* Allows text to wrap */
}

.info-item h3 {
    margin: 0;
    color: #666;
    font-size: 1rem;
}

.info-item p {
    margin: 10px 0 0;
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
    max-width: 100%;
}

.alert {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.alert.error {
    background: #f2dede;
    color: #a94442;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

tr:hover {
    background-color: #f5f5f5;
}

.button {
    padding: 8px 16px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.button:hover {
    opacity: 0.9;
}

.actions {
    margin-top: 20px;
    text-align: center;
}
</style>

<?php require_once '../includes/footer.php'; ?> 