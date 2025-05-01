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

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$customers = [];

try {
    // Get search term if provided
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Base query
    $query = "
        SELECT 
            c.CustomerID,
            c.Name,
            c.Email,
            COUNT(DISTINCT o.OrderID) as total_orders,
            COALESCE(SUM(o.OrderTotal), 0) as total_spent
        FROM Customer c
        LEFT JOIN `Order` o ON c.CustomerID = o.CustomerID
    ";
    
    // Add search condition if provided
    if (!empty($search)) {
        $query .= " WHERE c.Name LIKE ? OR c.Email LIKE ?";
        $params = ["%$search%", "%$search%"];
    } 
    else {
        $params = [];
    }
    
    $query .= " GROUP BY c.CustomerID ORDER BY c.Name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} 
catch (PDOException $e) {
    $error_message = "Error fetching customers: " . $e->getMessage();
}

// Include header
require_once '../includes/header.php';
?>

<div class="container">
    <h1>Customer Management</h1>

    <?php if (isset($error_message)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="card">
        <form method="GET" class="form">
            <div class="form-group">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="button">Search</button>
            </div>
        </form>
    </div>

    <!-- Customer List -->
    <div class="card">
        <h2>Customer List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Total Orders</th>
                    <th>Total Spent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loop through customers and display them -->
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($customer['CustomerID']); ?></td>
                        <td><?php echo htmlspecialchars($customer['Name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['total_orders']); ?></td>
                        <td>$<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></td>
                        <td>
                            <a href="customer_details.php?id=<?php echo $customer['CustomerID']; ?>" class="button">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.form {
    display: flex;
    gap: 10px;
}

.form-group {
    display: flex;
    gap: 10px;
    flex: 1;
}

.form-group input {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
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
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

tr:hover {
    background-color: #f5f5f5;
}
</style>

<?php require_once '../includes/footer.php'; ?> 