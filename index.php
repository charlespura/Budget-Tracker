<?php
// Root file - Main entry point
require_once 'config/database.php';

// Redirect to dashboard if logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Include header
include 'includes/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>💰 Budget Tracker</h1>
        <p>Track your income and expenses easily</p>
        <div class="hero-buttons">
            <a href="login.php" class="btn btn-primary">Login</a>
            <a href="register.php" class="btn btn-secondary">Register</a>
        </div>
    </div>
</div>

<div class="features-section">
    <h2>Features</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Track Expenses</h3>
            <p>Log all your expenses and income in one place</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📈</div>
            <h3>View Balance</h3>
            <p>See your current balance at a glance</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Set Goals</h3>
            <p>Create budget goals and track progress</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📱</div>
            <h3>Mobile Ready</h3>
            <p>Access from any device with responsive design</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>