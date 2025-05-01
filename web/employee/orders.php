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

// Establish database connection
require_once '../db_connect.php';

// Include header which might need database access
require_once '../includes/header.php';

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];

$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';

$query = "
    SELECT o.OrderID, o.OrderDate, o.Status, o.OrderTotal, c.Name AS CustomerName 
    FROM `Order` o 
    JOIN Customer c ON o.CustomerID = c.CustomerID 
    WHERE 1=1
";
$params = [];

// Filter by status if provided
if ($status_filter) {
    $query .= " AND o.Status = ?";
    $params[] = $status_filter;
}

// Search by order ID or customer name if provided
if ($search_query) {
    $query .= " AND (o.OrderID = ? OR c.Name LIKE ?)";
    $params[] = $search_query;
    $params[] = "%$search_query%";
}

$query .= " ORDER BY o.OrderDate DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!-- HTML and CSS for the page -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Order Management</h1>
    
    <h2>Orders</h2>

    <form method="get" style="margin-bottom: 20px;">
        <div>
            <label for="status">Filter by Status:</label>
            <select id="status" name="status">
                <option value="">All</option>
                <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="Shipped" <?php echo $status_filter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label for="search">Search:</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="button">Filter</button>
            <a href="orders.php" class="button">Reset</a>
        </div>
    </form>

    <!-- Display orders in a table -->
    <?php if (count($orders) > 0): ?>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo $order['OrderID']; ?></td>
                <td><?php echo $order['OrderDate']; ?></td>
                <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                <td><?php echo $order['Status']; ?></td>
                <td>$<?php echo number_format($order['OrderTotal'], 2); ?></td>
                <td>
                    <a href="order_detail.php?id=<?php echo $order['OrderID']; ?>">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>