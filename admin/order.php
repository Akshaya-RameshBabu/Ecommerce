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

$billsDir = realpath(__DIR__ . '/../bills');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orders List</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; }
.admin-main { margin-left: 3rem; }
</style>
</head>
<body class="bg-gray-100">
<div class="admin-container flex">
    <?php require_once './common/admin_sidebar.php'; ?>
    <main class="admin-main flex-1 p-6">
        <div class="container mx-auto max-w-6xl p-6 bg-white rounded-lg shadow-lg mt-10 min-h-[80vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-indigo-600 mb-6">Orders</h2>
            
            <div class="mb-4">
                <input type="text" id="searchInput" placeholder="Search by order ID or enquiry number..." class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            
            <div class="overflow-x-auto bg-white shadow-md rounded-lg">
                <table class="w-full border-collapse bg-white rounded-lg shadow-sm" id="ordersTable">
                    <thead>
                        <tr class="bg-indigo-500 text-white">
                            <th class="p-3 text-left">Order ID</th>
                            <th class="p-3 text-left">Name</th>
                            <th class="p-3 text-left">Mobile</th>
                            <th class="p-3 text-left">Overall Total (₹)</th>
                            <th class="p-3 text-left">Payment Status</th>
                            <th class="p-3 text-left">Order Date</th>
                            <th class="p-3 text-left">Enquiry No</th>
                            <th class="p-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $enquiryNo = $order['enquiry_no'];
                            $pdfFile = $billsDir . "/estimate_{$enquiryNo}.pdf";
                            $pdfExists = file_exists($pdfFile);
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-3 border-b"><?= $order['id'] ?></td>
                            <td class="p-3 border-b"><?= htmlspecialchars($order['name']) ?></td>
                            <td class="p-3 border-b"><?= htmlspecialchars($order['mobile']) ?></td>
                            <td class="p-3 border-b font-medium">₹<?= number_format($order['overall_total'],2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                                <?php elseif ($order['payment_status'] === 'pending'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $order['order_date'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $enquiryNo ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="view_order.php?id=<?= $order['id'] ?>" class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">View</a>
                                <?php if ($pdfExists): ?>
                                    <a href="../bills/estimate_<?= $enquiryNo ?>.pdf" target="_blank" class="px-3 py-1 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Open</a>
                                    <a href="../bills/estimate_<?= $enquiryNo ?>.pdf" download class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Download</a>
                                <?php else: ?>
                                    <span class="px-3 py-1 text-gray-400">No PDF</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#ordersTable tbody tr');
        rows.forEach(row => {
            const orderId = row.cells[0].textContent.toLowerCase();
            const enquiryNo = row.cells[6].textContent.toLowerCase();
            row.style.display = orderId.includes(filter) || enquiryNo.includes(filter) ? '' : 'none';
        });
    });
});
</script>

</body>
</html>
