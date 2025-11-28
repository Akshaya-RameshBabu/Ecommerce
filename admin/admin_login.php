<?php
session_start();
require_once __DIR__ . '../../includes/env.php';

// --- Database Configuration ---
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'diwali_db';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? ""; // Ensure this variable is used for DB connection

$error = null;

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set character set to UTF8
    $conn->exec("set names utf8");
} catch (PDOException $e) {
    // In a production environment, avoid echoing the full error message
    // echo "Connection failed: " . $e->getMessage();
    $error = "Database connection failed."; 
    // You might want to log the full error instead
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Sanitize input
    $adminUsername = trim($_POST['username'] ?? '');
    $adminPassword = trim($_POST['password'] ?? '');

    // 1. Fetch user data (specifically the hashed password) from DB
    $stmt = $conn->prepare("SELECT username, password FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$adminUsername]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Check if a user was found AND verify the password
    if ($row && password_verify($adminPassword, $row['password'])) {
        
        // --- Authentication Success ---
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $row['username']; // Use the username fetched from DB
        session_write_close();
        // Redirect to the dashboard
        header('Location: ManageCrackers.php');
        exit;
        
    } else {
        // --- Authentication Failure ---
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
</head>
<body>
    <div style="padding-top: 10%;">
    <div class="container" style="max-width: 400px; margin: 50px auto; background-color: #d7f7cdff; border: 1px solid #ddd; border-radius: 5px; padding:50px;">
        <h2>Admin Login</h2>
        <?php if (isset($error)): ?>
            <p class="error" style="color: red; text-align: center; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" style="display: flex; flex-direction: column;">
            <label style="margin-bottom: 10px;">Username:</label>
            <input type="text" name="username" required style="padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <label style="margin-bottom: 10px;">Password:</label>
            <input type="password" name="password" required style="padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <button type="submit" style="padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Login</button>
        </form>
    </div>
    </div>
</body>
</html>