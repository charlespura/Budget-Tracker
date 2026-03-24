<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';
require_once 'includes/auth.php';

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get current balance
$balance_query = "SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses
    FROM transactions WHERE user_id = ?";
$stmt = $conn->prepare($balance_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$balance_result = $stmt->get_result()->fetch_assoc();
$total_income = $balance_result['total_income'] ?? 0;
$total_expenses = $balance_result['total_expenses'] ?? 0;
$current_balance = $total_income - $total_expenses;

// Get recent transactions
$recent_query = "SELECT t.*, c.name as category_name, c.icon 
                 FROM transactions t 
                 LEFT JOIN categories c ON t.category_id = c.id 
                 WHERE t.user_id = ? 
                 ORDER BY t.transaction_date DESC, t.created_at DESC 
                 LIMIT 10";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_transactions = $stmt->get_result();

// Get monthly spending by category
$monthly_spending = "SELECT c.name, c.icon, SUM(t.amount) as total 
                     FROM transactions t 
                     JOIN categories c ON t.category_id = c.id 
                     WHERE t.user_id = ? AND t.type = 'expense' 
                     AND MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
                     GROUP BY c.id 
                     ORDER BY total DESC LIMIT 5";
$stmt = $conn->prepare($monthly_spending);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$category_spending = $stmt->get_result();

include 'includes/header.php';
?>

<div class="dashboard">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <h3>Current Balance</h3>
                <p class="stat-amount <?php echo $current_balance >= 0 ? 'positive' : 'negative'; ?>">
                    ₱<?php echo number_format($current_balance, 2); ?>
                </p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <h3>Total Income</h3>
                <p class="stat-amount positive">₱<?php echo number_format($total_income, 2); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📉</div>
            <div class="stat-info">
                <h3>Total Expenses</h3>
                <p class="stat-amount negative">₱<?php echo number_format($total_expenses, 2); ?></p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-actions">
        <a href="add_transaction.php" class="btn btn-primary">+ Add Transaction</a>
        <a href="reports.php" class="btn btn-secondary">📊 View Reports</a>
        <a href="budget_goals.php" class="btn btn-secondary">🎯 Set Goals</a>
    </div>
    
    <div class="dashboard-grid">
        <div class="card">
            <h2>Recent Transactions</h2>
            <?php if ($recent_transactions->num_rows > 0): ?>
                <div class="transaction-list">
                    <?php while($trans = $recent_transactions->fetch_assoc()): ?>
                        <div class="transaction-item">
                            <div class="transaction-icon"><?php echo $trans['icon'] ?? ($trans['type'] === 'income' ? '💰' : '💸'); ?></div>
                            <div class="transaction-details">
                                <div class="transaction-category"><?php echo htmlspecialchars($trans['category_name'] ?? 'Uncategorized'); ?></div>
                                <div class="transaction-date"><?php echo date('M d, Y', strtotime($trans['transaction_date'])); ?></div>
                                <?php if($trans['description']): ?>
                                    <div class="transaction-desc"><?php echo htmlspecialchars($trans['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="transaction-amount <?php echo $trans['type']; ?>">
                                <?php echo $trans['type'] === 'income' ? '+' : '-'; ?> ₱<?php echo number_format($trans['amount'], 2); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No transactions yet. <a href="add_transaction.php">Add your first transaction!</a></p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Top Spending Categories</h2>
            <?php if ($category_spending->num_rows > 0): ?>
                <div class="category-list">
                    <?php while($cat = $category_spending->fetch_assoc()): ?>
                        <div class="category-item">
                            <span class="category-icon"><?php echo $cat['icon']; ?></span>
                            <span class="category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                            <span class="category-amount">₱<?php echo number_format($cat['total'], 2); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No expenses this month yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>