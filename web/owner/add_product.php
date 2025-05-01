<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php'; // Starts session if not started

// Check if user is logged in as owner using the function
if (!isOwnerLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Establish database connection
require_once '../db_connect.php';

// Include header
require_once '../includes/header.php';

// Get owner name for welcome message
$owner_name = $_SESSION['employee_name'];

$error = '';
$success = '';
$name = ''; // Initialize form variables
$description = '';
$price = '';
$stock_quantity = '';
$category_name = ''; // Use category_name for text input

try {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? ''; // Keep as string for validation
        $stock_quantity = $_POST['stock_quantity'] ?? ''; // Keep as string
        $category_name = trim($_POST['category_name'] ?? ''); // Get category name from text input

        // Validate inputs
        if (empty($name)) {
            $error = "Product name is required.";
        } 
        elseif (empty($description)) {
            $error = "Product description is required.";
        } 
        elseif (!is_numeric($price) || floatval($price) <= 0) {
            $error = "Price must be a positive number.";
        } 
        elseif (!is_numeric($stock_quantity) || intval($stock_quantity) < 0) {
            $error = "Stock quantity cannot be negative.";
        } 
        elseif (empty($category_name)) {
            $error = "Category name is required.";
        } 
        elseif (strlen($category_name) > 50) {
             $error = "Category name cannot exceed 50 characters.";
        } 
        else {
            // Convert numeric values
            $price_float = floatval($price);
            $stock_int = intval($stock_quantity);

            // Insert new product - use Product.Category column
            $stmt = $pdo->prepare("
                INSERT INTO Product (Name, Description, Price, StockQuantity, Category)
                VALUES (?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$name, $description, $price_float, $stock_int, $category_name])) {
                $product_id = $pdo->lastInsertId(); // Get the ID of the new product
                $success = "Product added successfully!";
                // Clear form data only on success
                $name = $description = $price = $stock_quantity = $category_name = '';
                 // Redirect to the new product's detail page
                 header('Location: product_detail.php?id=' . $product_id);
                 exit();
            } 
            else {
                $error = "Failed to add product. Please try again.";
            }
        }
    }
} 
catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!-- HTML and CSS for the form -->
<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Add New Product</h1>

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

    <div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
        <form method="post" style="max-width: 600px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <label for="name" style="display: block; margin-bottom: 5px;">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="description" style="display: block; margin-bottom: 5px;">Description:</label>
                <textarea id="description" name="description" required style="width: 100%; padding: 8px; height: 100px;"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="price" style="display: block; margin-bottom: 5px;">Price:</label>
                <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" step="0.01" min="0.01" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="stock_quantity" style="display: block; margin-bottom: 5px;">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($stock_quantity); ?>" min="0" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="category_name" style="display: block; margin-bottom: 5px;">Category:</label>
                <input type="text" id="category_name" name="category_name" maxlength="50" value="<?php echo htmlspecialchars($category_name); ?>" required style="width: 100%; padding: 8px;">
            </div>

            <div style="text-align: center;">
                <button type="submit" class="button">Add Product</button>
                <a href="products.php" class="button">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>