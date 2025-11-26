<?php
require_once 'vendor/autoload.php';

use Razorpay\Api\Api;

session_start();

$api = new Api($_ENV['RAZORPAY_KEY_ID'], $_ENV['RAZORPAY_KEY_SECRET']);

$orderId = $_GET['order_id'];
$paymentId = $_GET['payment_id'];
$signature = $_GET['signature'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

$attributes = [
    'razorpay_order_id' => $order['razorpay_order_id'],
    'razorpay_payment_id' => $paymentId,
    'razorpay_signature' => $signature
];

try {
    $api->utility->verifyPaymentSignature($attributes);

    $conn->prepare("UPDATE orders SET status='PAID', payment_id=? WHERE id=?")
        ->execute([$paymentId, $orderId]);

    header("Location: generate_invoice.php?order_id=$orderId");
    exit;

} catch(Exception $e){
    $conn->prepare("UPDATE orders SET status='FAILED' WHERE id=?")->execute([$orderId]);
    die("Payment Verification Failed");
}
?>
