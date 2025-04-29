<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php';

// Check if user is logged in as owner
if (!isOwnerLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['access_level'])) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO Employee (Name, Email, Password, AccessLevel) VALUES (?, ?, SHA2(?, 256), ?)");
                        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['password'], $_POST['access_level']]);
                        $success_message = "Employee added successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error adding employee: " . $e->getMessage();
                    }
                }
                break;

            case 'edit':
                if (isset($_POST['employee_id'], $_POST['name'], $_POST['email'], $_POST['access_level'])) {
                    try {
                        $stmt = $pdo->prepare("UPDATE Employee SET Name = ?, Email = ?, AccessLevel = ? WHERE EmployeeID = ?");
                        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['access_level'], $_POST['employee_id']]);
                        $success_message = "Employee updated successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error updating employee: " . $e->getMessage();
                    }
                }
                break;

            case 'delete':
                if (isset($_POST['employee_id'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM Employee WHERE EmployeeID = ?");
                        $stmt->execute([$_POST['employee_id']]);
                        $success_message = "Employee deleted successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error deleting employee: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Fetch all employees
try {
    $stmt = $pdo->query("SELECT * FROM Employee ORDER BY Name");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching employees: " . $e->getMessage();
    $employees = [];
}

// Include header
require_once '../includes/header.php';
?>

<div class="container">
    <h1>Employee Management</h1>

    <?php if (isset($success_message)): ?>
        <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Add New Employee Form -->
    <div class="card">
        <h2>Add New Employee</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="access_level">Access Level:</label>
                <select id="access_level" name="access_level" required>
                    <option value="Employee">Employee</option>
                    <option value="Owner">Owner</option>
                </select>
            </div>
            <button type="submit" class="button">Add Employee</button>
        </form>
    </div>

    <!-- Employee List -->
    <div class="card">
        <h2>Current Employees</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Access Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($employee['EmployeeID']); ?></td>
                        <td><?php echo htmlspecialchars($employee['Name']); ?></td>
                        <td><?php echo htmlspecialchars($employee['Email']); ?></td>
                        <td><?php echo htmlspecialchars($employee['AccessLevel']); ?></td>
                        <td>
                            <button type="button" class="button" onclick="showEditModal(<?php echo $employee['EmployeeID']; ?>)">
                                Edit
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="employee_id" value="<?php echo $employee['EmployeeID']; ?>">
                                <button type="submit" class="button danger" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit Employee</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="employee_id" id="edit_employee_id">
            <div class="form-group">
                <label for="edit_name">Name:</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="edit_access_level">Access Level:</label>
                <select id="edit_access_level" name="access_level" required>
                    <option value="Employee">Employee</option>
                    <option value="Owner">Owner</option>
                </select>
            </div>
            <button type="submit" class="button">Save Changes</button>
        </form>
    </div>
</div>

<script>
function showEditModal(employeeId) {
    const modal = document.getElementById('editModal');
    const employee = <?php echo json_encode($employees); ?>.find(e => e.EmployeeID == employeeId);
    
    if (employee) {
        document.getElementById('edit_employee_id').value = employee.EmployeeID;
        document.getElementById('edit_name').value = employee.Name;
        document.getElementById('edit_email').value = employee.Email;
        document.getElementById('edit_access_level').value = employee.AccessLevel;
        modal.style.display = 'block';
    }
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-group label {
    font-weight: bold;
}

.form-group input,
.form-group select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
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
}

.button.danger {
    background: #f44336;
}

.button:hover {
    opacity: 0.9;
}

.alert {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.alert.success {
    background: #dff0d8;
    color: #3c763d;
}

.alert.error {
    background: #f2dede;
    color: #a94442;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 50%;
    max-width: 500px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

tr:hover {
    background-color: #f5f5f5;
}
</style>

<?php require_once '../includes/footer.php'; ?> 