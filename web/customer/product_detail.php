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

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

// Get product details
$product_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM Product WHERE ProductID = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Check stock availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantity'])) {
    $quantity = (int)$_POST['quantity'];
    
    // Get or create customer's cart
    $stmt = $pdo->prepare("SELECT CartID FROM Cart WHERE CustomerID = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $cart = $stmt->fetch();
    
    if (!$cart) {
        // Create new cart if it doesn't exist
        $stmt = $pdo->prepare("INSERT INTO Cart (CustomerID) VALUES (?)");
        $stmt->execute([$_SESSION['customer_id']]);
        $cart_id = $pdo->lastInsertId();
    } 
    else {
        $cart_id = $cart['CartID'];
    }
    
    // Check if product already in cart
    $stmt = $pdo->prepare("SELECT * FROM CartItem WHERE CartID = ? AND ProductID = ?");
    $stmt->execute([$cart_id, $product_id]);
    $existing_item = $stmt->fetch();
    
    if ($existing_item) {
        // Update quantity by adding to existing quantity
        $new_quantity = $existing_item['Quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE CartItem SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
        $stmt->execute([$new_quantity, $cart_id, $product_id]);
    } 
    else {
        // Add new item
        $stmt = $pdo->prepare("INSERT INTO CartItem (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
        $stmt->execute([$cart_id, $product_id, $quantity]);
    }
    
    // Verify the cart contents after update
    $verify_stmt = $pdo->prepare("
        SELECT ci.CartItemID, ci.CartID, ci.ProductID, ci.Quantity, p.Name
        FROM CartItem ci
        JOIN Product p ON ci.ProductID = p.ProductID
        WHERE ci.CartID = ?
    ");

    // Verify the cart contents
    $verify_stmt->execute([$cart_id]);
    $verify_items = $verify_stmt->fetchAll();
    
    header("Location: cart.php");
    exit();
}
?>

<!-- Main Content -->
<h2><?php echo htmlspecialchars($product['Name']); ?></h2>
<p><strong>Price:</strong> $<?php echo number_format($product['Price'], 2); ?></p>
<p><strong>Category:</strong> <?php echo htmlspecialchars($product['Category']); ?></p>
<p><strong>Stock:</strong> <?php echo $product['StockQuantity']; ?></p>
<p><strong>Description:</strong> <?php echo htmlspecialchars($product['Description']); ?></p>

<form method="post">
    <label for="quantity">Quantity:</label>
    <input type="number" id="quantity" name="quantity" min="1" max="<?php echo $product['StockQuantity']; ?>" value="1" required>
    <button type="submit" class="button">Add to Cart</button>
</form>

<a href="products.php">Back to Products</a>

<?php require_once '../includes/footer.php'; ?>