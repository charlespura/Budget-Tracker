<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $conn = getConnection();
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Add default categories for this user
                $default_categories = [
                    ['Allowance', 'income', '💰'],
                    ['Part-time Job', 'income', '💼'],
                    ['Food', 'expense', '🍔'],
                    ['Transport', 'expense', '🚗'],
                    ['School', 'expense', '📚'],
                    ['Bills', 'expense', '💡'],
                    ['Entertainment', 'expense', '🎮'],
                    ['Shopping', 'expense', '🛍️']
                ];
                
                $stmt_cat = $conn->prepare("INSERT INTO categories (user_id, name, type, icon) VALUES (?, ?, ?, ?)");
                foreach ($default_categories as $cat) {
                    $stmt_cat->bind_param("isss", $user_id, $cat[0], $cat[1], $cat[2]);
                    $stmt_cat->execute();
                }
                $stmt_cat->close();
                
                $success = 'Registration successful! You can now login.';
                $_POST = []; // Clear form
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
        $conn->close();
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Register</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username (min. 3 characters)</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password (min. 6 characters)</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
        
        <p class="auth-link">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>