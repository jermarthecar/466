<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php';

// Check if user is logged in as owner
if (!isset($_SESSION['owner_id'])) {
    header('Location: ../login.php');
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once '../includes/header.php';

// Get owner name for welcome message
$owner_name = $_SESSION['owner_name'];

$error = '';
$success = '';

try {
    // Get all categories for the dropdown
    $stmt = $pdo->prepare("SELECT CategoryID, Name FROM Category ORDER BY Name");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);

        // Validate inputs
        if (empty($name)) {
            $error = "Product name is required.";
        } elseif (empty($description)) {
            $error = "Product description is required.";
        } elseif ($price <= 0) {
            $error = "Price must be greater than 0.";
        } elseif ($stock_quantity < 0) {
            $error = "Stock quantity cannot be negative.";
        } elseif ($category_id <= 0) {
            $error = "Please select a valid category.";
        } else {
            // Insert new product
            $stmt = $pdo->prepare("
                INSERT INTO Product (Name, Description, Price, StockQuantity, CategoryID)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$name, $description, $price, $stock_quantity, $category_id])) {
                $success = "Product added successfully!";
                // Clear form data
                $name = $description = '';
                $price = $stock_quantity = 0;
                $category_id = 0;
            } else {
                $error = "Failed to add product. Please try again.";
            }
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

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
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="description" style="display: block; margin-bottom: 5px;">Description:</label>
                <textarea id="description" name="description" required style="width: 100%; padding: 8px; height: 100px;"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="price" style="display: block; margin-bottom: 5px;">Price:</label>
                <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($price ?? 0); ?>" step="0.01" min="0" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="stock_quantity" style="display: block; margin-bottom: 5px;">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($stock_quantity ?? 0); ?>" min="0" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="category_id" style="display: block; margin-bottom: 5px;">Category:</label>
                <select id="category_id" name="category_id" required style="width: 100%; padding: 8px;">
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['CategoryID']; ?>" <?php echo ($category_id == $category['CategoryID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="button">Add Product</button>
                <a href="products.php" class="button">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 