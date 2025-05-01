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

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once '../includes/header.php';

try {
    // Get all unique categories for the filter dropdown
    $stmt = $pdo->prepare("
        SELECT DISTINCT Category 
        FROM Product 
        WHERE Category IS NOT NULL 
        ORDER BY Category
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get selected category from URL parameter
    $selected_category = $_GET['category'] ?? '';

    // Get all products with their order counts, filtered by category if selected
    $query = "
        SELECT p.*,
               COUNT(DISTINCT oi.OrderID) as TotalOrders
        FROM Product p
        LEFT JOIN OrderItem oi ON p.ProductID = oi.ProductID
    ";
    
    if ($selected_category) {
        $query .= " WHERE p.Category = :category";
    }
    
    $query .= " GROUP BY p.ProductID ORDER BY p.Name";
    
    $stmt = $pdo->prepare($query);
    if ($selected_category) {
        $stmt->bindParam(':category', $selected_category);
    }
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Products</h1>
        <a href="add_product.php" class="button">Add New Product</a>
    </div>

    <!-- Add category filter -->
    <div style="margin-bottom: 20px;">
        <form method="get" style="display: flex; gap: 10px; align-items: center;">
            <label for="category">Filter by Category:</label>
            <select id="category" name="category" style="padding: 8px; border-radius: 4px; min-width: 200px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>" 
                            <?php echo $selected_category === $category ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button">Filter</button>
            <?php if ($selected_category): ?>
                <a href="products.php" class="button" style="background: #6c757d;">Clear Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="background-color: #dff0d8; color: #3c763d; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div style="background-color: #f2dede; color: #a94442; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div style="background-color: #f2dede; color: #a94442; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

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
                    <td style="padding: 10px;"><?php echo htmlspecialchars($product['Name']); ?></td>
                    <td style="padding: 10px;"><?php echo htmlspecialchars($product['Category']); ?></td>
                    <td style="padding: 10px;">$<?php echo number_format($product['Price'], 2); ?></td>
                    <td style="padding: 10px;">
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
                           class="button" style="background: #dc3545;"
                           onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?> 