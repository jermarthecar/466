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

require_once '../db_connect.php';
require_once '../includes/header.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);

        if (empty($name) || $price <= 0 || $stock < 0) {
            throw new Exception('Please fill in all required fields with valid values.');
        }

        // Insert new product
        $stmt = $pdo->prepare("
            INSERT INTO Product (Name, Price, StockQuantity, Description, Category)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $price, $stock, $description, $category]);

        $success_message = 'Product added successfully!';
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <h1>Add New Product</h1>

    <?php if ($success_message): ?>
        <div style="background-color: #dff0d8; color: #3c763d; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div style="background-color: #f2dede; color: #a94442; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="post" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
        <div style="margin-bottom: 15px;">
            <label for="name">Product Name:</label>
            <input type="text" id="name" name="name" required 
                   style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" min="0.01" required 
                   style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="stock">Stock Quantity:</label>
            <input type="number" id="stock" name="stock" min="0" required 
                   style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="category">Category:</label>
            <input type="text" id="category" name="category" required 
                   style="width: 100%; padding: 8px; margin-top: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="4" 
                      style="width: 100%; padding: 8px; margin-top: 5px;"></textarea>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <a href="products.php" class="button" style="background: #6c757d;">Cancel</a>
            <button type="submit" class="button">Add Product</button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?> 