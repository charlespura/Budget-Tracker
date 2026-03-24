# 💰 Budget Tracker

A simple yet powerful personal finance management application to track income, expenses, and budget goals.
<img width="800" height="500" alt="Screenshot 2026-03-24 at 2 09 16 PM" src="https://github.com/user-attachments/assets/b1c1cc9b-54b7-4c10-afe6-81496291bc97" />
<img width="800" height="500" alt="Screenshot 2026-03-24 at 2 09 42 PM" src="https://github.com/user-attachments/assets/2ad9907a-ed91-45a3-af11-22b693104825" />




## Features

- 🔐 User registration & login
- 💵 Add income and expenses
- 📊 View balance and transaction history
- 🎯 Set budget goals with progress tracking
- 📅 Filter transactions by date
- 📱 Mobile responsive design
- 📈 Monthly spending reports

## Requirements

- PHP 7.4+
- MySQL 5.7+ (or MariaDB equivalent)
- Local web server (XAMPP/WAMP/MAMP)

## Quick Installation

### 1. Database Setup
- Create MySQL database named `budget_tracker`
- Import `sql/database.sql`

### 2. Configure Database
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'budget_tracker');
```

### 3. Run Application
Place folder in your web server root (e.g. XAMPP `htdocs`)

Access: `http://localhost/budgettracker/`

## Folder Structure

```
budgettracker/
├── config/database.php
├── css/style.css
├── includes/
│   ├── auth.php
│   ├── footer.php
│   └── header.php
├── js/script.js
├── sql/database.sql
├── add_transaction.php
├── budget_goals.php
├── dashboard.php
├── index.php
├── login.php
├── logout.php
├── register.php
└── reports.php
```

## Usage

- Register: Create a new account
- Add Transactions: Log income and expenses
- Dashboard: View balance and recent activity
- Set Goals: Create monthly budget limits
- Reports: Analyze spending by date

## Tech Stack

PHP 7.4+

MySQL

HTML5/CSS3

JavaScript

## Troubleshooting

- "Connection failed": Check MySQL is running and database credentials
- "Headers already sent": Remove spaces before `<?php` tags
- Login not working: Verify the user exists in the database

## License

MIT License - Free for personal and commercial use
