<?php
require_once __DIR__ . '/../db_connect.php';

function authenticateCustomer($email, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Customer WHERE Email = ? AND Password = SHA2(?, 256)");
        $stmt->execute([$email, $password]);
        $customer = $stmt->fetch();
        
        error_log("Customer authentication attempt for email: " . $email);
        error_log("Found customer: " . ($customer ? "Yes" : "No"));
        
        if ($customer) {
            $_SESSION['customer_id'] = $customer['CustomerID'];
            $_SESSION['customer_name'] = $customer['Name'];
            $_SESSION['user_type'] = 'customer';
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Customer authentication error: " . $e->getMessage());
        return false;
    }
}

function authenticateEmployee($email, $password) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Employee WHERE Email = ? AND Password = SHA2(?, 256)");
        $stmt->execute([$email, $password]);
        $employee = $stmt->fetch();
        
        error_log("Employee authentication attempt for email: " . $email);
        error_log("Found employee: " . ($employee ? "Yes" : "No"));
        
        if ($employee) {
            $_SESSION['employee_id'] = $employee['EmployeeID'];
            $_SESSION['employee_name'] = $employee['Name'];
            $_SESSION['access_level'] = $employee['AccessLevel'];
            $_SESSION['user_type'] = 'employee';
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Employee authentication error: " . $e->getMessage());
        return false;
    }
}

function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']);
}

function isEmployeeLoggedIn() {
    return isset($_SESSION['employee_id']);
}

function redirectIfNotLoggedIn() {
    if (!isCustomerLoggedIn() && !isEmployeeLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotEmployee() {
    if (!isEmployeeLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function isOwnerLoggedIn() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner' && isset($_SESSION['employee_id']) && isset($_SESSION['access_level']) && $_SESSION['access_level'] === 'Owner';
}

function redirectIfNotOwner() {
    if (!isOwnerLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}
?>