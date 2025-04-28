<?php
// ENABLE ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Store</title>
    <style>
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
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body>
<header>
    <nav>
        <div>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                <a href="../customer/index.php">Home</a>
                <a href="../customer/products.php">Products</a>
                <a href="../customer/cart.php" class="cart-link">
                    Cart
                    <?php
                    if (isset($_SESSION['customer_id'])) {
                        $stmt = $pdo->prepare("SELECT SUM(Quantity) as total FROM CartItem ci JOIN Cart c ON ci.CartID = c.CartID WHERE c.CustomerID = ?");
                        $stmt->execute([$_SESSION['customer_id']]);
                        $cart_total = $stmt->fetchColumn();
                        if ($cart_total > 0) {
                            echo '<span class="cart-count">' . $cart_total . '</span>';
                        }
                    }
                    ?>
                </a>
                <a href="../customer/orders.php">My Orders</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner'): ?>
                <a href="../index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee'): ?>
                <a href="../index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="orders.php">Orders</a>
                <a href="products.php">Products</a>
            <?php else: ?>
                <a href="index.php">Home</a>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer'): ?>
                <span class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['customer_name'])[0]); ?></span>
                <a href="../customer/logout.php" class="button">Logout</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner'): ?>
                <span class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['employee_name'])[0]); ?></span>
                <a href="../owner/logout.php" class="button">Logout</a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee'): ?>
                <span class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['employee_name'])[0]); ?></span>
                <a href="../employee/logout.php" class="button">Logout</a>
            <?php else: ?>
                <a href="login.php" class="button">Login</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<div class="container">