<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";


// ----------------- Function to Delete Directory Recursively -----------------
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

// ----------------- Function to Compress Image -----------------
function compressImage($source, $destination, $quality = 25) {
    $info = getimagesize($source);
    if ($info === false) {
        return false;
    }

    $image = false;
    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    if ($image === false) {
        return false;
    }

    // Ensure destination directory exists
    $destDir = dirname($destination);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0777, true);
    }

    // Save compressed image
    $result = imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    return $result;
}


// ----------------- Single Upload -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['single_upload'])) {
    $name = trim($_POST['name']);
    $price_str = str_replace(',', '', $_POST['price']);
    $price = filter_var($price_str, FILTER_VALIDATE_FLOAT);
    $discount_str = str_replace(',', '', $_POST['discount']);
    $discount = filter_var($discount_str, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
    $category = trim($_POST['category']);
    $pieces = trim($_POST['pieces']);
    $items = trim($_POST['items']);
    $brand = trim($_POST['brand']);

    // Validate inputs
    if (empty($name) || $price === false || empty($category) || empty($pieces) || empty($items) || empty($brand)) {
        echo "<p class='text-red-500 text-center'>Error: All required fields must be filled with valid data.</p>";
        exit;
    }

    // Sanitize brand for directory safety
    $brand = preg_replace('/[^a-zA-Z0-9_]/', '_', $brand);
    if (empty($brand)) {
        $brand = 'unknown';
    }

    $imagePath = null;
    $compressedImagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'Uploads/';
        $compressedDir = 'Uploads/compressed/' ;
        $imageFileName = basename($_FILES['image']['name']);
        $imagePath = $uploadDir . $imageFileName;
        $compressedImagePath = $compressedDir . $imageFileName;

        // Create directories if they don't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!is_dir($compressedDir)) {
            mkdir($compressedDir, 0777, true);
        }

        // Move original image
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            echo "<p class='text-red-500 text-center'>Error: Failed to upload original image.</p>";
            exit;
        }

        // Compress and save image
        if (!compressImage($imagePath, $compressedImagePath, 25)) {
            echo "<p class='text-red-500 text-center'>Warning: Failed to compress image for '$name'. Using original image.</p>";
            $compressedImagePath = $imagePath; // Fallback to original if compression fails
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO items (name, price, discount, category, pieces, items, brand, image, compressed_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $discount, $category, $pieces, $items, $brand, $imagePath, $compressedImagePath]);
        echo "<p class='text-green-500 text-center'>Item uploaded successfully!</p>";
    } catch (PDOException $e) {
        echo "<p class='text-red-500 text-center'>Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        exit;
    }
}

