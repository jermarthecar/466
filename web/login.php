<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Clear any existing session data
$_SESSION = array();

// Include database connection
require_once 'db_connect.php';
error_log("Database connection included");

// Check if database connection is established
if (!isset($pdo)) {
    error_log("Database connection failed - \$pdo not set in db_connect.php");
    // Display a error message to the user
    die("An error occurred connecting to the database. Please try again later or contact support.");
}

$error = ''; // Initialize error message variable

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim input to remove accidental whitespace
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $user_type = $_POST['user_type'] ?? '';

    error_log("Attempting login - Email: [$email], User Type: [$user_type]");

    // Basic validation
    if (empty($email) || empty($password) || empty($user_type)) {
        $error = "Please fill in all fields.";
        error_log("Login error: Empty fields");
    } 
    else {
        // Proceed with database checks within a try-catch block
        try {
            $user = null;
            $stmt = null;

            // Prepare statement based on user type
            switch ($user_type) {
                case 'customer':
                    $stmt = $pdo->prepare("SELECT CustomerID, Name, Email, Password FROM Customer WHERE Email = ?");
                    break;
                case 'employee':
                case 'owner':
                    $stmt = $pdo->prepare("SELECT EmployeeID, Name, Email, Password, AccessLevel FROM Employee WHERE Email = ?");
                    break;
                default:
                    $error = "Invalid user type selected.";
                    error_log("Login error: Invalid user type selected: $user_type");
            }

            // Execute the query if statement was prepared
            if ($stmt && !$error) {
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

                // Check if user was found
                if ($user) {
                    error_log("User found in database: " . print_r($user, true));

                    $password_matches = false;
                    if ($user_type === 'customer' || $user_type === 'owner' || $user_type === 'employee') {
                        // Try password_verify (for new accounts)
                        if (password_verify($password, $user['Password'])) {
                            $password_matches = true;
                        } 
                        else {
                            // Try SHA2 (for legacy accounts)
                            $password_verify_stmt = $pdo->prepare("SELECT SHA2(?, 256) = ? AS password_matches");
                            $password_verify_stmt->execute([$password, $user['Password']]);
                            $password_check = $password_verify_stmt->fetch(PDO::FETCH_ASSOC);
                            $password_matches = $password_check['password_matches'];
                        }
                    }

                    error_log("Password verification result for $email: " . ($password_matches ? 'Match' : 'No Match'));

                    if ($password_matches) {
                        // Password is correct, proceed with setting session

                        // Specific check for Owner login attempt
                        if ($user_type === 'owner') {
                            if (!isset($user['AccessLevel']) || $user['AccessLevel'] !== 'Owner') {
                                $error = "Access denied. Not an owner account.";
                                error_log("Login error: Attempted owner login for non-owner employee. Email: $email, AccessLevel: " . ($user['AccessLevel'] ?? 'N/A'));
                                $user = null; // Prevent session setting
                            } 
                            else {
                                $_SESSION['employee_id'] = $user['EmployeeID'];
                                $_SESSION['employee_name'] = $user['Name'];
                                $_SESSION['access_level'] = $user['AccessLevel'];
                                $_SESSION['user_type'] = 'owner';
                                error_log("Owner login successful for: $email");
                            }
                        } 
                        elseif ($user_type === 'employee') {
                            if (!isset($user['AccessLevel']) || $user['AccessLevel'] === 'Owner') {
                                $error = "Invalid login type for owner. Select 'Owner'.";
                                error_log("Login error: Attempted employee login for owner. Email: $email");
                                $user = null; // Prevent session setting
                            } 
                            else {
                                $_SESSION['employee_id'] = $user['EmployeeID'];
                                $_SESSION['employee_name'] = $user['Name'];
                                $_SESSION['access_level'] = $user['AccessLevel'];
                                $_SESSION['user_type'] = 'employee';
                                error_log("Employee login successful for: $email");
                            }
                        } 
                        elseif ($user_type === 'customer') {
                            $_SESSION['customer_id'] = $user['CustomerID'];
                            $_SESSION['customer_name'] = $user['Name'];
                            $_SESSION['user_type'] = 'customer';
                            error_log("Customer login successful for: $email");
                        }

                        // If no error occurred during session setup, redirect
                        if (!$error && isset($_SESSION['user_type'])) {
                            error_log("Session data after successful login: " . print_r($_SESSION, true));

                            // Determine redirect path based on user type using relative paths
                            $redirect_path = match ($_SESSION['user_type']) {
                                'customer' => './customer/index.php',
                                'employee' => './employee/index.php',
                                'owner' => './owner/index.php',
                                default => './index.php',
                            };

                            error_log("Redirecting to: $redirect_path");

                            // Perform redirection if headers haven't been sent
                            if (!headers_sent()) {
                                header("Location: $redirect_path");
                                exit(); // Stop script execution after redirect
                            } 
                            else {
                                // Fallback if headers are already sent
                                $error = "Login successful, but could not redirect automatically.";
                                error_log("HEADER ALREADY SENT ERROR - Cannot redirect to $redirect_path");
                                // Provide a manual link
                                echo "Login successful! <a href='$redirect_path'>Click here to continue</a>.";
                                exit();
                            }
                        }
                    } 
                    else {
                        // Password mismatch
                        $error = "Invalid email or password.";
                        error_log("Password mismatch for user: $email");
                    }
                } 
                else {
                    // User email not found in the corresponding table
                    $error = "Invalid email or password.";
                    error_log("User not found for email: $email and type: $user_type");
                }
            } 
            elseif (!$error) {
                 // This case handles if $stmt remained null (e.g., invalid user_type initially)
                 // The error message for invalid user type is set above.
                 error_log("Statement preparation failed or invalid user type was caught earlier.");
            }

        } catch (PDOException $e) {
            // Catch any database exceptions
            $error = "A database error occurred. Please try again later.";
            error_log("Login PDOException: " . $e->getMessage());
            if (isset($stmt)) {
                 // Log the query that failed if possible
                error_log("Failing SQL Query (approx): " . $stmt->queryString);
            }
             error_log("PDO Error Details: " . print_r($e->errorInfo, true));
        }
    }
}

if (isset($_SESSION['user_type'])) {
    echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Music Store</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        select {
             appearance: none;
             background-color: white;
        }
        .button {
            background-color: #5cb85c;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #4cae4c;
        }
        .error {
            color: #d9534f;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #d9534f;
            border-radius: 4px;
            background-color: #f2dede;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #5cb85c;
            text-decoration: none;
        }
         .register-link a:hover {
             text-decoration: underline;
         }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>

        <!-- Display error message if any -->
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="post" action="login.php"> <div class="form-group">
                <label for="user_type">Login As:</label>
                <select id="user_type" name="user_type" required>
                    <option value="">-- Select User Type --</option>
                    <option value="customer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                    <option value="employee" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'employee') ? 'selected' : ''; ?>>Employee</option>
                    <option value="owner" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'owner') ? 'selected' : ''; ?>>Owner</option>
                </select>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="button">Login</button>
        </form>

        <div class="register-link">
            <p>New customer? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>