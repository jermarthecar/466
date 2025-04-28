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
            <a href="<?php echo dirname(dirname($_SERVER['PHP_SELF'])); ?>/index.php">Home</a>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Dashboard</a>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/orders.php">Orders</a>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/products.php">Products</a>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['employee_name'])[0]); ?></span>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/logout.php" class="button">Logout</a>
        </div>
    </nav>
</header>
<div class="container"> 