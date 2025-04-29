<?php
// Ensure session is started BEFORE any checks or functions are defined/used
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db_connect.php';

// --- authenticateCustomer function remains the same ---
function authenticateCustomer($email, $password) {
    global $pdo;
    try {
        // First, try matching with password_hash
        $stmt = $pdo->prepare("SELECT CustomerID, Name, Email, Password FROM Customer WHERE Email = ?");
        $stmt->execute([$email]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer && password_verify($password, $customer['Password'])) {
             error_log("Customer authentication successful (password_verify) for email: " . $email);
             $_SESSION['customer_id'] = $customer['CustomerID'];
             $_SESSION['customer_name'] = $customer['Name'];
             $_SESSION['user_type'] = 'customer';
             return true;
        }

        // If password_verify fails, try SHA2 for legacy passwords
        $stmt_sha = $pdo->prepare("SELECT CustomerID, Name, Email FROM Customer WHERE Email = ? AND Password = SHA2(?, 256)");
        $stmt_sha->execute([$email, $password]);
        $customer_sha = $stmt_sha->fetch(PDO::FETCH_ASSOC);

        if ($customer_sha) {
            error_log("Customer authentication successful (SHA2) for email: " . $email);
            $_SESSION['customer_id'] = $customer_sha['CustomerID'];
            $_SESSION['customer_name'] = $customer_sha['Name'];
            $_SESSION['user_type'] = 'customer';
            return true;
        }

        error_log("Customer authentication failed for email: " . $email);
        return false;

    } catch (PDOException $e) {
        error_log("Customer authentication error: " . $e->getMessage());
        return false;
    }
}


// --- authenticateEmployee function remains the same ---
function authenticateEmployee($email, $password) {
    global $pdo;
    try {
        // First, try matching with password_hash (assuming future employees might use it)
        $stmt = $pdo->prepare("SELECT EmployeeID, Name, Email, Password, AccessLevel FROM Employee WHERE Email = ?");
        $stmt->execute([$email]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee && password_verify($password, $employee['Password'])) {
             error_log("Employee authentication successful (password_verify) for email: " . $email);
             $_SESSION['employee_id'] = $employee['EmployeeID'];
             $_SESSION['employee_name'] = $employee['Name'];
             $_SESSION['access_level'] = $employee['AccessLevel'];
             // Determine user_type based on AccessLevel AFTER successful login
             $_SESSION['user_type'] = ($employee['AccessLevel'] === 'Owner') ? 'owner' : 'employee';
             return true;
        }


        // If password_verify fails, try SHA2
        $stmt_sha = $pdo->prepare("SELECT EmployeeID, Name, Email, Password, AccessLevel FROM Employee WHERE Email = ? AND Password = SHA2(?, 256)");
        $stmt_sha->execute([$email, $password]);
        $employee_sha = $stmt_sha->fetch(PDO::FETCH_ASSOC);

        if ($employee_sha) {
            error_log("Employee authentication successful (SHA2) for email: " . $email);
            $_SESSION['employee_id'] = $employee_sha['EmployeeID'];
            $_SESSION['employee_name'] = $employee_sha['Name'];
            $_SESSION['access_level'] = $employee_sha['AccessLevel'];
            // Determine user_type based on AccessLevel AFTER successful login
             $_SESSION['user_type'] = ($employee_sha['AccessLevel'] === 'Owner') ? 'owner' : 'employee';
            return true;
        }

        error_log("Employee authentication failed for email: " . $email);
        return false;

    } catch (PDOException $e) {
        error_log("Employee authentication error: " . $e->getMessage());
        return false;
    }
}


// --- isCustomerLoggedIn, isEmployeeLoggedIn, isOwnerLoggedIn remain the same ---
function isCustomerLoggedIn() {
    // Check for customer-specific session variables
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer' && isset($_SESSION['customer_id']);
}

function isEmployeeLoggedIn() {
     // Check for employee-specific session variables (excluding owner)
     return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee' && isset($_SESSION['employee_id']) && isset($_SESSION['access_level']) && $_SESSION['access_level'] !== 'Owner';
}

function isOwnerLoggedIn() {
    // Check for owner-specific session variables
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner' && isset($_SESSION['employee_id']) && isset($_SESSION['access_level']) && $_SESSION['access_level'] === 'Owner';
}


// --- REDIRECT FUNCTIONS - Use RELATIVE paths ---
function redirectIfNotLoggedIn() {
    // This function is less likely to be called directly, but if it were from a subdir...
    if (!isCustomerLoggedIn() && !isEmployeeLoggedIn() && !isOwnerLoggedIn()) {
        // Assume called from a subdir like owner/, customer/, employee/
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotEmployee() {
    // Assume called from employee/ subdir
    if (!isEmployeeLoggedIn() && !isOwnerLoggedIn()) { // Allow owner through for now
        header("Location: ../login.php");
        exit();
    }
}


function redirectIfNotOwner() {
     // Assume called from owner/ subdir
    if (!isOwnerLoggedIn()) {
        header('Location: ../login.php'); // Path from owner/ back to login.php
        exit();
    }
}
?>