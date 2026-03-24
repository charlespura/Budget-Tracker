<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get date filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get filtered transactions
$transactions_query = "SELECT t.*, c.name as category_name, c.icon 
                      FROM transactions t 
                      LEFT JOIN categories c ON t.category_id = c.id 
                      WHERE t.user_id = ? 
                      AND t.transaction_date BETWEEN ? AND ?
                      ORDER BY t.transaction_date DESC";
$stmt = $conn->prepare($transactions_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$transactions = $stmt->get_result();

// Calculate totals
$income_total = 0;
$expense_total = 0;
$transactions->data_seek(0);
while($trans = $transactions->fetch_assoc()) {
    if ($trans['type'] === 'income') {
        $income_total += $trans['amount'];
    } else {
        $expense_total += $trans['amount'];
    }
}

// Get category breakdown
$category_breakdown = "SELECT c.name, c.icon, 
                       SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END) as expense_total,
                       SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) as income_total
                       FROM transactions t 
                       JOIN categories c ON t.category_id = c.id 
                       WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
                       GROUP BY c.id
                       ORDER BY expense_total DESC";
$stmt = $conn->prepare($category_breakdown);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$categories = $stmt->get_result();

include 'includes/header.php';
?>

<div class="reports-page">
    <h1>Transaction Reports</h1>
    
    <div class="card">
        <h2>Filter by Date</h2>
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <h3>Total Income</h3>
                <p class="stat-amount positive">₱<?php echo number_format($income_total, 2); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📉</div>
            <div class="stat-info">
                <h3>Total Expenses</h3>
                <p class="stat-amount negative">₱<?php echo number_format($expense_total, 2); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <h3>Net Balance</h3>
                <p class="stat-amount <?php echo ($income_total - $expense_total) >= 0 ? 'positive' : 'negative'; ?>">
                    ₱<?php echo number_format($income_total - $expense_total, 2); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="card">
            <h2>Category Breakdown</h2>
            <?php if ($categories->num_rows > 0): ?>
                <div class="category-list">
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <div class="category-item">
                            <span class="category-icon"><?php echo $cat['icon']; ?></span>
                            <span class="category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                            <div class="category-stats">
                                <?php if($cat['expense_total'] > 0): ?>
                                    <span class="expense-amount">-₱<?php echo number_format($cat['expense_total'], 2); ?></span>
                                <?php endif; ?>
                                <?php if($cat['income_total'] > 0): ?>
                                    <span class="income-amount">+₱<?php echo number_format($cat['income_total'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No transactions in this period.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Transaction History</h2>
            <?php if ($transactions->num_rows > 0): ?>
                <div class="transaction-list">
                    <?php 
                    $transactions->data_seek(0);
                    while($trans = $transactions->fetch_assoc()): 
                    ?>
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
                <p>No transactions found for this period.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>