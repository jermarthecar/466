<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php';

// Get order ID from request
$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    exit('Order ID not provided');
}

try {
    // Get messages for the order
    $stmt = $pdo->prepare("
        SELECT m.*, e.Name as EmployeeName, c.Name as CustomerName
        FROM Message m
        JOIN Employee e ON m.EmployeeID = e.EmployeeID
        JOIN Customer c ON m.CustomerID = c.CustomerID
        WHERE m.OrderID = ?
        ORDER BY m.SentAt ASC
    ");
    $stmt->execute([$order_id]);
    $messages = $stmt->fetchAll();

    // Output messages as HTML
    foreach ($messages as $message): ?>
        <div class="message" style="margin-bottom: 15px; padding: 10px; background: <?php echo $message['SentBy'] === 'Employee' ? '#e3f2fd' : '#f5f5f5'; ?>; border-radius: 4px;">
            <p style="margin: 0 0 5px 0;">
                <strong><?php echo htmlspecialchars($message['SentBy'] === 'Employee' ? $message['EmployeeName'] : $message['CustomerName']); ?></strong>
                <small style="color: #666;">(<?php echo date('M j, Y g:i A', strtotime($message['SentAt'])); ?>)</small>
            </p>
            <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($message['MessageText'])); ?></p>
        </div>
    <?php endforeach;
}
catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    exit('Error fetching messages');
}
?> 