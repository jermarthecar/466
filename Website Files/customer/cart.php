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

// Get customer name for welcome message
$customer_name = $_SESSION['customer_name'];

$error = '';
$success = '';

try {
    // Get or create cart for customer
    $stmt = $pdo->prepare("SELECT CartID FROM Cart WHERE CustomerID = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $cart = $stmt->fetch();

    if (!$cart) {
        // Create new cart if it doesn't exist
        $stmt = $pdo->prepare("INSERT INTO Cart (CustomerID) VALUES (?)");
        $stmt->execute([$_SESSION['customer_id']]);
        $cart_id = $pdo->lastInsertId();
    } else {
        $cart_id = $cart['CartID'];
    }

    // Handle cart updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_cart'])) {
            foreach ($_POST['quantity'] as $product_id => $quantity) {
                if ($quantity > 0) {
                    // Check if item exists in cart
                    $stmt = $pdo->prepare("SELECT Quantity FROM CartItem WHERE CartID = ? AND ProductID = ?");
                    $stmt->execute([$cart_id, $product_id]);
                    $existing_item = $stmt->fetch();
                    
                    if ($existing_item) {
                        // Update existing item quantity
                        $stmt = $pdo->prepare("UPDATE CartItem SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
                        $stmt->execute([$quantity, $cart_id, $product_id]);
                    } else {
                        // Add new item
                        $stmt = $pdo->prepare("INSERT INTO CartItem (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
                        $stmt->execute([$cart_id, $product_id, $quantity]);
                    }
                } else {
                    // Remove item from cart
                    $stmt = $pdo->prepare("DELETE FROM CartItem WHERE CartID = ? AND ProductID = ?");
                    $stmt->execute([$cart_id, $product_id]);
                }
            }
            $success = "Cart updated successfully!";
            // Redirect to refresh the page and update the header
            header("Location: cart.php");
            exit();
        } elseif (isset($_POST['remove_item'])) {
            $product_id = $_POST['product_id'];
            $stmt = $pdo->prepare("DELETE FROM CartItem WHERE CartID = ? AND ProductID = ?");
            $stmt->execute([$cart_id, $product_id]);
            $success = "Item removed from cart!";
            // Redirect to refresh the page and update the header
            header("Location: cart.php");
            exit();
        }
    }

    // Get cart items details
    $stmt = $pdo->prepare("
        SELECT ci.CartItemID, p.ProductID, p.Name, p.Price, p.StockQuantity, ci.Quantity
        FROM CartItem ci
        JOIN Product p ON ci.ProductID = p.ProductID
        WHERE ci.CartID = ?
        ORDER BY p.Name
    ");
    $stmt->execute([$cart_id]);
    $cart_items = $stmt->fetchAll();

    $total = 0;
    foreach ($cart_items as $i => $item) {
        $cart_items[$i]['subtotal'] = $item['Price'] * $item['Quantity'];
        $total += $cart_items[$i]['subtotal'];
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Cart Error: " . $e->getMessage());
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Shopping Cart</h1>

    <?php if ($error): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color: green; padding: 10px; margin-bottom: 20px; border: 1px solid green; border-radius: 4px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 20px;">
            <p>Your cart is empty.</p>
            <a href="index.php" class="button">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form method="post">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; text-align: left;">Product</th>
                        <th style="padding: 10px; text-align: left;">Price</th>
                        <th style="padding: 10px; text-align: left;">Quantity</th>
                        <th style="padding: 10px; text-align: left;">Subtotal</th>
                        <th style="padding: 10px; text-align: left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;">
                                <a href="product_detail.php?id=<?php echo $item['ProductID']; ?>">
                                    <?php echo htmlspecialchars($item['Name']); ?>
                                </a>
                            </td>
                            <td style="padding: 10px;">$<?php echo number_format($item['Price'], 2); ?></td>
                            <td style="padding: 10px;">
                                <input type="number" name="quantity[<?php echo $item['ProductID']; ?>]" 
                                       value="<?php echo $item['Quantity']; ?>" 
                                       min="1" max="<?php echo $item['StockQuantity']; ?>"
                                       style="width: 60px;">
                            </td>
                            <td style="padding: 10px;">$<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td style="padding: 10px;">
                                <button type="submit" name="remove_item" class="button" 
                                        onclick="return confirm('Are you sure you want to remove this item?')">
                                    Remove
                                </button>
                                <input type="hidden" name="product_id" value="<?php echo $item['ProductID']; ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; padding: 10px;"><strong>Total:</strong></td>
                        <td style="padding: 10px;">$<?php echo number_format($total, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div style="text-align: center;">
                <button type="submit" name="update_cart" class="button">Update Cart</button>
                <a href="checkout.php" class="button">Proceed to Checkout</a>
                <a href="index.php" class="button">Continue Shopping</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>