 <?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bundle_upload'])) {

    $uploadDirBase = 'Uploads/';
    $compressedDirBase = 'Uploads/compressed/';

    if (!is_dir($uploadDirBase)) mkdir($uploadDirBase, 0777, true);
    if (!is_dir($compressedDirBase)) mkdir($compressedDirBase, 0777, true);

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo "<p class='text-red-500 text-center'>Error: No CSV file uploaded.</p>";
        exit;
    }

    $csvFile = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($csvFile, 'r');
    if ($handle === false) {
        echo "<p class='text-red-500 text-center'>Error: Unable to read CSV file.</p>";
        exit;
    }

    $header = fgetcsv($handle); // skip header
    $rowNumber = 2;

    while (($row = fgetcsv($handle)) !== false) {

        // Ensure CSV has minimum 8 columns
        if (count($row) < 8) {
            echo "<p class='text-red-500 text-center'>Error: Row $rowNumber has insufficient columns.</p>";
            $rowNumber++;
            continue;
        }

        // Extract CSV values
        $name            = trim($row[0]);
        $price           = floatval($row[1]);
        $discount        = floatval($row[2]);
        $category_id     = intval($row[3]);
        $brand_id        = intval($row[4]);
        $stock           = intval($row[5]);
        $description     = trim($row[6]);
        $imageFileName   = trim($row[7]); // CSV image filename

        // Additional fields if required
        $weight = $row[8] ?? null;
        $packaging_type = $row[9] ?? null;
        $product_form = $row[10] ?? null;
        $origin = $row[11] ?? null;
        $grade = $row[12] ?? null;
        $purity = $row[13] ?? null;
        $flavor = $row[14] ?? null;
        $nutrition = $row[15] ?? null;
        $shelf_life = $row[16] ?? null;
        $storage_instructions = $row[17] ?? null;

        // Validate required values
        if (empty($name) || $price <= 0 || $category_id <= 0 || $brand_id <= 0) {
            echo "<p class='text-red-500 text-center'>Error: Invalid data in row $rowNumber. Skipped.</p>";
            $rowNumber++;
            continue;
        }

        // =============== Image Handling ==================
        $imagePath = null;
        $compressedImagePath = null;

        if ($imageFileName && isset($_FILES['images'])) {

            $imgIndex = array_search($imageFileName, $_FILES['images']['name']);

            if ($imgIndex !== false && $_FILES['images']['error'][$imgIndex] === UPLOAD_ERR_OK) {

                $imagePath = $uploadDirBase . $imageFileName;
                $compressedImagePath = $compressedDirBase . $imageFileName;

                move_uploaded_file($_FILES['images']['tmp_name'][$imgIndex], $imagePath);

                // compress image
                if (!compressImage($imagePath, $compressedImagePath, 25)) {
                    $compressedImagePath = $imagePath; // fallback
                }

            } else {
                echo "<p class='text-red-500 text-center'>Warning: Image '$imageFileName' missing for row $rowNumber.</p>";
            }
        }

        // =============== Database Insert =================
        try {

            $stmt = $conn->prepare("
                INSERT INTO items 
                    (name, price, discount, category_id, brand_id, stock,
                     description, image, compressed_image, weight, packaging_type, 
                     product_form, origin, grade, purity, flavor, nutrition, shelf_life, storage_instructions) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name, $price, $discount, $category_id, $brand_id, $stock,
                $description, $imagePath, $compressedImagePath,
                $weight, $packaging_type, $product_form, $origin, $grade,
                $purity, $flavor, $nutrition, $shelf_life, $storage_instructions
            ]);

        } catch (PDOException $e) {
            echo "<p class='text-red-500 text-center'>DB Error (Row $rowNumber): " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        $rowNumber++;
    }

    fclose($handle);

    echo "<p class='text-green-500 text-center'>Bundle upload completed successfully!</p>";
}
?>
 <!DOCTYPE html>
 <head>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/config.js"></script>
    <script src="/cart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="/Styles.css" rel="stylesheet">
    <title> Bundle Upload</title>
      </head>

<body class="bg-gray-100">
    <div class="admin-container flex">
        <?php require_once './common/admin_sidebar.php'; ?>
        <main class="admin-main flex-1 p-6"> <h3 class="text-xl font-semibold text-green-600 mt-4 mb-4">Bundle Upload (CSV + Images)</h3>
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
                </main>
    </div>
</body>
</html>
