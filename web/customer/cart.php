<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and check authentication
session_start();
require_once '../includes/auth.php';
require_once '../db_connect.php';

// Check if user is logged in as customer
if (!isCustomerLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Include header after authentication check and session start
require_once '../includes/header.php';

// Get customer name for welcome message
$customer_name = $_SESSION['customer_name'];

$error = '';
$success = '';

// Display and clear session messages
if (isset($_SESSION['cart_success_message'])) {
    $success = $_SESSION['cart_success_message'];
    unset($_SESSION['cart_success_message']); // Clear message after displaying
}
if (isset($_SESSION['cart_error_message'])) {
    $error = $_SESSION['cart_error_message'];
    unset($_SESSION['cart_error_message']); // Clear message after displaying
}


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
    } 
    else {
        $cart_id = $cart['CartID'];
    }

    // Handle cart actions (Add, Update, Remove) via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle ADD action from products.php
        if (isset($_POST['action']) && $_POST['action'] === 'add' && isset($_POST['product_id'])) {
            $product_id = (int)$_POST['product_id'];
            $quantity = 1; // Default quantity when adding from products page

            if ($cart_id && $product_id > 0) {
                // Check if product already in cart
                $stmt = $pdo->prepare("SELECT Quantity FROM CartItem WHERE CartID = ? AND ProductID = ?");
                $stmt->execute([$cart_id, $product_id]);
                $existing_item = $stmt->fetch();

                if ($existing_item) {
                    // Update quantity by adding to existing quantity
                    $new_quantity = $existing_item['Quantity'] + $quantity;
                    // Check stock before increasing quantity
                    $stmt_check_stock = $pdo->prepare("SELECT StockQuantity FROM Product WHERE ProductID = ?");
                    $stmt_check_stock->execute([$product_id]);
                    $stock = $stmt_check_stock->fetchColumn();
                    if ($stock !== false && $new_quantity <= $stock) {
                        $stmt = $pdo->prepare("UPDATE CartItem SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
                        $stmt->execute([$new_quantity, $cart_id, $product_id]);
                         $_SESSION['cart_success_message'] = "Item quantity updated in cart!";
                    } 
                    else {
                         $_SESSION['cart_error_message'] = "Not enough stock to add more of this item.";
                    }
                } 
                else {
                     // Check stock before adding new item
                    $stmt_check_stock = $pdo->prepare("SELECT StockQuantity FROM Product WHERE ProductID = ?");
                    $stmt_check_stock->execute([$product_id]);
                    $stock = $stmt_check_stock->fetchColumn();
                    if ($stock !== false && $quantity <= $stock) {
                        // Add new item
                        $stmt = $pdo->prepare("INSERT INTO CartItem (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
                        $stmt->execute([$cart_id, $product_id, $quantity]);
                        $_SESSION['cart_success_message'] = "Item added to cart!";
                    } 
                    else {
                        $_SESSION['cart_error_message'] = "Item is out of stock.";
                    }
                }
            } 
            else {
                $_SESSION['cart_error_message'] = "Could not add item to cart.";
            }
            // Redirect to refresh the cart page and show messages
            header("Location: cart.php");
            exit();

        }
        // Handle update action from cart.php form
        elseif (isset($_POST['update_cart'])) {
            $updated_count = 0;
            $removed_count = 0;
            foreach ($_POST['quantity'] as $product_id => $quantity) {
                $product_id = (int)$product_id; // Ensure integer
                $quantity = (int)$quantity;   // Ensure integer

                if ($product_id > 0) {
                    if ($quantity > 0) {
                        // Check stock before updating
                        $stmt_check_stock = $pdo->prepare("SELECT StockQuantity FROM Product WHERE ProductID = ?");
                        $stmt_check_stock->execute([$product_id]);
                        $stock = $stmt_check_stock->fetchColumn();

                        if ($stock !== false && $quantity <= $stock) {
                            // Directly update the quantity for this product in the cart
                            $stmt = $pdo->prepare("UPDATE CartItem SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
                            $stmt->execute([$quantity, $cart_id, $product_id]);
                            $updated_count += $stmt->rowCount(); // Count successful updates
                        } 
                        else {
                            // Handle insufficient stock
                             $_SESSION['cart_error_message'] = "Not enough stock for one of the items. Adjusted quantity where possible.";
                             // Update to max stock if available stock is positive
                            if ($stock > 0) {
                                $stmt = $pdo->prepare("UPDATE CartItem SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
                                $stmt->execute([$stock, $cart_id, $product_id]);
                                $updated_count += $stmt->rowCount();
                             } 
                             else { // If stock is 0, remove item
                                $stmt = $pdo->prepare("DELETE FROM CartItem WHERE CartID = ? AND ProductID = ?");
                                $stmt->execute([$cart_id, $product_id]);
                                $removed_count += $stmt->rowCount();
                            }
                        }
                    } 
                    else {
                        // Quantity is 0 or less, remove the item
                        $stmt = $pdo->prepare("DELETE FROM CartItem WHERE CartID = ? AND ProductID = ?");
                        $stmt->execute([$cart_id, $product_id]);
                        $removed_count += $stmt->rowCount(); // Count successful deletes
                    }
                }
            }
            // Set a success message based on actions taken
            if ($updated_count > 0 || $removed_count > 0) {
                // Avoid overwriting potential error message about stock
                if (!isset($_SESSION['cart_error_message'])) {
                     $_SESSION['cart_success_message'] = "Cart updated successfully!";
                }
            }

            // Redirect to refresh the page and update the header
            header("Location: cart.php");
            exit();
        }
        // Handle remove action from cart.php form
        elseif (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
            $product_id = (int)$_POST['product_id'];
            if ($cart_id && $product_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM CartItem WHERE CartID = ? AND ProductID = ?");
                $stmt->execute([$cart_id, $product_id]);
                 $_SESSION['cart_success_message'] = "Item removed from cart!";
            } else {
                 $_SESSION['cart_error_message'] = "Could not remove item.";
            }
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
        // Ensure quantity in cart doesn't exceed current stock
        if ($item['Quantity'] > $item['StockQuantity']) {
            $error = "Note: Quantity for some items exceeds available stock. Please update your cart.";
            // Automatically adjust quantity to available stock in the cart and database
            $stmt = $pdo->prepare("UPDATE CartItem SET Quantity = ? WHERE CartItemID = ?");
            $stmt->execute([$item['StockQuantity'], $item['CartItemID']]);
            $cart_items[$i]['Quantity'] = $item['StockQuantity']; // Update local cart item quantity
            $cart_items[$i]['subtotal'] = $item['StockQuantity'] * $item['Price']; // Update subtotal
            $total -= $item['subtotal']; // Remove old subtotal
            $total += $cart_items[$i]['subtotal']; // Add new subtotal
            $error = "Quantity for item " . htmlspecialchars($item['Name']) . " adjusted to available stock.";
            $_SESSION['cart_error_message'] = $error; // Set session error message
        }
    }
} 
catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Cart Error: " . $e->getMessage());
    // Clear cart items if DB error occurs during fetch
    $cart_items = [];
    $total = 0;
}
?>

<!-- HTML and CSS for the cart page -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Shopping Cart</h1>

    <?php if ($error): ?>
        <div style="color: red; background-color: #fdd; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color: green; background-color: #dfd; padding: 10px; margin-bottom: 20px; border: 1px solid green; border-radius: 4px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 20px;">
            <p>Your cart is empty.</p>
            <a href="index.php" class="button">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form method="post" action="cart.php">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; text-align: left;">Product</th>
                        <th style="padding: 10px; text-align: right;">Price</th>
                        <th style="padding: 10px; text-align: center;">Quantity (Max: Stock)</th>
                        <th style="padding: 10px; text-align: right;">Subtotal</th>
                        <th style="padding: 10px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;">
                                <a href="product_detail.php?id=<?php echo $item['ProductID']; ?>">
                                    <?php echo htmlspecialchars($item['Name']); ?>
                                </a>
                                <?php if ($item['Quantity'] > $item['StockQuantity']): ?>
                                    <br><small style="color: orange;">(Only <?php echo $item['StockQuantity']; ?> available)</small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; text-align: right;">$<?php echo number_format($item['Price'], 2); ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <input type="number" name="quantity[<?php echo $item['ProductID']; ?>]"
                                       value="<?php echo $item['Quantity']; ?>"
                                       min="0"
                                       max="<?php echo $item['StockQuantity']; ?>"
                                       style="width: 60px; text-align: center;"
                                       aria-label="Quantity for <?php echo htmlspecialchars($item['Name']); ?>">
                                <small>(<?php echo $item['StockQuantity']; ?>)</small>
                            </td>
                            <td style="padding: 10px; text-align: right;">$<?php echo number_format($item['subtotal'], 2); ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <form method="post" action="cart.php" style="display: inline;">
                                     <input type="hidden" name="product_id" value="<?php echo $item['ProductID']; ?>">
                                     <button type="submit" name="remove_item" class="button"
                                             style="background-color: #d9534f;"
                                             onclick="return confirm('Are you sure you want to remove this item?')">
                                         Remove
                                     </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; padding: 10px; font-weight: bold;">Total:</td>
                        <td style="padding: 10px; text-align: right; font-weight: bold;">$<?php echo number_format($total, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <div style="text-align: center; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <a href="index.php" class="button" style="background-color: #5bc0de;">Continue Shopping</a>
                 <button type="submit" name="update_cart" class="button">Update Cart Quantities</button>
                <a href="checkout.php" class="button">Proceed to Checkout</a>
            </div>
        </form> <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>