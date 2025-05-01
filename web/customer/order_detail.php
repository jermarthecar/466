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
    header("Location: orders.php");
    exit();
}

// Get order details
$order_id = $_GET['id'];
$stmt = $pdo->prepare("
    SELECT o.*, s.TrackingNum, s.DateShipped, s.Notes 
    FROM `Order` o 
    LEFT JOIN Shipment s ON o.OrderID = s.OrderID 
    WHERE o.OrderID = ? AND o.CustomerID = ?
");
// Check if the order belongs to the logged-in customer
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch();

// If the order does not exist or does not belong to the customer, redirect to orders page
if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get message history
$stmt = $pdo->prepare("
    SELECT m.*, e.Name as EmployeeName
    FROM Message m
    JOIN Employee e ON m.EmployeeID = e.EmployeeID
    WHERE m.OrderID = ?
    ORDER BY m.SentAt ASC
");
$stmt->execute([$order_id]);
$messages = $stmt->fetchAll();

// Get the last employee who messaged or a default employee
$stmt = $pdo->prepare("
    SELECT m.EmployeeID 
    FROM Message m 
    WHERE m.OrderID = ? AND m.SentBy = 'Employee' 
    ORDER BY m.SentAt DESC 
    LIMIT 1
");
$stmt->execute([$order_id]);
$last_employee = $stmt->fetch();

if (!$last_employee) {
    // If no employee has messaged yet, get the first available employee
    $stmt = $pdo->prepare("
        SELECT EmployeeID 
        FROM Employee 
        WHERE AccessLevel = 'Employee' 
        LIMIT 1
    ");
    $stmt->execute();
    $last_employee = $stmt->fetch();
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message_text = trim($_POST['message']);
    if (!empty($message_text)) {
        $stmt = $pdo->prepare("
            INSERT INTO Message (OrderID, EmployeeID, CustomerID, MessageText, SentBy)
            VALUES (?, ?, ?, ?, 'Customer')
        ");
        $stmt->execute([
            $order_id,
            $last_employee['EmployeeID'],
            $_SESSION['customer_id'],
            $message_text
        ]);
        header("Location: order_detail.php?id=" . $order_id);
        exit();
    }
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.Name 
    FROM OrderItem oi 
    JOIN Product p ON oi.ProductID = p.ProductID 
    WHERE oi.OrderID = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<h2>Order #<?php echo $order['OrderID']; ?></h2>

<p><strong>Date:</strong> <?php echo $order['OrderDate']; ?></p>
<p><strong>Status:</strong> <?php echo $order['Status']; ?></p>
<p><strong>Shipping Address:</strong> <?php echo nl2br(htmlspecialchars($order['ShippingAddress'])); ?></p>
<p><strong>Billing Address:</strong> <?php echo nl2br(htmlspecialchars($order['BillingAddress'])); ?></p>
<p><strong>Payment Method:</strong> <?php echo $order['PaymentMethod']; ?></p>

<!--- Display tracking information if the order is shipped -->
<?php if ($order['Status'] === 'Shipped'): ?>
    <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['TrackingNum']); ?></p>
    <p><strong>Date Shipped:</strong> <?php echo $order['DateShipped']; ?></p>
<?php endif; ?>

<div style="margin-top: 30px;">
    <h3>Order Items</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Product</th>
                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Quantity</th>
                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Price</th>
                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <!-- Loop through order items and display them -->
            <?php foreach ($order_items as $item): ?>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($item['Name']); ?></td>
                    <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $item['Quantity']; ?></td>
                    <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">$<?php echo number_format($item['PriceAtOrderTime'], 2); ?></td>
                    <td style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">$<?php echo number_format($item['Subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; padding: 8px; border-top: 2px solid #ddd;"><strong>Total:</strong></td>
                <td style="text-align: right; padding: 8px; border-top: 2px solid #ddd;"><strong>$<?php echo number_format($order['OrderTotal'], 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Message History -->
<div style="margin-top: 30px;">
    <h3>Contact Store</h3>
    <form method="post" style="margin-bottom: 20px;">
        <div style="margin-bottom: 10px;">
            <label for="message">Message:</label>
            <textarea id="message" name="message" required style="width: 100%; padding: 8px; min-height: 100px;"></textarea>
        </div>
        <button type="submit" class="button">Send Message</button>
    </form>

    <!-- If there are messages, display them -->
    <?php if (!empty($messages)): ?>
        <h4>Message History</h4>
        <div id="message-history" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
            <?php foreach ($messages as $message): ?>
                <div class="message" style="margin-bottom: 15px; padding: 10px; background: <?php echo $message['SentBy'] === 'Employee' ? '#e3f2fd' : '#f5f5f5'; ?>; border-radius: 4px;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php echo htmlspecialchars($message['SentBy'] === 'Employee' ? $message['EmployeeName'] : 'You'); ?></strong>
                        <small style="color: #666;">(<?php echo date('M j, Y g:i A', strtotime($message['SentAt'])); ?>)</small>
                    </p>
                    <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($message['MessageText'])); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


<script>
// Function to fetch and update messages
function fetchMessages() {
    fetch('../get_messages.php?order_id=<?php echo $order_id; ?>')
        .then(response => response.text())
        .then(html => {
            const messageHistory = document.getElementById('message-history');
            if (messageHistory) {
                messageHistory.innerHTML = html;
                // Scroll to bottom
                messageHistory.scrollTop = messageHistory.scrollHeight;
            }
        })
        .catch(error => console.error('Error fetching messages:', error));
}

// Fetch messages every 5 seconds
setInterval(fetchMessages, 5000);

// Also fetch messages when the page loads
document.addEventListener('DOMContentLoaded', fetchMessages);
</script>

<div style="margin-top: 20px;">
    <a href="orders.php" class="button">Back to Orders</a>
</div>

<?php require_once '../includes/footer.php'; ?>