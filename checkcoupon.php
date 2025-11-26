<?php
require_once __DIR__ . "/includes/env.php";

header('Content-Type: application/json');

// Database connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'diwali_db';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $couponCode = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
    if (!$couponCode) {
        echo json_encode(['success' => false, 'message' => 'No coupon code provided']);
        exit;
    }

    // Convert to lowercase for case-insensitive comparison
    $couponCode = strtolower($couponCode);

    // Current date and time (Hardcoded for testing; replace with NOW() or PHP date if needed)
   $currentDate = date("Y-m-d");

    // Use LOWER() in SQL to ensure case-insensitive matching
    $stmt = $conn->prepare("
        SELECT discount_percent 
        FROM coupons 
        WHERE LOWER(code) = ? 
          AND is_active = 1 
          AND expiry_date >= ? 
        LIMIT 1
    ");
    $stmt->execute([$couponCode, $currentDate]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        echo json_encode(['success' => true, 'discount_percent' => $coupon['discount_percent']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code']);
    }
} catch (PDOException $e) {
    error_log("Error checking coupon: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error validating coupon']);
}
?>
