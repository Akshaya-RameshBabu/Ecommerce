<?php
require_once 'vendor/autoload.php';
require_once __DIR__ . "/includes/env.php";

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---------------- DB Connection ------------------
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'diwali_db';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['order_id'])) {
        die("<p style='color:red;'>Error: Order ID missing.</p>");
    }

    $orderId = intval($_GET['order_id']);

    // ---------------- Fetch Order ------------------
    $orderStmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("<p style='color:red;'>Order not found.</p>");
    }

    $customerName     = $order['customer_name'];
    $customerMobile   = $order['customer_mobile'];
    $customerEmail    = $order['customer_email'];
    $customerState    = $order['customer_state'];
    $customerCity     = $order['customer_city'];
    $customerAddress  = $order['customer_address'];
    $orderedDateTime  = $order['ordered_date'];

    $enquiryNumber    = $order['invoice_number']; // already stored after Razorpay success
    $packingpercent   = $order['packing_percent'] ?? 3;

    // ---------------- Fetch Items ------------------
    $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$orderId]);
    $itemsBought = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$itemsBought) {
        die("<p style='color:red;'>No items found for this order.</p>");
    }

    // ---------------- Fetch Admin ------------------
    $adminStmt = $conn->query("SELECT name, phone, email, shopaddress FROM admin_details LIMIT 1");
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

    $adminName     = $admin['name'] ?? 'Store Name';
    $adminMobile   = $admin['phone'] ?? '0000000000';
    $adminEmail    = $admin['email'] ?? 'example@gmail.com';
    $adminAddress  = $admin['shopaddress'] ?? 'Address not set';

    // ---------------- Settings ------------------
    $gstRate = 0;
    $rowsHtml = "";
    $subtotal = $totalDiscount = $netTotal = 0;

    foreach ($itemsBought as $index => $item) {

        $gross = $item['price'];
        $discPercent = $item['discount'];
        $discountAmount = ($gross * $discPercent) / 100;
        $net = $gross - $discountAmount;
        $amount = $net * $item['quantity'];

        $subtotal += $gross * $item['quantity'];
        $totalDiscount += $discountAmount * $item['quantity'];
        $netTotal += $amount;

        $rowsHtml .= "<tr>
            <td>".($index+1)."</td>
            <td class='left'>".$item['product_name']."</td>
            <td>".number_format($gross,2)."</td>
            <td>".number_format($discountAmount,2)." (".$discPercent."%)</td>
            <td>".number_format($net,2)."</td>
            <td>".$item['quantity']."</td>
            <td>".number_format($amount,2)."</td>
        </tr>";
    }

    $packingcharge = ($netTotal * $packingpercent) / 100;
    $overallTotal  = $netTotal + $packingcharge;

    // ---------------- Generate Invoice HTML ------------------
    $html = "
    <html><head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width:100%; border-collapse: collapse; }
        th,td { border:1px solid black; padding:6px; }
        th{ font-weight:bold; }
        .left{text-align:left;}
        .right{text-align:right;}
        .center{text-align:center;}
        .no-border td{ border:none!important; }
    </style>
    </head><body>

    <h2 style='text-align:center;'>Invoice #$enquiryNumber</h2>

    <table class='no-border'>
        <tr><td><strong>Customer:</strong> $customerName</td></tr>
        <tr><td><strong>Mobile:</strong> $customerMobile</td></tr>
        <tr><td><strong>Email:</strong> $customerEmail</td></tr>
        <tr><td><strong>Address:</strong> $customerAddress, $customerCity, $customerState</td></tr>
        <tr><td><strong>Date:</strong> $orderedDateTime</td></tr>
    </table>

    <br>
    <table>
        <tr>
            <th>S.No</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Disc (₹/% )</th>
            <th>Final Price</th>
            <th>Qty</th>
            <th>Total</th>
        </tr>
        $rowsHtml
        <tr><td colspan='6' class='right'><strong>Subtotal</strong></td><td><strong>".number_format($subtotal,2)."</strong></td></tr>
        <tr><td colspan='6' class='right'>Discount</td><td>- ".number_format($totalDiscount,2)."</td></tr>
        <tr><td colspan='6' class='right'>Packing Charge (3%)</td><td>".number_format($packingcharge,2)."</td></tr>
        <tr><td colspan='6' class='right'><strong>Grand Total</strong></td><td><strong>".number_format($overallTotal,2)."</strong></td></tr>
    </table>
    </body></html>
    ";

    // ---------------- Generate PDF ------------------
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();

    $filePath = __DIR__."/bills/invoice_$orderId.pdf";
    file_put_contents($filePath, $dompdf->output());

    echo "<h3>Invoice Ready</h3>";
    echo "<a href='/bills/invoice_$orderId.pdf' download>Download Invoice</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: ".$e->getMessage()."</p>";
}
?>
