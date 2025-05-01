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

// Establish database connection
require_once '../db_connect.php';

// Include header
require_once '../includes/header.php';

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

if (!$order_id) {
    header('Location: orders.php');
    exit();
}

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.Name as CustomerName,
            c.Email as CustomerEmail,
            c.ShippingAddress,
            s.TrackingNum,
            s.DateShipped,
            s.Notes as ShippingNotes,
            COUNT(oi.OrderItemID) as item_count
        FROM `Order` o
        JOIN Customer c ON o.CustomerID = c.CustomerID
        LEFT JOIN Shipment s ON o.OrderID = s.OrderID
        LEFT JOIN OrderItem oi ON o.OrderID = oi.OrderID
        WHERE o.OrderID = ?
        GROUP BY o.OrderID
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

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

    // Handle message submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
        $message_text = trim($_POST['message']);
        if (!empty($message_text)) {
            $stmt = $pdo->prepare("
                INSERT INTO Message (OrderID, EmployeeID, CustomerID, MessageText, SentBy)
                VALUES (?, ?, ?, ?, 'Employee')
            ");
            $stmt->execute([
                $order_id,
                $_SESSION['employee_id'],
                $order['CustomerID'],
                $message_text
            ]);
            header("Location: order_detail.php?id=" . $order_id);
            exit();
        }
    }

    // Handle order status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $pdo->beginTransaction();
        try {
            $new_status = '';
            $tracking_num = '';
            $notes = '';

            switch ($_POST['action']) {
                case 'ship':
                    $new_status = 'Shipped';
                    // Generate a tracking number (format: CARRIER + 9 digits)
                    $carriers = ['UPS', 'FEDEX', 'USPS', 'DHL'];
                    $carrier = $carriers[array_rand($carriers)];
                    $tracking_num = $carrier . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
                    
                    // Create shipment record
                    $stmt = $pdo->prepare("
                        INSERT INTO Shipment (OrderID, DateShipped, TrackingNum)
                        VALUES (?, NOW(), ?)
                    ");
                    $stmt->execute([$order_id, $tracking_num]);
                    break;

                case 'deliver':
                    $new_status = 'Delivered';
                    $notes = $_POST['notes'] ?? '';
                    // Update shipment notes if exists
                    if (!empty($notes)) {
                        $stmt = $pdo->prepare("
                            UPDATE Shipment 
                            SET Notes = ? 
                            WHERE OrderID = ?
                        ");
                        $stmt->execute([$notes, $order_id]);
                    }
                    break;

                case 'cancel':
                    $new_status = 'Cancelled';
                    // Get all items in the order with their quantities
                    $stmt = $pdo->prepare("
                        SELECT oi.ProductID, oi.Quantity 
                        FROM OrderItem oi 
                        WHERE oi.OrderID = ?
                    ");
                    $stmt->execute([$order_id]);
                    $items = $stmt->fetchAll();

                    // Restore stock quantities for each item
                    foreach ($items as $item) {
                        $stmt = $pdo->prepare("
                            UPDATE Product 
                            SET StockQuantity = StockQuantity + ? 
                            WHERE ProductID = ?
                        ");
                        $stmt->execute([$item['Quantity'], $item['ProductID']]);
                    }
                    break;

                default:
                    throw new Exception("Invalid action");
            }

            // Update order status
            $stmt = $pdo->prepare("
                UPDATE `Order` 
                SET Status = ? 
                WHERE OrderID = ?
            ");
            $stmt->execute([$new_status, $order_id]);

            $pdo->commit();
            header("Location: order_detail.php?id=" . $order_id);
            exit();
        } 
        catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }

    // Get order items
    $stmt = $pdo->prepare("
        SELECT 
            oi.*,
            p.Name as ProductName,
            p.Description as ProductDescription,
            p.Price as ProductPrice
        FROM OrderItem oi
        JOIN Product p ON oi.ProductID = p.ProductID
        WHERE oi.OrderID = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
} 
catch (PDOException $e) {
    $error = "Error loading order details: " . $e->getMessage();
}
?>

<!-- HTML and CSS for Order Details Page -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Order Details</h1>
    
    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div style="color: green; padding: 10px; margin-bottom: 20px; border: 1px solid green; border-radius: 4px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Order Information -->
    <?php if ($order): ?>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h2>Order Information</h2>
            <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['OrderID']); ?></p>
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['CustomerName']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['CustomerEmail']); ?></p>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['OrderDate']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['Status']); ?></p>
            <?php if ($order['Status'] === 'Shipped' || $order['Status'] === 'Delivered'): ?>
                <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['TrackingNum']); ?></p>
                <p><strong>Date Shipped:</strong> <?php echo htmlspecialchars($order['DateShipped']); ?></p>
                <?php if (!empty($order['ShippingNotes'])): ?>
                    <p><strong>Delivery Notes:</strong> <?php echo htmlspecialchars($order['ShippingNotes']); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <p><strong>Total:</strong> $<?php echo number_format($order['OrderTotal'], 2); ?></p>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h2>Order Items</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; text-align: left;">Product</th>
                        <th style="padding: 10px; text-align: left;">Quantity</th>
                        <th style="padding: 10px; text-align: left;">Price</th>
                        <th style="padding: 10px; text-align: left;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;"><?php echo htmlspecialchars($item['ProductName']); ?></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($item['Quantity']); ?></td>
                            <td style="padding: 10px;">$<?php echo number_format($item['ProductPrice'], 2); ?></td>
                            <td style="padding: 10px;">$<?php echo number_format($item['Subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h2>Contact Customer</h2>
            <form method="post" style="margin-bottom: 20px;">
                <div style="margin-bottom: 10px;">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" required style="width: 100%; padding: 8px; min-height: 100px;"></textarea>
                </div>
                <button type="submit" class="button">Send Message</button>
            </form>

            <!-- Message History -->
            <?php if (!empty($messages)): ?>
                <h3>Message History</h3>
                <div id="message-history" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                    <?php foreach ($messages as $message): ?>
                        <div class="message" style="margin-bottom: 15px; padding: 10px; background: <?php echo $message['SentBy'] === 'Employee' ? '#e3f2fd' : '#f5f5f5'; ?>; border-radius: 4px;">
                            <p style="margin: 0 0 5px 0;">
                                <strong><?php echo htmlspecialchars($message['SentBy'] === 'Employee' ? $message['EmployeeName'] : $order['CustomerName']); ?></strong>
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

        <!-- Order Processing Section -->
        <?php if ($order['Status'] === 'Processing'): ?>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <h2>Process Order</h2>
                <form method="post" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" name="action" value="ship" class="button">Ship Order</button>
                        <button type="submit" name="action" value="cancel" class="button" onclick="return confirm('Are you sure you want to cancel this order? This will restore the items to inventory.')">Cancel Order</button>
                    </div>
                </form>
            </div>
        <?php elseif ($order['Status'] === 'Shipped'): ?>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <h2>Process Order</h2>
                <form method="post" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label for="notes">Delivery Notes:</label>
                        <input type="text" id="notes" name="notes" style="width: 100%; padding: 8px;">
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-end;">
                        <button type="submit" name="action" value="deliver" class="button">Mark as Delivered</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px;">
            <a href="orders.php" class="button">Back to Orders</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