// ----------------- Bundle Upload (CSV + Images) -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bundle_upload'])) {
    $uploadDirBase = 'Uploads/';
    $compressedDirBase = 'Uploads/compressed/';
    if (!is_dir($uploadDirBase)) {
        mkdir($uploadDirBase, 0777, true);
    }
    if (!is_dir($compressedDirBase)) {
        mkdir($compressedDirBase, 0777, true);
    }

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $csvFile = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($csvFile, 'r');
        $rowNumber = 1; // Track row number for debugging
        if ($handle !== false) {
            $header = fgetcsv($handle); // Skip header row
            $rowNumber++;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 7) {
                    echo "<p class='text-red-500 text-center'>Error: Row $rowNumber has insufficient columns. Expected at least 7, got " . count($row) . ".</p>";
                    $rowNumber++;
                    continue;
                }

                $name = trim($row[0]);
                $price_str = str_replace(',', '', $row[1]);
                $price = filter_var($price_str, FILTER_VALIDATE_FLOAT);
                $discount_str = str_replace(',', '', $row[2]);
                $discount = filter_var($discount_str, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
                $category = trim($row[3]);
                $pieces = trim($row[4]) !== '' ? trim($row[4]) : null; // Allow empty pieces
                $items = trim($row[5]);
                $brand = trim($row[6]);
                $imageFileName = isset($row[7]) ? trim($row[7]) : null;
                $imagePath = null;
                $compressedImagePath = null;

                // Validate inputs
                if (empty($name) || $price === false || empty($category) || empty($brand)) {
                    echo "<p class='text-red-500 text-center'>Error: Invalid data in row $rowNumber (name: '$name', price: '$price_str', category: '$category', pieces: '$pieces', items: '$items', brand: '$brand'). Skipping.</p>";
                    $rowNumber++;
                    continue;
                }

                // Sanitize brand for directory safety
                $brand = preg_replace('/[^a-zA-Z0-9_]/', '_', $brand);
                if (empty($brand)) {
                    $brand = 'unknown';
                }

                // Handle image upload
                if ($imageFileName && isset($_FILES['images'])) {
                    $imageIndex = array_search($imageFileName, $_FILES['images']['name']);
                    if ($imageIndex !== false && $_FILES['images']['error'][$imageIndex] === UPLOAD_ERR_OK) {
                        $uploadDir = $uploadDirBase ;
                        $compressedDir = $compressedDirBase ;
                        $imagePath = $uploadDir . basename($imageFileName);
                        $compressedImagePath = $compressedDir . basename($imageFileName);

                        // Create directories if they don't exist
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        if (!is_dir($compressedDir)) {
                            mkdir($compressedDir, 0777, true);
                        }

                        // Move original image
                        if (!move_uploaded_file($_FILES['images']['tmp_name'][$imageIndex], $imagePath)) {
                            echo "<p class='text-red-500 text-center'>Warning: Failed to upload original image '$imageFileName' for row $rowNumber.</p>";
                        } else {
                            // Compress and save image
                            if (!compressImage($imagePath, $compressedImagePath, 25)) {
                                echo "<p class='text-red-500 text-center'>Warning: Failed to compress image '$imageFileName' for row $rowNumber. Using original image.</p>";
                                $compressedImagePath = $imagePath; // Fallback to original
                            }
                        }
                    } else {
                        echo "<p class='text-red-500 text-center'>Warning: Image '$imageFileName' not found or failed to upload for row $rowNumber.</p>";
                    }
                }

                try {
                    $stmt = $conn->prepare("INSERT INTO items (name, price, discount, category, pieces, items, brand, image, compressed_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $price, $discount, $category, $pieces, $items, $brand, $imagePath, $compressedImagePath]);
                } catch (PDOException $e) {
                    echo "<p class='text-red-500 text-center'>Error in row $rowNumber: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
                }
                $rowNumber++;
            }
            fclose($handle);
            echo "<p class='text-green-500 text-center'>Bundle upload completed!</p>";
        } else {
            echo "<p class='text-red-500 text-center'>Error: Unable to open CSV file.</p>";
        }
    } else {
        echo "<p class='text-red-500 text-center'>Error: No CSV file uploaded or upload failed.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add items</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/config.js"></script>
    <script src="/cart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="/Styles.css" rel="stylesheet">
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
            <hr class="my-8">

            <div class="container mx-auto max-w-4xl p-6 bg-white rounded-lg shadow-lg mt-10">
                <!-- Single Upload Form -->
                <h3 class="text-xl font-semibold text-indigo-600 mt-4 mb-4">Single Upload</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="text" name="name" placeholder="Name" required
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                         <!-- Category Dropdown -->
        <div>
            <label class="font-semibold">Select Category</label>

            <div class="flex gap-3">
                <select id="categorySelect" name="category_id" class="w-full p-2 border rounded" required></select>

                <button type="button" onclick="openCategoryModal()" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg">
                   <i class="fas fa-plus"></i> 
                </button>
            </div>
        </div>

        <!-- Brand Dropdown -->
        <div>
            <label class="font-semibold">Select Brand</label>

            <div class="flex gap-3">
                <select id="brandSelect" name="brand_id" class="w-full p-2 border rounded" required></select>

                <button type="button" onclick="openBrandModal()" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg">
                 <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
                    <input type="text" name="price" placeholder="Price" required
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="discount" placeholder="Discount (%)"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="stock" placeholder="Stock"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="weight" placeholder="Weight"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="Packaging_type" placeholder="Packaging Type"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="product_form" placeholder="Product form"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="origin" placeholder="Origin (country)"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="grade" placeholder="Grade"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="puriy" placeholder="Purity"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="floavour" placeholder="Flavour"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="description" placeholder="Description"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="nutrition" placeholder="Nutrition"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="self_life" placeholder="Self Life (best before)"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="storage_instruction" placeholder="Storage Instruction"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="category" placeholder="Category" required
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="pieces" placeholder="Pieces"
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="items" placeholder="Items" required
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" name="brand" placeholder="Brand" required
                        class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="file" name="image" accept="image/*" class="w-full p-3 border rounded-lg">
                    <button type="submit" name="single_upload"
                        class="w-full bg-indigo-600 text-white p-3 rounded-lg hover:bg-indigo-700 transition-colors">Single
                        Upload</button>
                </form>

                <hr class="my-8">

                <!-- Bundle Upload Form -->
                <h3 class="text-xl font-semibold text-green-600 mt-4 mb-4">Bundle Upload (CSV + Images)</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <label class="block text-gray-700">Upload CSV File (Format:
                        name,price,discount,category,pieces,items,brand,image_filename)</label>
                    <input type="file" name="csv_file" accept=".csv" required class="w-full p-3 border rounded-lg">
                    <label class="block text-gray-700">Upload Images (multiple, names must match CSV)</label>
                    <input type="file" name="images[]" accept="image/*" multiple class="w-full p-3 border rounded-lg">
                    <button type="submit" name="bundle_upload"
                        class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition-colors">Bundle
                        Upload</button>
                </form>


            </div>
        </main>
    </div>

<!-- CATEGORY MODAL -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-xl w-96">
        <h2 class="text-xl font-bold mb-4 text-green-700">Add Category</h2>

        <input id="newCategoryName" type="text" class="w-full p-2 border rounded mb-3" placeholder="Category Name">

        <button onclick="saveCategory()" class="w-full py-2 bg-green-600 text-white rounded">Save</button>
        <button onclick="closeCategoryModal()" class="w-full mt-2 py-2 border rounded">Cancel</button>
    </div>
</div>

<!-- BRAND MODAL -->
<div id="brandModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-xl w-96">
        <h2 class="text-xl font-bold mb-4 text-green-700">Add Brand</h2>

        <input id="newBrandName" type="text" class="w-full p-2 border rounded mb-3" placeholder="Brand Name">

        <button onclick="saveBrand()" class="w-full py-2 bg-green-600 text-white rounded">Save</button>
        <button onclick="closeBrandModal()" class="w-full mt-2 py-2 border rounded">Cancel</button>
    </div>
</div>


    <script>
/* -------------------- FETCH DROPDOWN DATA -------------------- */

function loadCategories() {
    fetch(BASE_URL +"/api/fetch_categories.php")
        .then(res => res.json())
        .then(data => {
            let html = "";
            data.forEach(c => {
                html += `<option value="${c.id}">${c.name}</option>`;
            });
            document.getElementById("categorySelect").innerHTML = html;
        });
}

function loadBrands() {
    console.log("Loading brands...");
    fetch(BASE_URL +"/api/fetch_brands.php")
        .then(res => res.json())
        .then(data => {
            let html = "";
            data.forEach(b => {
                html += `<option value="${b.id}">${b.name}</option>`;
                console.log(b);
            });
            document.getElementById("brandSelect").innerHTML = html;
        });
}

loadCategories();
loadBrands();

/* -------------------- CATEGORY MODAL -------------------- */
function openCategoryModal() {
    document.getElementById("categoryModal").classList.remove("hidden");
}
function closeCategoryModal() {
    document.getElementById("categoryModal").classList.add("hidden");
}
function saveCategory() {
    let name = document.getElementById("newCategoryName").value;

    fetch(BASE_URL +"/api/add_category.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "name=" + name
    }).then(() => {
        showToastMessage("Category added successfully!");
        closeCategoryModal();
        loadCategories();
    });
}

/* -------------------- BRAND MODAL -------------------- */
function openBrandModal() {
    document.getElementById("brandModal").classList.remove("hidden");
}
function closeBrandModal() {
    document.getElementById("brandModal").classList.add("hidden");
}
function saveBrand() {
    let name = document.getElementById("newBrandName").value;

    fetch(BASE_URL +"/api/add_brand.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "name=" + name
    }).then(() => {
          showToastMessage(" New Brand added successfully!");
        closeBrandModal();
        loadBrands();
    });
}
</script>
   <div id="toast-container"></div>
</body>

</html>