<?php
// require_once 'config.php';

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
//     $name = htmlspecialchars($_POST['name']);
//     $price = htmlspecialchars($_POST['price']);
//     $discount = htmlspecialchars($_POST['discount']);
//     $category = htmlspecialchars($_POST['category']);
//     $stock = htmlspecialchars($_POST['stock']);

//     $imagePath = null;
//     if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
//         $uploadDir = 'Uploads/';
//         if (!is_dir($uploadDir)) {
//             mkdir($uploadDir, 0777, true);
//         }
//         $imagePath = $uploadDir . basename($_FILES['image']['name']);
//         move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
//     }

//     $stmt = $conn->prepare("INSERT INTO items (name, price, discount, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
//     $stmt->execute([$name, $price, $discount, $category, $stock, $imagePath]);
// }
?>
<!-- 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Cracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .admin-main {
            margin-left: 16rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="admin-container flex">
        <?php
        //  require_once './common/admin_sidebar.php'; 
         ?>
        <main class="admin-main flex-1 p-6">
            <div class="container mx-auto max-w-4xl p-6 bg-white rounded-lg shadow-lg mt-10">
                <h3 class="text-xl font-semibold text-indigo-600 mt-8 mb-4">Add New Cracker</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="text" name="name" placeholder="Name" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="number" name="price" placeholder="Price" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="number" name="discount" placeholder="Discount (%)" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="category" placeholder="Category" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="number" name="stock" placeholder="Stock" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="file" name="image" accept="image/*" class="w-full p-3 border rounded-lg">
                    <button type="submit" name="add" class="w-full bg-indigo-600 text-white p-3 rounded-lg hover:bg-indigo-700 transition-colors">Add</button>
                </form>
            </div>
        </main>
    </div> 

</body>
</html> -->


<!-- <?php
// require_once 'config.php';

// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
//     $uploadDir = 'Uploads/';
//     if (!is_dir($uploadDir)) {
//         mkdir($uploadDir, 0777, true);
//     }
//     if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
//         $csvFile = $_FILES['csv_file']['tmp_name'];
//         $handle = fopen($csvFile, 'r');
//         if ($handle !== false) {
//             $header = fgetcsv($handle);
//             while (($row = fgetcsv($handle)) !== false) {
//                 $name = htmlspecialchars($row[0]);
//                 $price = htmlspecialchars($row[1]);
//                 $discount = htmlspecialchars($row[2]);
//                 $category = htmlspecialchars($row[3]);
//                 $stock = htmlspecialchars($row[4]);
//                 $imageFileName = isset($row[5]) ? htmlspecialchars($row[5]) : null;
//                 $imagePath = null;

//                 // Check if image file is uploaded with the same name
//                 if ($imageFileName && isset($_FILES['images']) && isset($_FILES['images']['name'])) {
//                     $imageIndex = array_search($imageFileName, $_FILES['images']['name']);
//                     if ($imageIndex !== false && $_FILES['images']['error'][$imageIndex] === UPLOAD_ERR_OK) {
//                         $tmpName = $_FILES['images']['tmp_name'][$imageIndex];
//                         $imagePath = $uploadDir . basename($imageFileName);
//                         move_uploaded_file($tmpName, $imagePath);
//                     }
//                 }

//                 $stmt = $conn->prepare("INSERT INTO items (name, price, discount, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
//                 $stmt->execute([$name, $price, $discount, $category, $stock, $imagePath]);
//             }
//             fclose($handle);
//         }
//     }
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Cracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .admin-main {
            margin-left: 16rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="admin-container flex">
        <?php 
        // require_once './common/admin_sidebar.php';
         ?>
        <main class="admin-main flex-1 p-6">
            <div class="container mx-auto max-w-4xl p-6 bg-white rounded-lg shadow-lg mt-10">
                <h3 class="text-xl font-semibold text-indigo-600 mt-8 mb-4">Add New Cracker</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <label class="block text-gray-700">Upload CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required class="w-full p-3 border rounded-lg">
                    <label class="block text-gray-700">Upload Images (multiple, names must match CSV)</label>
                    <input type="file" name="images[]" accept="image/*" multiple class="w-full p-3 border rounded-lg">
                    <button type="submit" name="upload_csv" class="w-full bg-indigo-600 text-white p-3 rounded-lg hover:bg-indigo-700 transition-colors">Upload CSV & Images</button>
                </form>
            </div>
        </main>
    </div>

</body>
</html> -->
<!-- -------------------------------------------------------------------------------------------------------------------- -->
<?php
// session_start();

// ✅ Protect this page
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: admin_login.php");
//     exit();
// }

// require_once 'config.php';

// // ----------------- Single Upload -----------------
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['single_upload'])) {
//     $name = htmlspecialchars($_POST['name']);
//     $price = htmlspecialchars($_POST['price']);
//     $discount = htmlspecialchars($_POST['discount']);
//     $category = htmlspecialchars($_POST['category']);
//     $stock = htmlspecialchars($_POST['stock']);

//     $imagePath = null;
//     if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
//         $uploadDir = 'Uploads/';
//         if (!is_dir($uploadDir)) {
//             mkdir($uploadDir, 0777, true);
//         }
//         $imagePath = $uploadDir . basename($_FILES['image']['name']);
//         move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
//     }

//     $stmt = $conn->prepare("INSERT INTO items (name, price, discount, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
//     $stmt->execute([$name, $price, $discount, $category, $stock, $imagePath]);
// }

// // ----------------- Bundle Upload (CSV + Images) -----------------
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bundle_upload'])) {
//     $uploadDir = 'Uploads/';
//     if (!is_dir($uploadDir)) {
//         mkdir($uploadDir, 0777, true);
//     }

//     if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
//         $csvFile = $_FILES['csv_file']['tmp_name'];
//         $handle = fopen($csvFile, 'r');
//         if ($handle !== false) {
//             $header = fgetcsv($handle); // skip header row

//             while (($row = fgetcsv($handle)) !== false) {
//                 $name = htmlspecialchars($row[0]);
//                 $price = htmlspecialchars($row[1]);
//                 $discount = htmlspecialchars($row[2]);
//                 $category = htmlspecialchars($row[3]);
//                 $stock = htmlspecialchars($row[4]);
//                 $imageFileName = isset($row[5]) ? htmlspecialchars($row[5]) : null;
//                 $imagePath = null;

//                 // Match image from multiple upload by filename
//                 if ($imageFileName && isset($_FILES['images'])) {
//                     $imageIndex = array_search($imageFileName, $_FILES['images']['name']);
//                     if ($imageIndex !== false && $_FILES['images']['error'][$imageIndex] === UPLOAD_ERR_OK) {
//                         $tmpName = $_FILES['images']['tmp_name'][$imageIndex];
//                         $imagePath = $uploadDir . basename($imageFileName);
//                         move_uploaded_file($tmpName, $imagePath);
//                     }
//                 }

//                 $stmt = $conn->prepare("INSERT INTO items (name, price, discount, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
//                 $stmt->execute([$name, $price, $discount, $category, $stock, $imagePath]);
//             }
//             fclose($handle);
//         }
//     }
// }
?>
<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Cracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .admin-main { margin-left: 16rem; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="admin-container flex">
        <?php require_once './common/admin_sidebar.php'; ?>
        <main class="admin-main flex-1 p-6">
            <div class="container mx-auto max-w-4xl p-6 bg-white rounded-lg shadow-lg mt-10">

                <!-- Single Upload Form 
                <h3 class="text-xl font-semibold text-indigo-600 mt-4 mb-4">Single Upload</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="text" name="name" placeholder="Name" required class="w-full p-3 border rounded-lg">
                    <input type="number" name="price" placeholder="Price" required class="w-full p-3 border rounded-lg">
                    <input type="number" name="discount" placeholder="Discount (%)" class="w-full p-3 border rounded-lg">
                    <input type="text" name="category" placeholder="Category" required class="w-full p-3 border rounded-lg">
                    <input type="number" name="stock" placeholder="Stock" required class="w-full p-3 border rounded-lg">
                    <input type="file" name="image" accept="image/*" class="w-full p-3 border rounded-lg">
                    <button type="submit" name="single_upload" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700">Single Upload</button>
                </form>

                <hr class="my-8">

                <!-- Bundle Upload Form 
                <h3 class="text-xl font-semibold text-green-600 mt-4 mb-4">Bundle Upload (CSV + Images)</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <label class="block text-gray-700">Upload CSV File</label>
                    <input type="file" name="csv_file" accept=".csv" required class="w-full p-3 border rounded-lg">

                    <label class="block text-gray-700">Upload Images (multiple, names must match CSV)</label>
                    <input type="file" name="images[]" accept="image/*" multiple class="w-full p-3 border rounded-lg">

                    <button type="submit" name="bundle_upload" class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-700">Bundle Upload</button>
                </form>

            </div>
        </main>
    </div>
</body>
</html> -->
<!-- -------------------------------------------------------------- -->