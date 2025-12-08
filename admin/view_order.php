<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";


if (!isset($_GET['id'])) {
    die("Order ID missing!");
}

$order_id = intval($_GET['id']);

// Fetch order (PDO)
$order_sql = "SELECT * FROM orders WHERE id = :id";
$stmt = $conn->prepare($order_sql);
$stmt->execute(['id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found!");
}

// Fetch all items
$items_sql = "SELECT * FROM order_items WHERE order_id = :id ORDER BY id ASC";
$stmt_items = $conn->prepare($items_sql);
$stmt_items->execute(['id' => $order_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?= $order_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .admin-main { margin-left: 3rem; } /* Adjust if sidebar exists */
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="admin-container flex">
    <?php require_once './common/admin_sidebar.php'; ?>

    <main class="admin-main flex-1 p-6">
        <div class="container mx-auto max-w-5xl">
            <!-- Order Details Card -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-indigo-600 mb-4">Order Details - #<?= $order['id'] ?></h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 text-gray-700">
                    <p><span class="font-semibold">Name:</span> <?= $order['name'] ?></p>
                    <p><span class="font-semibold">Mobile:</span> <?= $order['mobile'] ?></p>
                    <p><span class="font-semibold">Email:</span> <?= $order['email'] ?></p>
                    <p><span class="font-semibold">Address:</span> <?= $order['address'] ?></p>
                    <p><span class="font-semibold">State:</span> <?= $order['state'] ?></p>
                    <p><span class="font-semibold">City:</span> <?= $order['city'] ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 text-gray-700">
                    <p><span class="font-semibold">Subtotal:</span> ₹<?= $order['subtotal'] ?></p>
                    <p><span class="font-semibold">Packing Charge:</span> ₹<?= $order['packing_charge'] ?></p>
                    <p><span class="font-semibold">Net Total:</span> ₹<?= $order['net_total'] ?></p>
                    <p><span class="font-semibold">Overall Total:</span> ₹<?= $order['overall_total'] ?></p>
                    <p><span class="font-semibold">Coupon Code:</span> <?= $order['coupon_code'] ?></p>
                    <p><span class="font-semibold">Coupon Discount:</span> ₹<?= $order['coupon_discount_amount'] ?></p>
                    <p><span class="font-semibold">Order Date:</span> <?= $order['order_date'] ?></p>
                    <p>
                        <span class="font-semibold">Payment Status:</span>
                        <?php if ($order['payment_status'] === 'paid'): ?>
                            <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 font-semibold text-sm">Paid</span>
                        <?php elseif ($order['payment_status'] === 'pending'): ?>
                            <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 font-semibold text-sm">Pending</span>
                        <?php else: ?>
                            <span class="px-2 py-1 rounded-full bg-red-100 text-red-800 font-semibold text-sm">Failed</span>
                        <?php endif; ?>
                    </p>
                </div>

     <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-gray-700">
    <p class="break-words"><span class="font-semibold">Razorpay Order ID:</span> <?= $order['razorpay_order_id'] ?></p>
    <p class="break-words"><span class="font-semibold">Razorpay Payment ID:</span> <?= $order['razorpay_payment_id'] ?></p>
    <p class="break-words"><span class="font-semibold">Razorpay Signature:</span> <?= $order['razorpay_signature'] ?></p>
</div>

            </div>

            <!-- Order Items Table -->
            <div class="bg-white shadow-lg rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (₹)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount (₹)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discounted Price (₹)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $i = 1;
                        $grand_total = 0;
                        foreach ($items as $item): 
                            $grand_total += $item['amount'];
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $i++ ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $item['product_name'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">₹<?= $item['price'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">₹<?= $item['discount'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">₹<?= $item['discounted_price'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $item['qty'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800">₹<?= $item['amount'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="bg-gray-100 font-semibold">
                            <td colspan="6" class="px-6 py-4 text-right text-gray-800">Total Amount:</td>
                            <td class="px-6 py-4 text-sm text-gray-800">₹<?= number_format($grand_total, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>
