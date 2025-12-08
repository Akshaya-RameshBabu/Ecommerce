<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";


// Fetch all orders
$sql = "SELECT * FROM orders ORDER BY id DESC";
$stmt = $conn->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders List</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<style>
       .admin-main {
            margin-left: 3rem;
        }
    </style>
<body class="bg-gray-100">
    <div class="admin-container flex">
        <?php require_once './common/admin_sidebar.php'; ?>
 <main class="admin-main flex-1 p-6">
            <div class="container mx-auto max-w-4xl p-6 bg-white rounded-lg shadow-lg mt-10">
                <h2 class="text-2xl font-bold text-indigo-600 mb-6">Orders</h2>
                <div class="overflow-x-auto">
    <div class="overflow-x-auto bg-white shadow-md rounded-lg">
        <table class="w-full border-collapse bg-white rounded-lg shadow-sm">
                         <thead >
                <tr class=" bg-indigo-500 text-white">
                    <th class="p-3 text-left">Order ID</th>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Mobile</th>
                    <th class="p-3 text-left">Overall Total (₹)</th>
                    <th class="p-3 text-left">Payment Status</th>
                    <th class="p-3 text-left">Order Date</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($orders as $order) { ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="p-3 border-b"><?= $order['id'] ?></td>
                    <td class="p-3 border-b"><?= $order['name'] ?></td>
                    <td class="p-3 border-b"><?= $order['mobile'] ?></td>
                    <td class="p-3 border-bfont-medium">₹<?= $order['overall_total'] ?></td>
                    
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($order['payment_status'] === 'paid') { ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                        <?php } elseif ($order['payment_status'] === 'pending') { ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                        <?php } else { ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                        <?php } ?>
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $order['order_date'] ?></td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="view_order.php?id=<?= $order['id'] ?>" class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">View</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
