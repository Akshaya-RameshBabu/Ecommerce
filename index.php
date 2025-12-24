<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";
$logoDir = "images/logo/";
$logos = glob($logoDir . "*.{png,jpg,jpeg,svg,webp}", GLOB_BRACE);
// Opt-in debugging flag (use ?debug_images=1)
$debugImages = isset($_GET['debug_images']) && $_GET['debug_images'] === '1';

$res = $conn->prepare("SELECT * FROM carousel ORDER BY sort_order ASC");
$res->execute();
$slides = $res->fetchAll(PDO::FETCH_ASSOC);


// Fetch GST rate from the settings table
$stmt = $conn->prepare("SELECT gst_rate, last_enquiry_number, notification_text, minimum_order FROM settings LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$notificationText = !empty($settings['notification_text']) ? $settings['notification_text'] : null;
$gstRate = isset($settings['gst_rate']) ? floatval($settings['gst_rate']) : 18;
$minimumOrder = !empty($settings['minimum_order']) ? floatval($settings['minimum_order']) : 2000; // Minimum order amount

// Fetch shop details from DB
$stmt = $conn->prepare("SELECT name, shopaddress, phone, email FROM admin_details LIMIT 1");
$stmt->execute();
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

$adminName = $shop['name'] ?? 'RGreenMart';
$shopAddress = $shop['shopaddress'] ?? 'Chandragandhi Nagar, Madurai, Tamil Nadu';
$shopPhone = $shop['phone'] ?? '99524 24474';
$shopEmail = $shop['email'] ?? 'sales@rgreenmart.com';

// Fetch necessary data from items table including only the primary image from item_images
$stmt = $conn->prepare(
    "SELECT 
        i.id, 
        i.name, 
        i.category_id, 
        i.brand_id, 
        i.status, 
        i.packaging_type, 
        i.product_form, 
        i.origin, 
        i.grade, 
        i.purity, 
        i.flavor, 
        i.description, 
        i.nutrition, 
        i.shelf_life, 
        i.storage_instructions, 
        i.expiry_info, 
        i.tags, 
        i.created_at, 
        i.updated_at,
        -- Use the image column from items table, fall back to subquery if empty
        COALESCE(i.image, (
            SELECT COALESCE(compressed_path, image_path) 
            FROM item_images 
            WHERE item_id = i.id AND is_primary = 1 
            LIMIT 1
        )) AS primary_image,
        -- Fetch price and stock from the first available variant
        v.price, 
        v.old_price, 
        v.discount, 
        v.stock, 
        v.weight_value, 
        v.weight_unit
    FROM items i
    LEFT JOIN item_variants v ON v.id = (
        SELECT id FROM item_variants 
        WHERE item_id = i.id AND status = 1 
        ORDER BY price ASC LIMIT 1
    )"
);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If debug mode is enabled, dump the raw query result to browser console and error log
if ($debugImages) {
    // Use JSON_UNESCAPED_SLASHES to keep paths readable in console
    $jsonItems = json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "<script>console.log('DEBUG: items query result', $jsonItems);</script>";
    error_log('[image-debug] items query result: ' . $jsonItems);
}

// GST Rate (Set from your code)
$gstRate = 18; // <-- Change if needed

$processedItems = [];

foreach ($items as $idx => $item) {

    // -------- PRICE CALCULATIONS -------- //
    $grossPrice = round($item['price']);
    $netPrice   = round($item['price'] / (1 + $gstRate / 100));
    $gstAmount  = $grossPrice - $netPrice;

    $discountRate = (float) $item['discount'];
    $discountAmount = round($netPrice * ($discountRate / 100));
    $simpleDiscountedPrice = round($grossPrice * (1 - ($discountRate / 100)));

    // -------- IMAGE PATH HANDLING (NEW: use item_images table) -------- //

    $defaultPublicImage = "/images/default.jpg";
    // Use the primary image provided by the query (is_primary = 1). If absent, use default.
    $candidate = trim($item['primary_image'] ?? '');

    // Resolve candidate across possible public paths (try without and with /admin/ prefix)
    $displayImgPath = $defaultPublicImage;
    $publicOriginal = $defaultPublicImage;
    $publicCompressed = $defaultPublicImage;
    $fileExists = false;
    $testedPaths = [];

    if (!empty($candidate)) {
        $variants = [
            '/' . ltrim($candidate, '/'),
            '/admin/' . ltrim($candidate, '/'),
        ];

        foreach ($variants as $p) {
            $testedPaths[] = $p;
            $serverP = $_SERVER['DOCUMENT_ROOT'] . $p;
            if (file_exists($serverP)) {
                $displayImgPath = $p;
                $publicOriginal = $p;
                $publicCompressed = $p;
                $fileExists = true;
                break;
            }
        }
    }

    // Debug output (opt-in)
    if ($debugImages) {
        $dbg = [
            'item_id' => $item['id'] ?? null,
            'item_name' => $item['name'] ?? null,
            'primary_image_field' => $item['primary_image'] ?? null,
            'candidate' => $candidate,
            'tested_paths' => $testedPaths ?? [],
            'serverPath' => isset($serverP) ? $serverP : null,
            'file_exists' => $fileExists,
            'displayImgPath' => $displayImgPath
        ];

        // Output to page for quick debugging
        echo "<div style='background:#fff7cc;border:1px solid #ffd54f;padding:8px;margin:6px 0;font-family:monospace;'>";
        echo "<strong>Image Debug:</strong> " . htmlspecialchars(json_encode($dbg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        echo "</div>";

        // Also write to server error log for post-mortem
        error_log("[image-debug] " . json_encode($dbg));
    }

    // -------- BUILD CLEAN OUTPUT ARRAY -------- //
    $processedItems[$idx] = [
        // BASIC FIELDS
        'id'          => htmlspecialchars($item['id'], ENT_QUOTES),
        'name'        => htmlspecialchars($item['name'], ENT_QUOTES),
        'category_id' => $item['category_id'],
        'brand_id'    => $item['brand_id'],

        // PRICING
        'price'                => $item['price'],
        'old_price'            => $item['old_price'],
        'discount'             => $discountRate,
        'grossPrice'           => $grossPrice,
        'netPrice'             => $netPrice,
        'gstAmount'            => $gstAmount,
        'discountAmount'       => $discountAmount,
        'simpleDiscountedPrice'=> $simpleDiscountedPrice,

        // STOCK
        'stock'  => $item['stock'],

        // IMAGES
        'image'           => $publicOriginal,
        'compressedImage' => $publicCompressed,
        'displayImgPath'  => htmlspecialchars($displayImgPath, ENT_QUOTES),

        // PRODUCT DETAILS
        'weight'        => $item['weight_value'] . ' ' . $item['weight_unit'],
        'packaging_type'=> $item['packaging_type'],
        'product_form'  => $item['product_form'],
        'origin'        => $item['origin'],
        'grade'         => $item['grade'],
        'purity'        => $item['purity'],
        'flavor'        => $item['flavor'],

        // TEXT INFO
        'description'           => $item['description'],
        'nutrition'             => $item['nutrition'],
        'shelf_life'            => $item['shelf_life'],
        'storage_instructions'  => $item['storage_instructions'],
        'expiry_info'           => $item['expiry_info'],

        // META
        'tags'        => $item['tags'],
        'created_at'  => $item['created_at'],
        'updated_at'  => $item['updated_at'],

        'discountRate' => $discountRate,
    ];
  
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RGreenMart</title>
    <link rel="icon" type="image/png" href="./images/LOGO.jpg">
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="./Styles.css">

</head>

<body>
    
<main id="main" style="position: relative; z-index: 2;">
   <?php if ($notificationText): ?>
<div class="scrolling-text-container">
    <span class="scrolling-text">
        <?= htmlspecialchars($notificationText); ?>
    </span>
</div>
<?php endif; ?>

    <?php require_once $_SERVER["DOCUMENT_ROOT"] . "/includes/header.php"; ?>
    <section id="hero" >
<div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">

  <!-- Indicators -->
  <div class="carousel-indicators">
    <?php foreach ($slides as $i => $slide): ?>
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="<?= $i ?>"
        class="<?= $i === 0 ? 'active' : '' ?>"></button>
    <?php endforeach; ?>
  </div>

  <!-- Slides -->
  <div class="carousel-inner">
    <?php foreach ($slides as $i => $slide): ?>
     <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>" data-bs-interval="5000">
        <img src="<?= $slide['image_path'] ?>" class="d-block w-100" 
             style="object-fit: cover; max-height: 80vh;">
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Controls -->
  <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>

</div>

</section>



    <div id="main-body">
        <section id="products">

            <div class="container">


              <div class="product-grid">
    <?php foreach ($processedItems as $item): ?>

        <?php
        // Build the cart object for JS
        $cartData = [
            "id" => $item["id"],
            "name" => $item["name"],
            "oldamt" => $item["grossPrice"],
            "discountRate" => $item["discountRate"],
            "gstRate" => $gstRate,
            "price" => $item["simpleDiscountedPrice"],
            "image" => $item["displayImgPath"]
        ];
        ?>

       

            <div class="card-container"
                 data-idx="<?= $item['id']; ?>"
            >
 <a href="product.php?id=<?= $item['id']; ?>" >
                <!-- Product Image -->
                <div class="product-image">
                    <img src="<?= $item['displayImgPath']; ?>" alt="<?= $item['name']; ?>">

                    <?php if ($item['discountRate'] > 0): ?>
                        <span class="badge">-<?= $item['discountRate']; ?>%</span>
                    <?php endif; ?>
                </div>

              <p class="product-title"><?= htmlspecialchars($item['name']); ?></p>



                <!-- Price -->
                <p class="price">
                    
                    <span class="new-price">₹<?= $item['simpleDiscountedPrice']; ?></span>
                    <span class="old-price">₹<?= $item['grossPrice']; ?></span>
                </p>
                    </a>
                <!-- Add to Cart -->
                <button class="add-to-cart-btn"
                        onclick='event.stopPropagation();
                                 event.preventDefault();
                                 saveToCart(<?= json_encode($cartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);'
                        title="Add to Cart">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>

            </div>

     
    <?php endforeach; ?>
</div>

    </div>
    </section>
                    </main>

    <script>
function saveToCart(product) {

    // Add default quantity
    product.quantity = 1;

    // Now send it to addToCart()
    addToCart(product);

}
    </script>

    <div class="partner-wrapper">
    <div class="partner-track">
        <?php foreach ($logos as $logo): ?>
            <div class="partner-logo">
                <img src="<?= $logo ?>" alt="Partner Logo">
            </div>
        <?php endforeach; ?>

        <!-- duplicate for smooth infinite scroll -->
        <?php foreach ($logos as $logo): ?>
            <div class="partner-logo">
                <img src="<?= $logo ?>" alt="Partner Logo">
            </div>
        <?php endforeach; ?>
    </div>
</div>
    <?php require_once $_SERVER["DOCUMENT_ROOT"] ."/includes/footer.php"; ?>
    
    <div id="toast-container"></div>
    <script src="/cart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>