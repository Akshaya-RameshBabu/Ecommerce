<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $gstRate = htmlspecialchars($_POST['gst_rate']);
    $discount = htmlspecialchars($_POST['discount']);
    $stmt = $conn->prepare("UPDATE settings SET gst_rate = ?, discount = ? WHERE id = 1");
    $stmt->execute([$gstRate, $discount]);
}

$settings = $conn->query("SELECT gst_rate, discount FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .admin-main {
            margin-left: 3rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="admin-container flex">
        <?php require_once './common/admin_sidebar.php'; ?>
        <main class="admin-main flex-1 p-6">
            <div class="container mx-auto max-w-4xl p-6 bg-white rounded-lg shadow-lg mt-10">
                <h2 class="text-2xl font-bold text-indigo-600 mb-4">Update Settings</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="gst_rate" class="block text-sm font-medium text-gray-700">GST Rate (%):</label>
                        <input type="number" name="gst_rate" id="gst_rate" value="<?php echo htmlspecialchars($settings['gst_rate']); ?>" step="0.01" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="discount" class="block text-sm font-medium text-gray-700">Discount (%):</label>
                        <input type="number" name="discount" id="discount" value="<?php echo htmlspecialchars($settings['discount']); ?>" step="0.01" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button type="submit" name="update_settings" class="w-full bg-indigo-600 text-white p-3 rounded-lg hover:bg-indigo-700 transition-colors">Update Settings</button>
                </form>
            </div>
        </main>
    </div>

</body>
</html>