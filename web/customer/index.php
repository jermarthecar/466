<?php
// Enable error reporting
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
    // Get featured products (products with highest stock)
    $stmt = $pdo->prepare("
        SELECT * FROM Product 
        WHERE StockQuantity > 0 
        ORDER BY StockQuantity DESC 
        LIMIT 4
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();

    // Get new arrivals (most recently added products)
    $stmt = $pdo->prepare("
        SELECT * FROM Product 
        WHERE StockQuantity > 0 
        ORDER BY ProductID DESC 
        LIMIT 4
    ");
    $stmt->execute();
    $new_arrivals = $stmt->fetchAll();

    // Get low stock items (potential deals)
    $stmt = $pdo->prepare("
        SELECT * FROM Product 
        WHERE StockQuantity > 0 AND StockQuantity < 5 
        ORDER BY StockQuantity ASC 
        LIMIT 4
    ");
    $stmt->execute();
    $deals = $stmt->fetchAll();
} 
catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

<!-- Main Content -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <?php if (isset($error)): ?>
        <div class="error-message" style="color: red; padding: 10px; margin: 10px 0; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-bottom: 40px;">
        <h1>Welcome to Music Store</h1>
        <p style="font-size: 18px;">Discover our collection of vinyl records and music memorabilia</p>
    </div>

    <!-- Featured Products -->
    <div style="margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px;">Featured Products</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach ($featured_products as $product): ?>
                <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
                    <h3><?php echo htmlspecialchars($product['Name']); ?></h3>
                    <p style="color: #666;"><?php echo htmlspecialchars($product['Category']); ?></p>
                    <p style="font-size: 20px; font-weight: bold; color: #4CAF50;">$<?php echo number_format($product['Price'], 2); ?></p>
                    <p>Stock: <?php echo $product['StockQuantity']; ?></p>
                    <a href="product_detail.php?id=<?php echo $product['ProductID']; ?>" class="button">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- New Arrivals -->
    <div style="margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px;">New Arrivals</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <?php foreach ($new_arrivals as $product): ?>
                <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
                    <h3><?php echo htmlspecialchars($product['Name']); ?></h3>
                    <p style="color: #666;"><?php echo htmlspecialchars($product['Category']); ?></p>
                    <p style="font-size: 20px; font-weight: bold; color: #4CAF50;">$<?php echo number_format($product['Price'], 2); ?></p>
                    <p>Stock: <?php echo $product['StockQuantity']; ?></p>
                    <a href="product_detail.php?id=<?php echo $product['ProductID']; ?>" class="button">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Special Deals -->
    <?php if (count($deals) > 0): ?>
        <div style="margin-bottom: 40px;">
            <h2 style="margin-bottom: 20px;">Limited Stock Deals</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <?php foreach ($deals as $product): ?>
                    <div style="background: #fff3cd; padding: 20px; border-radius: 5px; text-align: center;">
                        <h3><?php echo htmlspecialchars($product['Name']); ?></h3>
                        <p style="color: #666;"><?php echo htmlspecialchars($product['Category']); ?></p>
                        <p style="font-size: 20px; font-weight: bold; color: #dc3545;">$<?php echo number_format($product['Price'], 2); ?></p>
                        <p style="color: #dc3545;">Only <?php echo $product['StockQuantity']; ?> left!</p>
                        <a href="product_detail.php?id=<?php echo $product['ProductID']; ?>" class="button">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 40px;">
        <a href="products.php" class="button" style="padding: 15px 30px; font-size: 18px;">View All Products</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 