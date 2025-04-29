<?php
// ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- [Existing Head and Style tags] ---
// Assume $pdo is available if needed for cart count, ensure db_connect.php was required before this header.
// Best practice: require db_connect.php once before this header if session check needs it AND cart count needs it.
// If db_connect.php is already required reliably before this file, the check below is optional.
if (!isset($pdo)) {
    // Attempt to include it relative to the includes directory
     require_once __DIR__ . '/../db_connect.php';
     // If $pdo is still not set, there's a bigger issue. Log or handle error.
     if (!isset($pdo)) {
         error_log("Database connection (\$pdo) not available in header.php");
         // Display minimal header or error
     }
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            height: 100%;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            background: #333;
            color: white;
            padding: 10px 20px;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        nav a:hover {
            background-color: #555;
        }
        .container {
            flex: 1;
            padding: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .button {
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        .button:hover {
            background: #45a049;
        }
        .cart-link {
            position: relative;
            display: inline-block;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        footer {
            background: #333;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-name {
            color: #4CAF50; /* Or another contrasting color */
            font-weight: bold;
        }
    </style>
</head>
<body>
<header>
    <nav>
        <div>
            <?php
                // Define base path relative to the web root.
                // This might need adjustment based on your server setup.
                // If your site root is 'web/', this should work.
                // If header.php is always included from files within web/, these paths should be fine.
                $base_path = '/'; // Adjust if your site isn't at the web server root
                                 // Or use relative paths carefully from the perspective of files *including* this header.

                // Determine the correct base directory for links based on user type context
                // A common approach is to define a base URL constant in a config file.
                // For simplicity here, we'll use relative paths assuming includes/header.php location
                $customer_base = '../customer/';
                $owner_base = '../owner/';
                $employee_base = '../employee/';
                $root_base = '../'; // Path from 'includes' back to 'web' root

                // Handle cases where header might be included from root files (e.g., index.php, login.php)
                // We can check the current script path, but a simpler fix for this structure
                // is often to use root-relative paths if the server setup allows (e.g., href="/customer/index.php")
                // Or, ensure all includes use consistent relative paths.
                // Let's assume includes are always from one level down (customer/, owner/, employee/) or root.

                // A simple check (might need refinement): are we in a subdirectory?
                 $is_subdir = strpos($_SERVER['PHP_SELF'], '/customer/') !== false ||
                             strpos($_SERVER['PHP_SELF'], '/owner/') !== false ||
                             strpos($_SERVER['PHP_SELF'], '/employee/') !== false;

                if (!$is_subdir) {
                    // If included from root (e.g., web/index.php, web/login.php)
                    $customer_base = './customer/';
                    $owner_base = './owner/';
                    $employee_base = './employee/';
                    $root_base = './';
                }


            ?>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                <a href="<?php echo $customer_base; ?>index.php">Home</a>
                <a href="<?php echo $customer_base; ?>products.php">Products</a>
                <a href="<?php echo $customer_base; ?>cart.php" class="cart-link">
                    Cart
                    <?php
                    // Ensure $pdo is available before trying to use it
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
                             // Optionally display an error or default icon
                        }
                    }
                    ?>
                </a>
                <a href="<?php echo $customer_base; ?>orders.php">My Orders</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner'): ?>
                <a href="<?php echo $owner_base; ?>index.php">Dashboard</a>  <a href="<?php echo $owner_base; ?>orders.php">Orders</a>
                <a href="<?php echo $owner_base; ?>products.php">Products</a>
                <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee'): ?>
                <a href="<?php echo $employee_base; ?>index.php">Home</a> <a href="<?php echo $employee_base; ?>dashboard.php">Dashboard</a> <a href="<?php echo $employee_base; ?>orders.php">Orders</a>
                <a href="<?php echo $employee_base; ?>products.php">Products</a>
                 <?php else: // Not logged in ?>
                <a href="<?php echo $root_base; ?>index.php">Home</a>
                 <?php endif; ?>
        </div>
        <div class="user-info">
             <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                <span class="user-name">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['customer_name'])[0]); ?></span>
                <a href="<?php echo $customer_base; ?>logout.php" class="button">Logout</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner'): ?>
                 <span class="user-name">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['employee_name'])[0]); ?> (Owner)</span>
                <a href="<?php echo $owner_base; ?>logout.php" class="button">Logout</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee'): ?>
                 <span class="user-name">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['employee_name'])[0]); ?> (Employee)</span>
                <a href="<?php echo $employee_base; ?>logout.php" class="button">Logout</a>
            <?php else: // Not logged in ?>
                <a href="<?php echo $root_base; ?>register.php">Register</a>
                <a href="<?php echo $root_base; ?>login.php" class="button">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<div class="container">