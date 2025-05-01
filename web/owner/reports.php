<?php
session_start(); // Start session for owner authentication

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include auth functions first to start session
require_once '../includes/auth.php';  // Starts session if not started

// Check if user is logged in as owner
if (!isOwnerLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// First establish database connection
require_once '../db_connect.php';

// Get date range for reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Debug information
$debug_info = "Start Date: " . $start_date . "\n";
$debug_info .= "End Date: " . $end_date . "\n";

// Fetch sales data
try {
    // Total sales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.OrderID) as total_orders,
            COALESCE(SUM(oi.Quantity * oi.PriceAtOrderTime), 0) as total_revenue,
            COALESCE(AVG(oi.Quantity * oi.PriceAtOrderTime), 0) as average_order_value
        FROM `Order` o
        JOIN OrderItem oi ON o.OrderID = oi.OrderID
        WHERE o.OrderDate BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $sales_summary = $stmt->fetch();
    
    $debug_info .= "Total Orders: " . $sales_summary['total_orders'] . "\n";
    $debug_info .= "Total Revenue: " . $sales_summary['total_revenue'] . "\n";
    $debug_info .= "Average Order Value: " . $sales_summary['average_order_value'] . "\n";

    // Top products
    $stmt = $pdo->prepare("
        SELECT 
            p.Name,
            COALESCE(SUM(oi.Quantity), 0) as total_sold,
            COALESCE(SUM(oi.Quantity * oi.PriceAtOrderTime), 0) as total_revenue
        FROM OrderItem oi
        JOIN Product p ON oi.ProductID = p.ProductID
        JOIN `Order` o ON oi.OrderID = o.OrderID
        WHERE o.OrderDate BETWEEN ? AND ?
        GROUP BY p.ProductID
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_products = $stmt->fetchAll();

    // Sales by day
    $stmt = $pdo->prepare("
        SELECT 
            DATE(o.OrderDate) as date,
            COUNT(DISTINCT o.OrderID) as orders,
            COALESCE(SUM(oi.Quantity * oi.PriceAtOrderTime), 0) as revenue
        FROM `Order` o
        JOIN OrderItem oi ON o.OrderID = oi.OrderID
        WHERE o.OrderDate BETWEEN ? AND ?
        GROUP BY DATE(o.OrderDate)
        ORDER BY date
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_sales = $stmt->fetchAll();

} 
catch (PDOException $e) {
    $error_message = "Error fetching reports: " . $e->getMessage();
    $debug_info .= "Error: " . $e->getMessage() . "\n";
}

// Include header
require_once '../includes/header.php';
?>

<div class="container">
    <h1>Sales Reports</h1>

    <?php if (isset($error_message)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Date Range Form -->
    <div class="card">
        <form method="GET" class="form">
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
            </div>
            <button type="submit" class="button">Generate Report</button>
        </form>
    </div>

    <?php if (isset($sales_summary)): ?>
        <!-- Sales Summary -->
        <div class="card">
            <h2>Sales Summary</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p><?php echo number_format($sales_summary['total_orders'] ?? 0); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p>$<?php echo number_format($sales_summary['total_revenue'] ?? 0, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Average Order Value</h3>
                    <p>$<?php echo number_format($sales_summary['average_order_value'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="card">
            <h2>Top Selling Products</h2>
            <?php if (count($top_products) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['Name']); ?></td>
                                <td><?php echo number_format($product['total_sold'] ?? 0); ?></td>
                                <td>$<?php echo number_format($product['total_revenue'] ?? 0, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No sales found in this period.</p>
            <?php endif; ?>
        </div>

        <!-- Daily Sales Chart -->
        <div class="card">
            <h2>Daily Sales</h2>
            <?php if (count($daily_sales) > 0): ?>
                <div id="salesChart" style="height: 300px;"></div>
            <?php else: ?>
                <p>No daily sales data available for this period.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Debug Information -->
    <div class="card" style="display: none;">
        <h2>Debug Information</h2>
        <pre><?php echo htmlspecialchars($debug_info); ?></pre>
    </div>
</div>

<!-- Include Chart.js for the daily sales chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (isset($daily_sales)): ?>
const dailySales = <?php echo json_encode($daily_sales); ?>;
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: dailySales.map(sale => sale.date),
        datasets: [{
            label: 'Revenue',
            data: dailySales.map(sale => sale.revenue),
            borderColor: '#4CAF50',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>
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
    gap: 20px;
    align-items: flex-end;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-group label {
    font-weight: bold;
}

.form-group input {
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

.button:hover {
    opacity: 0.9;
}

.alert {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.alert.error {
    background: #f2dede;
    color: #a94442;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-card h3 {
    margin: 0;
    color: #666;
    font-size: 1rem;
}

.stat-card p {
    margin: 10px 0 0;
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
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