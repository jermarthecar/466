<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and check authentication
session_start();
require_once 'db_connect.php';

// Include header
require_once 'includes/header.php';

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
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>

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
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>