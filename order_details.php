<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    $_SESSION["redirect_after_login"] = "my_orders.php";
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];

// Get order ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid order ID");
}
$orderId = (int)$_GET['id'];

// Fetch order details
$sqlOrder = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->execute([$orderId, $userId]);
$order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found");
}

// Fetch order items with product images
$sqlItems = "SELECT oi.*, i.image 
             FROM order_items oi
             LEFT JOIN items i ON oi.product_id = i.id
             WHERE oi.order_id = ?";
$stmtItems = $conn->prepare($sqlItems);
$stmtItems->execute([$orderId]);
$orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order #<?= $order['id'] ?> Details</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="/toast.js"></script>
<style>
     .payment_badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 9999px; /* fully rounded pill */
    font-size: 13px;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    transition: transform 0.2s, box-shadow 0.2s;
}

/* Status colors with subtle gradient */
.paid {
    background: linear-gradient(135deg, #a7f3d0, #49b771ff);
     color: #065f46;
}
.pending {
    background: linear-gradient(135deg, #fef08a, #eab308);
        color: #78350f;
}
.failed {
    background: linear-gradient(135deg, #fecaca, #dc2626);
    color: #7f1d1d;
}

/* Hover effect */
.payment_badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>
</head>
<body>
   <?php include "includes/header.php"; ?>
<div class=" bg-white p-8 m-5 rounded-2xl shadow-xl">
    <h1 class="text-3xl font-bold mb-6 text-green-800">Order #<?= $order['id'] ?></h1>

    <!-- Order Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="space-y-2">
            <p><span class="font-semibold">Order Date:</span> <?= date("d M Y, h:i A", strtotime($order['order_date'])) ?></p>
            <p><span class="font-semibold">Name:</span> <?= htmlspecialchars($order['name']) ?></p>
            <p><span class="font-semibold">Email:</span> <?= htmlspecialchars($order['email']) ?></p>
            <p><span class="font-semibold">Mobile:</span> <?= htmlspecialchars($order['mobile']) ?></p>
            <p><span class="font-semibold">Address:</span> <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?></p>
        </div>
        <div class="space-y-2">
            <p><span class="font-semibold">Payment Status:</span> 
                <span class="payment_badge <?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span>
            </p>
           <?php if($order['payment_status'] === 'paid'): ?>
    <p class="flex items-center space-x-2">
        <span class="font-semibold">Payment ID:</span> 
        <code id="paymentId" class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($order['razorpay_payment_id']) ?></code>
        <i class="fa-regular fa-copy cursor-pointer text-gray-600 hover:text-gray-800" title="Copy Payment ID" onclick="copyPaymentId()"></i>
    </p>
<?php endif; ?>
        </div>
    </div>

    <!-- Items -->
    <h2 class="text-2xl font-semibold text-green-700 mb-4">Items</h2>
    <div class="space-y-4">
        <?php foreach ($orderItems as $item): ?>
            <div class="flex flex-col md:flex-row items-center md:items-start border p-4 rounded-xl shadow-sm hover:shadow-md transition">
                <img src="admin/<?= htmlspecialchars($item['image'] ?? 'no-image.png') ?>" 
                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                     class="w-24 h-24 md:w-28 md:h-28 object-cover rounded-lg mb-3 md:mb-0 md:mr-6">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-green-800"><?= htmlspecialchars($item['product_name']) ?></h3>
                    <p class="text-gray-600">Price: ₹<?= number_format($item['price'],2) ?> | Discount: <?= $item['discount'] ?>%</p>
                    <p class="text-gray-600">Qty: <?= $item['qty'] ?> | Amount: ₹<?= number_format($item['amount'],2) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Totals -->
   <div class="mt-8 border-t pt-6 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
    <div class="space-y-2">
        <p><span class="font-semibold">Subtotal:</span> ₹<?= number_format($order['subtotal'],2) ?></p>
        <p><span class="font-semibold">Packing Charge:</span> ₹<?= number_format($order['packing_charge'],2) ?></p>
        <p><span class="font-semibold">Net Total:</span> ₹<?= number_format($order['net_total'],2) ?></p>
        <p class="text-xl font-bold"><span class="font-semibold">Overall Total:</span> ₹<?= number_format($order['overall_total'],2) ?></p>
        <?php if(!empty($order['coupon_code'])): ?>
            <p><span class="font-semibold">Coupon:</span> <?= htmlspecialchars($order['coupon_code']) ?> (-₹<?= number_format($order['coupon_discount_amount'],2) ?>)</p>
        <?php endif; ?>
    </div>

    <a href="download_bill.php?order_id=<?= $order['id'] ?>" 
       class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
       target="_blank">Download Invoice</a>
</div>
</div>
<?php require_once 'includes/footer.php'; ?>
<script>
function copyPaymentId() {
    const paymentId = document.getElementById('paymentId').innerText;
    navigator.clipboard.writeText(paymentId).then(() => {
        showToast('Payment ID copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}
</script>
</body>

</html>
