<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tracker - Manage Your Finances</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php">💰 Budget Tracker</a>
            </div>
            <ul class="nav-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="add_transaction.php">Add Transaction</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    <main class="container">