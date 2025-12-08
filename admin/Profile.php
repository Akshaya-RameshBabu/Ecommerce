<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $id = htmlspecialchars($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->execute([$id]);
}

$admins = $conn->query("SELECT id, username FROM admin_users")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
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
                <h2 class="text-2xl font-bold text-indigo-600 mb-6">Manage Admin Users</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse bg-white rounded-lg shadow-sm">
                        <thead>
                            <tr class="bg-indigo-500 text-white">
                                <th class="p-3 text-left">ID</th>
                                <th class="p-3 text-left">Username</th>
                                <th class="p-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($admin['id']); ?></td>
                                    <td class="p-3 border-b"><?php echo htmlspecialchars($admin['username']); ?></td>
                                    <td class="p-3 border-b">
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                            <button type="submit" name="delete_admin" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition-colors">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>