<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/database.php';
require_once 'includes/auth.php';

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get current month as YYYY-MM
$current_month = date('Y-m');
$current_month_date = $current_month . '-01'; // For database queries

// Get expense categories
$categories_query = "SELECT * FROM categories WHERE user_id = ? AND type = 'expense' ORDER BY name";
$stmt = $conn->prepare($categories_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $goal_id = intval($_POST['goal_id']);
        $delete_query = "DELETE FROM budget_goals WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $goal_id, $user_id);
        if ($stmt->execute()) {
            $success = 'Budget goal deleted successfully!';
        } else {
            $error = 'Failed to delete budget goal.';
        }
    } else {
        $category_id = intval($_POST['category_id']);
        $amount = floatval($_POST['amount']);
        $month = $_POST['month'] ?? $current_month;
        $month_date = $month . '-01'; // Convert to full date
        
        if ($amount <= 0) {
            $error = 'Please enter a valid amount';
        } else {
            // Check if goal exists
            $check_query = "SELECT id FROM budget_goals WHERE user_id = ? AND category_id = ? AND month = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("iis", $user_id, $category_id, $month_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing goal
                $update_query = "UPDATE budget_goals SET amount = ? WHERE user_id = ? AND category_id = ? AND month = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("diis", $amount, $user_id, $category_id, $month_date);
                if ($stmt->execute()) {
                    $success = 'Budget goal updated successfully!';
                } else {
                    $error = 'Failed to update budget goal.';
                }
            } else {
                // Insert new goal
                $insert_query = "INSERT INTO budget_goals (user_id, category_id, amount, month) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iids", $user_id, $category_id, $amount, $month_date);
                if ($stmt->execute()) {
                    $success = 'Budget goal created successfully!';
                } else {
                    $error = 'Failed to create budget goal.';
                }
            }
        }
    }
}

// Get existing goals for current month
$goals_query = "SELECT bg.*, c.name as category_name, c.icon,
                COALESCE(SUM(t.amount), 0) as spent
                FROM budget_goals bg
                JOIN categories c ON bg.category_id = c.id
                LEFT JOIN transactions t ON t.category_id = c.id 
                    AND t.user_id = bg.user_id 
                    AND t.type = 'expense'
                    AND MONTH(t.transaction_date) = MONTH(bg.month)
                    AND YEAR(t.transaction_date) = YEAR(bg.month)
                WHERE bg.user_id = ? AND bg.month = ?
                GROUP BY bg.id
                ORDER BY c.name";
$stmt = $conn->prepare($goals_query);
$stmt->bind_param("is", $user_id, $current_month_date);
$stmt->execute();
$goals = $stmt->get_result();

include 'includes/header.php';
?>

<div class="budget-goals">
    <h1>Budget Goals</h1>
    <p>Set monthly spending limits for different categories</p>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="dashboard-grid">
        <div class="card">
            <h2>Set New Goal</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php 
                        if ($categories && $categories->num_rows > 0) {
                            $categories->data_seek(0);
                            while($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php 
                            endwhile;
                        } 
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Monthly Budget Limit (₱)</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="month">Month</label>
                    <input type="month" id="month" name="month" value="<?php echo $current_month; ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Set Budget Goal</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Current Goals (<?php echo date('F Y', strtotime($current_month . '-01')); ?>)</h2>
            <?php if ($goals && $goals->num_rows > 0): ?>
                <div class="goals-list">
                    <?php while($goal = $goals->fetch_assoc()): 
                        $percentage = ($goal['spent'] / $goal['amount']) * 100;
                        $status_class = $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success');
                    ?>
                        <div class="goal-item">
                            <div class="goal-header">
                                <span class="goal-icon"><?php echo $goal['icon']; ?></span>
                                <span class="goal-name"><?php echo htmlspecialchars($goal['category_name']); ?></span>
                                <form method="POST" action="" style="margin: 0;">
                                    <input type="hidden" name="goal_id" value="<?php echo $goal['id']; ?>">
                                    <button type="submit" name="delete" class="btn-delete" onclick="return confirm('Are you sure you want to delete this goal?');">🗑️</button>
                                </form>
                            </div>
                            <div class="goal-amounts">
                                <span>Budget: ₱<?php echo number_format($goal['amount'], 2); ?></span>
                                <span>Spent: ₱<?php echo number_format($goal['spent'], 2); ?></span>
                                <span class="remaining">Remaining: ₱<?php echo number_format($goal['amount'] - $goal['spent'], 2); ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $status_class; ?>" style="width: <?php echo min($percentage, 100); ?>%"></div>
                            </div>
                            <div class="goal-status <?php echo $status_class; ?>">
                                <?php if ($percentage >= 100): ?>
                                    ⚠️ Over budget!
                                <?php elseif ($percentage >= 80): ?>
                                    ⚠️ Approaching limit
                                <?php else: ?>
                                    ✓ On track
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No budget goals set for this month. Create your first goal!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.goals-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.goal-item {
    background: var(--light-color);
    padding: 1rem;
    border-radius: 8px;
}

.goal-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.goal-icon {
    font-size: 1.2rem;
}

.goal-name {
    flex: 1;
    font-weight: bold;
}

.btn-delete {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    opacity: 0.7;
}

.btn-delete:hover {
    opacity: 1;
}

.goal-amounts {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

.remaining {
    font-weight: bold;
    color: var(--primary-color);
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.progress-fill.success {
    background: var(--success-color);
}

.progress-fill.warning {
    background: var(--warning-color);
}

.progress-fill.danger {
    background: var(--danger-color);
}

.goal-status {
    font-size: 0.85rem;
    text-align: right;
}

.goal-status.success {
    color: var(--success-color);
}

.goal-status.warning {
    color: var(--warning-color);
}

.goal-status.danger {
    color: var(--danger-color);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>