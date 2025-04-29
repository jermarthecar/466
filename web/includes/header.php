<?php
// ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Session is expected to be started by auth.php if included, or by the calling script ---

// --- Database connection check ---
if (!isset($pdo)) {
    // __DIR__ is the directory of *this* file (includes/)
    require_once __DIR__ . '/../db_connect.php';
    if (!isset($pdo)) {
        error_log("Database connection (\$pdo) not available in header.php");
    }
}

// Get user type safely
$user_type = $_SESSION['user_type'] ?? null;

// Determine location relative to web root
$current_script_path = $_SERVER['PHP_SELF'];
$is_in_customer = (strpos($current_script_path, '/customer/') !== false);
$is_in_owner = (strpos($current_script_path, '/owner/') !== false);
$is_in_employee = (strpos($current_script_path, '/employee/') !== false);
$is_in_root = !$is_in_customer && !$is_in_owner && !$is_in_employee;

// --- Define relative base paths ---
$root_rel_path = ''; // Path prefix to get TO the web root directory
if ($is_in_customer || $is_in_owner || $is_in_employee) {
    $root_rel_path = '../'; // Go up one level from customer/, owner/, employee/
} else {
    $root_rel_path = './'; // Already in the root
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Store</title>
    <style>
        /* --- [Existing Styles] --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: Arial, sans-serif; }
        body { display: flex; flex-direction: column; min-height: 100vh; }
        header { background: #333; color: white; padding: 10px 20px; }
        nav { display: flex; justify-content: space-between; align-items: center; }
        nav a { color: white; text-decoration: none; margin: 0 10px; padding: 5px 10px; border-radius: 4px; transition: background-color 0.3s; }
        nav a:hover { background-color: #555; }
        .container { flex: 1; padding: 20px; width: 100%; max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .button { padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; transition: background-color 0.3s; }
        .button:hover { background: #45a049; }
        .cart-link { position: relative; display: inline-block; }
        .cart-count { position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; }
        footer { background: #333; color: white; padding: 20px; text-align: center; margin-top: auto; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-name { color: #4CAF50; font-weight: bold; }
    </style>
</head>
<body>
<header>
    <nav>
        <div>
            <?php // Use RELATIVE paths calculated above ?>
            <?php if ($user_type === 'customer'): ?>
                <a href="<?php echo $is_in_customer ? 'index.php' : 'customer/index.php'; ?>">Home</a>
                <a href="<?php echo $is_in_customer ? 'products.php' : 'customer/products.php'; ?>">Products</a>
                <a href="<?php echo $is_in_customer ? 'cart.php' : 'customer/cart.php'; ?>" class="cart-link">
                    Cart
                    <?php
                    if (isset($pdo) && isset($_SESSION['customer_id'])) {
                        try {
                            $stmt = $pdo->prepare("SELECT SUM(Quantity) as total FROM CartItem ci JOIN Cart c ON ci.CartID = c.CartID WHERE c.CustomerID = ?");
                            $stmt->execute([$_SESSION['customer_id']]);
                            $cart_total = $stmt->fetchColumn();
                            if ($cart_total > 0) {
                                echo '<span class="cart-count">' . $cart_total . '</span>';
                            }
                        } catch (PDOException $e) {
                             error_log("Error fetching cart count: " . $e->getMessage());
                        }
                    }
                    ?>
                </a>
                <a href="<?php echo $is_in_customer ? 'orders.php' : 'customer/orders.php'; ?>">My Orders</a>
            <?php elseif ($user_type === 'owner'): ?>
                <a href="<?php echo $is_in_owner ? 'index.php' : 'owner/index.php'; ?>">Dashboard</a>
                <a href="<?php echo $is_in_owner ? 'orders.php' : 'owner/orders.php'; ?>">Orders</a>
                <a href="<?php echo $is_in_owner ? 'products.php' : 'owner/products.php'; ?>">Products</a>
                <a href="<?php echo $is_in_owner ? 'employees.php' : 'owner/employees.php'; ?>">Employees</a>
                <a href="<?php echo $is_in_owner ? 'reports.php' : 'owner/reports.php'; ?>">Reports</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee'): ?>
                <a href="<?php echo $is_in_employee ? 'index.php' : 'employee/index.php'; ?>">Home</a>
                <a href="<?php echo $is_in_employee ? 'dashboard.php' : 'employee/dashboard.php'; ?>">Dashboard</a>
                <a href="<?php echo $is_in_employee ? 'orders.php' : 'employee/orders.php'; ?>">Orders</a>
                <a href="<?php echo $is_in_employee ? 'products.php' : 'employee/products.php'; ?>">Products</a>
                <a href="<?php echo $is_in_employee ? 'customers.php' : 'employee/customers.php'; ?>">Customers</a>
            <?php else: // Not logged in ?>
                <a href="<?php echo $root_rel_path; ?>index.php">Home</a>
            <?php endif; ?>
        </div>
        <div class="user-info">
             <?php if ($user_type === 'customer'): ?>
                <span class="user-name">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['customer_name'])[0]); ?></span>
                <a href="<?php echo $is_in_customer ? 'logout.php' : 'customer/logout.php'; ?>" class="button">Logout</a>
            <?php elseif ($user_type === 'owner'): ?>
                 <span class="user-name">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['employee_name'])[0]); ?> (Owner)</span>
                <a href="<?php echo $is_in_owner ? 'logout.php' : 'owner/logout.php'; ?>" class="button">Logout</a>
            <?php elseif ($user_type === 'employee'): ?>
                 <span class="user-name">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['employee_name'])[0]); ?> (Employee)</span>
                <a href="<?php echo $is_in_employee ? 'logout.php' : 'employee/logout.php'; ?>" class="button">Logout</a>
            <?php else: // Not logged in ?>
                <a href="<?php echo $root_rel_path; ?>register.php">Register</a>
                <a href="<?php echo $root_rel_path; ?>login.php" class="button">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<div class="container">