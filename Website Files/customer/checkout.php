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
    // Get customer info
    $stmt = $pdo->prepare("SELECT * FROM Customer WHERE CustomerID = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch();

    if (!$customer) {
        throw new Exception("Customer not found");
    }

    // Get cart items
    $stmt = $pdo->prepare("
        SELECT p.ProductID, p.Name, p.Price, p.StockQuantity, ci.Quantity, (p.Price * ci.Quantity) AS Subtotal 
        FROM CartItem ci 
        JOIN Product p ON ci.ProductID = p.ProductID 
        JOIN Cart c ON ci.CartID = c.CartID 
        WHERE c.CustomerID = ?
    ");
    $stmt->execute([$_SESSION['customer_id']]);
    $cart_items = $stmt->fetchAll();

    if (count($cart_items) === 0) {
        header("Location: cart.php");
        exit();
    }

    // Check stock availability
    foreach ($cart_items as $item) {
        if ($item['Quantity'] > $item['StockQuantity']) {
            throw new Exception("Not enough stock for " . $item['Name']);
        }
    }

    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['Subtotal'];
    }

    // Handle checkout
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate inputs
        $required_fields = ['shipping_address', 'billing_address', 'payment_method'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields");
            }
        }

        if (!in_array($_POST['payment_method'], ['Credit Card', 'Debit Card'])) {
            throw new Exception("Invalid payment method");
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO `Order` (CustomerID, Status, ShippingAddress, BillingAddress, OrderTotal, PaymentMethod) 
                VALUES (?, 'Processing', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['customer_id'],
                $_POST['shipping_address'],
                $_POST['billing_address'],
                $total,
                $_POST['payment_method']
            ]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                // Add order item
                $stmt = $pdo->prepare("
                    INSERT INTO OrderItem (OrderID, ProductID, Quantity, PriceAtOrderTime) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['ProductID'],
                    $item['Quantity'],
                    $item['Price']
                ]);

                // Update stock
                $stmt = $pdo->prepare("
                    UPDATE Product 
                    SET StockQuantity = StockQuantity - ? 
                    WHERE ProductID = ?
                ");
                $stmt->execute([$item['Quantity'], $item['ProductID']]);
            }

            // Clear cart
            $stmt = $pdo->prepare("
                DELETE FROM CartItem 
                WHERE CartID IN (SELECT CartID FROM Cart WHERE CustomerID = ?)
            ");
            $stmt->execute([$_SESSION['customer_id']]);

            // Commit transaction
            $pdo->commit();

            // Redirect to order confirmation
            header("Location: order_confirmation.php?id=" . $order_id);
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            throw $e;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<h2>Checkout</h2>

<?php if (isset($error)): ?>
    <div class="error-message" style="color: red; padding: 10px; margin: 10px 0; border: 1px solid red; border-radius: 4px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form method="post">
    <div style="margin-bottom: 15px;">
        <label for="shipping_address">Shipping Address:</label>
        <textarea id="shipping_address" name="shipping_address" required style="width: 100%; padding: 8px;"><?php echo htmlspecialchars($customer['ShippingAddress']); ?></textarea>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="billing_address">Billing Address:</label>
        <textarea id="billing_address" name="billing_address" required style="width: 100%; padding: 8px;"><?php echo htmlspecialchars($customer['ShippingAddress']); ?></textarea>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label>Payment Method:</label><br>
        <label style="margin-right: 20px;">
            <input type="radio" name="payment_method" value="Credit Card" required> Credit Card
        </label>
        <label>
            <input type="radio" name="payment_method" value="Debit Card"> Debit Card
        </label>
    </div>

    <h3>Order Summary</h3>
    <table>
        <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
        </tr>
        <?php foreach ($cart_items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['Name']); ?></td>
                <td>$<?php echo number_format($item['Price'], 2); ?></td>
                <td><?php echo $item['Quantity']; ?></td>
                <td>$<?php echo number_format($item['Subtotal'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
            <td>$<?php echo number_format($total, 2); ?></td>
        </tr>
    </table>

    <button type="submit" class="button">Complete Purchase</button>
</form>

<?php require_once '../includes/footer.php'; ?>