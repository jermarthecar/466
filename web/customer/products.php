<?php
// Enable rror reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and check authentication first
session_start();
require_once '../includes/auth.php';
require_once '../db_connect.php';

// Check if user is logged in as customer
if (!isCustomerLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Include header after authentication check
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

    // Get all available products, filtered by category if selected
    $query = "
        SELECT * FROM Product 
        WHERE StockQuantity > 0
    ";
    
    if ($selected_category) {
        $query .= " AND Category = :category";
    }
    
    $query .= " ORDER BY Name";
    
    $stmt = $pdo->prepare($query);
    if ($selected_category) {
        $stmt->bindParam(':category', $selected_category);
    }
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 20px;">Our Products</h1>

    <!-- Add category filter -->
    <div style="margin-bottom: 30px;">
        <form method="get" style="display: flex; gap: 10px; align-items: center; justify-content: center;">
            <label for="category">Filter by Genre:</label>
            <select id="category" name="category" style="padding: 8px; border-radius: 4px; min-width: 200px;">
                <option value="">All Genres</option>
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

    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Display products in a grid layout -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        <?php foreach ($products as $product): ?>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; text-align: center;">
                <h3><?php echo htmlspecialchars($product['Name']); ?></h3>
                <p style="color: #666;"><?php echo htmlspecialchars($product['Category']); ?></p>
                <p style="font-size: 1.2em; margin: 10px 0;">$<?php echo number_format($product['Price'], 2); ?></p>
                <p style="margin-bottom: 15px;">
                    <?php if ($product['StockQuantity'] < 10): ?>
                        <span style="color: orange;">Only <?php echo $product['StockQuantity']; ?> left!</span>
                    <?php else: ?>
                        In Stock
                    <?php endif; ?>
                </p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button onclick="window.location.href='product_detail.php?id=<?php echo $product['ProductID']; ?>'" class="button">View Details</button>
                    <form method="post" action="cart.php" style="margin: 0;">
                        <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="button">Add to Cart</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>