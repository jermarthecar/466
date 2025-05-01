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
        SELECT p.*, p.Category as CategoryName, -- Get category name from Product table
               (SELECT COUNT(*) FROM OrderItem oi WHERE oi.ProductID = p.ProductID) as TotalOrders
        FROM Product p
        -- No JOIN needed here
    ";

    // Add filter conditions
    $where_clauses = [];
    if ($filter === 'in_stock') {
        $where_clauses[] = "p.StockQuantity > 0";
    } 
    elseif ($filter === 'out_of_stock') {
        $where_clauses[] = "p.StockQuantity = 0";
    } 
    elseif ($filter === 'low_stock') {
        $where_clauses[] = "p.StockQuantity < 10 AND p.StockQuantity > 0";
    }

    // Add WHERE clause if there are any conditions
    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $query .= " ORDER BY p.Name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
} 
catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!-- HTML and PHP code to display the product management page -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Product Management</h1>

    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <a href="?filter=all" class="button">All Products</a>
            <a href="?filter=in_stock" class="button">In Stock</a>
            <a href="?filter=out_of_stock" class="button">Out of Stock</a>
            <a href="?filter=low_stock" class="button">Low Stock</a>
        </div>
        <div>
            <a href="add_product.php" class="button">Add New Product</a>
        </div>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; text-align: left;">ID</th>
                <th style="padding: 10px; text-align: left;">Name</th>
                <th style="padding: 10px; text-align: left;">Category</th>
                <th style="padding: 10px; text-align: left;">Price</th>
                <th style="padding: 10px; text-align: left;">Stock</th>
                <th style="padding: 10px; text-align: left;">Orders</th>
                <th style="padding: 10px; text-align: left;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Loop through products and display them -->
            <?php foreach ($products as $product): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;"><?php echo $product['ProductID']; ?></td>
                    <td style="padding: 10px;">
                        <a href="product_detail.php?id=<?php echo $product['ProductID']; ?>">
                            <?php echo htmlspecialchars($product['Name']); ?>
                        </a>
                    </td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($product['CategoryName']); // Use the alias from the query ?></td>
                    <td style="padding: 10px;">$<?php echo number_format($product['Price'], 2); ?></td>
                    <td style="padding: 10px;">
                        <!-- Display stock status with color coding -->
                        <?php if ($product['StockQuantity'] == 0): ?>
                            <span style="color: red;">Out of Stock</span>
                        <?php elseif ($product['StockQuantity'] < 10): ?>
                            <span style="color: orange;"><?php echo $product['StockQuantity']; ?></span>
                        <?php else: ?>
                            <?php echo $product['StockQuantity']; ?>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px;"><?php echo $product['TotalOrders']; ?></td>
                    <td style="padding: 10px;">
                        <a href="edit_product.php?id=<?php echo $product['ProductID']; ?>" class="button">Edit</a>
                        <a href="delete_product.php?id=<?php echo $product['ProductID']; ?>"
                           class="button"
                           onclick="return confirm('Are you sure you want to delete this product?')">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>