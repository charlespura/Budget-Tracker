<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get categories for dropdown
$categories_query = "SELECT * FROM categories WHERE user_id = ? ORDER BY type, name";
$stmt = $conn->prepare($categories_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$categories = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'];
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description']);
    $transaction_date = $_POST['transaction_date'];
    
    if ($amount <= 0) {
        $error = 'Please enter a valid amount';
    } elseif (empty($transaction_date)) {
        $error = 'Please select a date';
    } else {
        $insert_query = "INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iidsss", $user_id, $category_id, $amount, $type, $description, $transaction_date);
        
        if ($stmt->execute()) {
            $success = 'Transaction added successfully!';
            $_POST = []; // Clear form
            // Refresh categories list
            $stmt = $conn->prepare($categories_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $categories = $stmt->get_result();
        } else {
            $error = 'Failed to add transaction. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Add Transaction</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="type">Transaction Type</label>
                <select id="type" name="type" required onchange="filterCategories()">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <?php 
                    $categories->data_seek(0);
                    while($cat = $categories->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" data-type="<?php echo $cat['type']; ?>">
                            <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="amount">Amount (₱)</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" 
                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="transaction_date">Date</label>
                <input type="date" id="transaction_date" name="transaction_date" 
                       value="<?php echo htmlspecialchars($_POST['transaction_date'] ?? date('Y-m-d')); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description (Optional)</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Add Transaction</button>
        </form>
    </div>
</div>

<script>
function filterCategories() {
    const type = document.getElementById('type').value;
    const categorySelect = document.getElementById('category_id');
    const options = categorySelect.options;
    
    for (let i = 0; i < options.length; i++) {
        const option = options[i];
        if (option.getAttribute('data-type') === type) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
    
    // Select first visible option
    for (let i = 0; i < options.length; i++) {
        if (options[i].style.display !== 'none') {
            categorySelect.selectedIndex = i;
            break;
        }
    }
}

// Initial filter
filterCategories();
</script>

<?php include 'includes/footer.php'; ?>