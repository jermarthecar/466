<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// First establish database connection
require_once 'db_connect.php';

// include header
require_once 'includes/header.php';

// Initialize variables
$error = '';
$success = '';
$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$address = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } 
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } 
    elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } 
    else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email is already registered.';
            } 
            else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Combine first and last name
                $full_name = $first_name . ' ' . $last_name;

                // Insert new customer
                $stmt = $pdo->prepare("
                    INSERT INTO Customer (Name, Email, Password, ShippingAddress) 
                    VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $full_name,
                    $email,
                    $hashed_password,
                    $address
                ]);

                $success = 'Registration successful! You can now login.';
                
                // Clear form
                $first_name = $last_name = $email = $phone = $address = '';
            }
        } 
        catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>

<!-- HTML and CSS for the registration form -->
<div style="max-width: 600px; margin: 40px auto; padding: 20px; background: #f9f9f9; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="text-align: center; margin-bottom: 20px;">Create an Account</h2>
    
    <!-- Display error or success messages -->
    <?php if ($error): ?>
        <div style="color: red; padding: 10px; margin-bottom: 20px; border: 1px solid red; border-radius: 4px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color: #4CAF50; padding: 10px; margin-bottom: 20px; border: 1px solid #4CAF50; border-radius: 4px;">
            <?php echo htmlspecialchars($success); ?>
            <p style="margin-top: 10px;">
                <a href="login.php" style="color: #4CAF50;">Click here to login</a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Registration form -->
    <form method="POST" action="" style="display: flex; flex-direction: column; gap: 15px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label for="first_name" style="display: block; margin-bottom: 5px;">First Name *</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" 
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
            </div>
            <div>
                <label for="last_name" style="display: block; margin-bottom: 5px;">Last Name *</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" 
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
            </div>
        </div>

        <div>
            <label for="email" style="display: block; margin-bottom: 5px;">Email *</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
        </div>

        <div>
            <label for="phone" style="display: block; margin-bottom: 5px;">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div>
            <label for="address" style="display: block; margin-bottom: 5px;">Address</label>
            <textarea id="address" name="address" rows="3" 
                      style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"><?php echo htmlspecialchars($address); ?></textarea>
        </div>

        <div>
            <label for="password" style="display: block; margin-bottom: 5px;">Password *</label>
            <input type="password" id="password" name="password" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
            <small style="color: #666;">Password must be at least 8 characters long</small>
        </div>

        <div>
            <label for="confirm_password" style="display: block; margin-bottom: 5px;">Confirm Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
        </div>

        <button type="submit" style="background: #4CAF50; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer;">
            Register
        </button>
    </form>

    <div style="text-align: center; margin-top: 20px;">
        <p>Already have an account? <a href="login.php" style="color: #4CAF50;">Login here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>