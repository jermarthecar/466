<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php';

// Check if user is logged in as owner
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'Owner') {
    header('Location: ../login.php');
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Then include header which might need database access
require_once '../includes/header.php';

// Get owner name for welcome message
$owner_name = $_SESSION['employee_name'];

// Get product ID from URL
$product_id = $_GET['id'] ?? 0;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

try {
    // Get product details
    $stmt = $pdo->prepare("
        SELECT p.*, c.Name as CategoryName 
        FROM Product p 
        JOIN Category c ON p.CategoryID = c.CategoryID 
        WHERE p.ProductID = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: products.php');
        exit();
    }

    // Get all categories for dropdown
    $stmt = $pdo->prepare("SELECT CategoryID, Name FROM Category ORDER BY Name");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $stock_quantity = $_POST['stock_quantity'] ?? 0;
        $category_id = $_POST['category_id'] ?? 0;

        // Validate inputs
        if (empty($name) || empty($description) || $price <= 0 || $stock_quantity < 0 || $category_id <= 0) {
            $error = "Please fill in all fields with valid values.";
        } else {
            // Update product
            $stmt = $pdo->prepare("
                UPDATE Product 
                SET Name = ?, Description = ?, Price = ?, StockQuantity = ?, CategoryID = ?
                WHERE ProductID = ?
            ");
            $stmt->execute([$name, $description, $price, $stock_quantity, $category_id, $product_id]);
            
            header('Location: product_detail.php?id=' . $product_id);
            exit();
        }
    }
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Edit Product</h1>
    
    <?php if (isset($error)): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
        <form method="post" style="max-width: 600px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <label for="name" style="display: block; margin-bottom: 5px;">Product Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['Name']); ?>" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="description" style="display: block; margin-bottom: 5px;">Description:</label>
                <textarea id="description" name="description" required style="width: 100%; padding: 8px; height: 100px;"><?php echo htmlspecialchars($product['Description']); ?></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="price" style="display: block; margin-bottom: 5px;">Price:</label>
                <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($product['Price']); ?>" step="0.01" min="0" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="stock_quantity" style="display: block; margin-bottom: 5px;">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product['StockQuantity']); ?>" min="0" required style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="category_id" style="display: block; margin-bottom: 5px;">Category:</label>
                <select id="category_id" name="category_id" required style="width: 100%; padding: 8px;">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['CategoryID']; ?>" <?php echo $category['CategoryID'] == $product['CategoryID'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="button">Save Changes</button>
                <a href="product_detail.php?id=<?php echo $product_id; ?>" class="button">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 