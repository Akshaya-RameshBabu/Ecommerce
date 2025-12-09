<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";

// ---------------------- DELETE IMAGE ----------------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("SELECT image_path FROM carousel WHERE id=?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $file = $_SERVER["DOCUMENT_ROOT"] . "/" . $data['image_path'];
        if (file_exists($file)) unlink($file);

        $del = $conn->prepare("DELETE FROM carousel WHERE id=?");
        $del->execute([$id]);
    }

    header("Location: carousel_manager.php");
    exit;
}

// ---------------------- UPLOAD IMAGE ----------------------
if (isset($_POST['upload'])) {
    if (isset($_FILES['carousel_image']) && $_FILES['carousel_image']['error'] === 0) {
        $uploadDir = $_SERVER["DOCUMENT_ROOT"] . "/images/";
        $dbPath = "images/";

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = pathinfo($_FILES['carousel_image']['name'], PATHINFO_EXTENSION);
        $newName = uniqid("banner_", true) . "." . $ext;
        $filePath = $uploadDir . $newName;

        if (move_uploaded_file($_FILES['carousel_image']['tmp_name'], $filePath)) {
            $orderStmt = $conn->query("SELECT MAX(sort_order) AS m FROM carousel");
            $lastOrder = $orderStmt->fetch(PDO::FETCH_ASSOC)['m'] ?? 0;

            $stmt = $conn->prepare("INSERT INTO carousel (image_path, sort_order) VALUES (?, ?)");
            $stmt->execute([$dbPath . $newName, $lastOrder + 1]);
        }
    }

    header("Location: carousel_manager.php");
    exit;
}

// ---------------------- UPDATE ORDER (DRAG & DROP) ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder'])) {
    $data = json_decode($_POST['reorder'], true);
    if ($data && is_array($data)) {
        foreach ($data as $item) {
            $stmt = $conn->prepare("UPDATE carousel SET sort_order=? WHERE id=?");
            $stmt->execute([intval($item['sort_order']), intval($item['id'])]);
        }
    }
    echo json_encode(['success'=>true]);
    exit;
}

// ---------------------- FETCH IMAGES ----------------------
$stmt = $conn->prepare("SELECT * FROM carousel ORDER BY sort_order ASC");
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Carousel Manager</title>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
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
<div class="container mx-auto max-w-4xl p-6 bg-white rounded-lg shadow-lg p-6 mt-10">
<h2 class="text-2xl font-bold text-indigo-600 mt-8 mb-4">Carousel Manager</h2>

<!-- UPLOAD -->
<div class="mb-4">
    <h2 class="text-xl font-semibold mb-2">Upload New Image</h2>
    <form method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
        <input type="file" name="carousel_image" required class="border p-2 rounded">
        <button type="submit" name="upload" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-green-600">Upload</button>
    </form>
</div>

<!-- DRAG & DROP LIST -->
<h2 class="text-xl font-semibold mb-2">Reorder Carousel Images</h2>
<p class="text-gray-600 mb-4">Drag and drop the images to reorder them. Changes will be saved automatically.</p>

<ul id="carouselList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
<?php foreach ($images as $img): ?>
    <li class="bg-white p-4 rounded shadow cursor-move flex flex-col items-center" data-id="<?= $img['id'] ?>">
        <img src="../<?= $img['image_path'] ?>" class="w-full h-48 object-cover rounded mb-2">
        <a href="?delete=<?= $img['id'] ?>" class="text-red-500 hover:underline font-bold">Delete</a>
    </li>
<?php endforeach; ?>
</ul>
</div>
</main>
</div>
<script>
// Initialize SortableJS
const el = document.getElementById('carouselList');
const sortable = Sortable.create(el, {
    animation: 150,
    onEnd: function(evt) {
        const items = el.querySelectorAll('li');
        let order = [];
        items.forEach((item, index) => {
            order.push({ id: item.getAttribute('data-id'), sort_order: index + 1 });
        });

        // Send order to server via POST
        const formData = new FormData();
        formData.append('reorder', JSON.stringify(order));

        fetch('carousel_manager.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) console.log("Order updated");
        });
    }
});
</script>

</body>
</html>
