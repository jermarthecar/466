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

// Get product ID from URL
$product_id = $_GET['id'] ?? 0;

// Validate product ID
if (!$product_id) {
    header('Location: products.php');
    exit();
}

$product = null; // Initialize product variable
$error = ''; // Initialize error message

try {
    // Get product details
    $stmt = $pdo->prepare("
        SELECT p.*
        FROM Product p
        WHERE p.ProductID = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    // Check if product exists
    if (!$product) {
        header('Location: products.php');
        exit();
    }


    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = $_POST['price'] ?? ''; // Keep as string for validation
        $stock_quantity = $_POST['stock_quantity'] ?? ''; // Keep as string
        $category_name = trim($_POST['category_name'] ?? ''); // Get from text input

        // Validate inputs
        if (empty($name)) {
            $error = "Product name cannot be empty.";
        } 
        elseif (empty($description)) {
            $error = "Product description cannot be empty.";
        } 
        elseif (!is_numeric($price) || floatval($price) <= 0) {
            $error = "Please enter a valid positive price.";
        } 
        elseif (!is_numeric($stock_quantity) || intval($stock_quantity) < 0) {
            $error = "Please enter a valid non-negative stock quantity.";
        } 
        elseif (empty($category_name)) {
            $error = "Please enter a category name.";
        } 
        elseif (strlen($category_name) > 50) {
             $error = "Category name cannot exceed 50 characters.";
        } 
        else {
            // Convert price and stock to appropriate types
            $price_float = floatval($price);
            $stock_int = intval($stock_quantity);

            // Update product
            $stmt = $pdo->prepare("
                UPDATE Product
                SET Name = ?, Description = ?, Price = ?, StockQuantity = ?, Category = ?
                WHERE ProductID = ?
            ");
            if ($stmt->execute([$name, $description, $price_float, $stock_int, $category_name, $product_id])) {
                 // Redirect on success to prevent re-submission
                 header('Location: product_detail.php?id=' . $product_id . '&success=1');
                 exit();
            } 
            else {
                 $error = "Failed to update product.";
            }
        }
         // If validation failed, reload product data to show current values in form
         $stmt = $pdo->prepare("SELECT p.* FROM Product p WHERE p.ProductID = ?");
         $stmt->execute([$product_id]);
         $product = $stmt->fetch();
    }
} 
catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    // Fetch product again in case of error during update process to display form
    if ($product_id && !$product) {
         try {
              $stmt = $pdo->prepare("SELECT p.* FROM Product p WHERE p.ProductID = ?");
              $stmt->execute([$product_id]);
              $product = $stmt->fetch();
         } 
        catch (PDOException $e2) // Ignore error fetching after error
    }
}

// Check for success message from redirect
$success_message = isset($_GET['success']) ? "Product updated successfully!" : "";

?>

<!-- HTML and CSS for the form -->
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Edit Product</h1>

    <?php if ($error): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
     <?php if ($success_message): ?>
        <div style="color: green; padding: 10px; margin-bottom: 20px; border: 1px solid green; border-radius: 4px;">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($product):?> <!-- Only show form if product exists -->
    <div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
        <form method="post" style="max-width: 600px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <label for="name" style="display: block; margin-bottom: 5px;">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $product['Name']); // Repopulate from POST on error ?>" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="description" style="display: block; margin-bottom: 5px;">Description:</label>
                <textarea id="description" name="description" required style="width: 100%; padding: 8px; height: 100px;"><?php echo htmlspecialchars($_POST['description'] ?? $product['Description']); ?></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="price" style="display: block; margin-bottom: 5px;">Price:</label>
                 <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? $product['Price']); ?>" step="0.01" min="0.01" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="stock_quantity" style="display: block; margin-bottom: 5px;">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? $product['StockQuantity']); ?>" min="0" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                 <label for="category_name" style="display: block; margin-bottom: 5px;">Category:</label>
                 <input type="text" id="category_name" name="category_name" maxlength="50" value="<?php echo htmlspecialchars($_POST['category_name'] ?? $product['Category']); ?>" required style="width: 100%; padding: 8px;">
            </div>


            <div style="text-align: center;">
                <button type="submit" class="button">Save Changes</button>
                <a href="product_detail.php?id=<?php echo $product_id; ?>" class="button">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>