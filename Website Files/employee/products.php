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

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once '../includes/header.php';

// Get employee name for welcome message
$employee_name = $_SESSION['employee_name'];
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Product Management</h1>
    
    <?php
    try {
        // Get all products
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   (SELECT COUNT(*) FROM OrderItem oi WHERE oi.ProductID = p.ProductID) as total_orders
            FROM Product p
            ORDER BY p.Name
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();

        if ($products) {
            echo '<div style="overflow-x: auto;">';
            echo '<table style="width: 100%; border-collapse: collapse;">';
            echo '<thead><tr style="background: #f5f5f5;">';
            echo '<th style="padding: 10px; text-align: left;">Product ID</th>';
            echo '<th style="padding: 10px; text-align: left;">Name</th>';
            echo '<th style="padding: 10px; text-align: left;">Price</th>';
            echo '<th style="padding: 10px; text-align: left;">Stock</th>';
            echo '<th style="padding: 10px; text-align: left;">Category</th>';
            echo '<th style="padding: 10px; text-align: left;">Total Orders</th>';
            echo '<th style="padding: 10px; text-align: left;">Actions</th>';
            echo '</tr></thead><tbody>';

            foreach ($products as $product) {
                echo '<tr style="border-bottom: 1px solid #ddd;">';
                echo '<td style="padding: 10px;">#' . htmlspecialchars($product['ProductID']) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($product['Name']) . '</td>';
                echo '<td style="padding: 10px;">$' . number_format($product['Price'], 2) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($product['StockQuantity']) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($product['Category']) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($product['total_orders']) . '</td>';
                echo '<td style="padding: 10px;">';
                echo '<a href="edit_product.php?id=' . $product['ProductID'] . '" style="color: #4CAF50; text-decoration: none; margin-right: 10px;">Edit</a>';
                echo '<a href="delete_product.php?id=' . $product['ProductID'] . '" style="color: #f44336; text-decoration: none;">Delete</a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<p>No products found.</p>';
        }
    } catch (PDOException $e) {
        echo '<p style="color: red;">Error loading products: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>

    <div style="margin-top: 30px; text-align: center;">
        <a href="add_product.php" class="button" style="display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
            Add New Product
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 