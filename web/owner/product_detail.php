<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php'; // This implicitly starts session if not started

// Check if user is logged in as owner using the function
if (!isOwnerLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once '../includes/header.php';

// Get owner name for welcome message
$owner_name = $_SESSION['employee_name'];

// Get product ID from URL
$product_id = $_GET['id'] ?? 0;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

$product = null; // Initialize
$sales_history = []; // Initialize
$error = ''; // Initialize

try {
    // Get product details - select p.Category, remove JOIN
    $stmt = $pdo->prepare("
        SELECT p.*, p.Category as CategoryName -- Get category name from Product table
        FROM Product p
        -- No JOIN needed here
        WHERE p.ProductID = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: products.php');
        exit();
    }

    // Get product sales history
    $stmt = $pdo->prepare("
        SELECT oi.OrderID, oi.Quantity, oi.PriceAtOrderTime, o.OrderDate, c.Name as CustomerName
        FROM OrderItem oi
        JOIN `Order` o ON oi.OrderID = o.OrderID
        JOIN Customer c ON o.CustomerID = c.CustomerID
        WHERE oi.ProductID = ?
        ORDER BY o.OrderDate DESC
        LIMIT 10
    ");
    $stmt->execute([$product_id]);
    $sales_history = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error loading product details: " . $e->getMessage();
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Product Details</h1>

    <?php if ($error): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif ($product): // Only display if product loaded successfully ?>
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1; background: #f9f9f9; padding: 20px; border-radius: 5px;">
                <h2><?php echo htmlspecialchars($product['Name']); ?></h2>
                <p><strong>Product ID:</strong> <?php echo htmlspecialchars($product['ProductID']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['CategoryName']); // Use alias from query ?></p>
                <p><strong>Price:</strong> $<?php echo number_format($product['Price'], 2); ?></p>
                <p><strong>Stock Quantity:</strong>
                    <?php if ($product['StockQuantity'] == 0): ?>
                        <span style="color: red;">Out of Stock</span>
                    <?php elseif ($product['StockQuantity'] < 5): ?>
                        <span style="color: orange;">Low Stock (<?php echo $product['StockQuantity']; ?>)</span>
                    <?php else: ?>
                        <span style="color: green;"><?php echo $product['StockQuantity']; ?></span>
                    <?php endif; ?>
                </p>
                <p><strong>Description:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>

                <div style="margin-top: 20px;">
                    <a href="edit_product.php?id=<?php echo $product['ProductID']; ?>" class="button">Edit Product</a>
                    <a href="products.php" class="button">Back to Products</a>
                </div>
            </div>

            <div style="flex: 1; background: #f9f9f9; padding: 20px; border-radius: 5px;">
                <h2>Recent Sales History</h2>
                <?php if (count($sales_history) > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f5f5f5;">
                                <th style="padding: 10px; text-align: left;">Date</th>
                                <th style="padding: 10px; text-align: left;">Customer</th>
                                <th style="padding: 10px; text-align: left;">Quantity</th>
                                <th style="padding: 10px; text-align: left;">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_history as $sale): ?>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 10px;"><?php echo htmlspecialchars(date('Y-m-d', strtotime($sale['OrderDate']))); ?></td>
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($sale['CustomerName']); ?></td>
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($sale['Quantity']); ?></td>
                                    <td style="padding: 10px;">$<?php echo number_format($sale['PriceAtOrderTime'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No sales history found for this product.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>