<?php
require_once 'vendor/autoload.php';
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";

$id = $_GET['id'] ?? null;
if (!$id) exit("Invalid Product");

$stmt = $conn->prepare("SELECT * FROM items WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$gstRate = isset($settings['gst_rate']) ? floatval($settings['gst_rate']) : 18;
if (!$product) exit("Product Not Found");

// ----------------------- IMAGE LOGIC -----------------------

$defaultImage = "./images/default.jpg";

// Try to get primary image from item_images table first
$imgStmt = $conn->prepare("SELECT compressed_path, image_path FROM item_images WHERE item_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1");
$imgStmt->execute([$id]);
$img = $imgStmt->fetch(PDO::FETCH_ASSOC);

if ($img && (!empty($img['compressed_path']) || !empty($img['image_path']))) {
    $candidate = !empty($img['compressed_path']) ? $img['compressed_path'] : $img['image_path'];

    // Try candidate in multiple locations (root and admin/ prefix)
    $variants = [
        '/' . ltrim($candidate, '/'),
        '/admin/' . ltrim($candidate, '/'),
    ];

    $found = false;
    foreach ($variants as $v) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $v)) {
            $displayImgPath = $v;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $displayImgPath = $defaultImage;
    }
} else {
    // Backwards compatible fallback to legacy columns
    $image = basename($product['image']);
    $originalImgPath = "./admin/Uploads/$image";
    $compressedImgPath = "./admin/Uploads/compressed/$image";

    $displayImgPath = file_exists($compressedImgPath)
        ? $compressedImgPath
        : (file_exists($originalImgPath) ? $originalImgPath : $defaultImage);
}

$displayImgPath = htmlspecialchars($displayImgPath, ENT_QUOTES, 'UTF-8');

// --- Fetch all images for gallery ---
$images = [];
$allStmt = $conn->prepare("SELECT id, image_path, compressed_path, is_primary FROM item_images WHERE item_id = ? ORDER BY is_primary DESC, sort_order ASC");
$allStmt->execute([$id]);
$rows = $allStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $candidate = !empty($r['compressed_path']) ? $r['compressed_path'] : $r['image_path'];
    if (empty($candidate)) continue;
    $variants = ['/' . ltrim($candidate, '/'), '/admin/' . ltrim($candidate, '/')];
    $src = null;
    foreach ($variants as $v) {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $v)) { $src = $v; break; }
    }
    if (!$src) continue;
    $images[] = [
        'id' => $r['id'],
        'src' => $src,
        'is_primary' => (bool)$r['is_primary']
    ];
}

// If no images found via table, try legacy locations if product image exists
if (empty($images)) {
    $image = basename($product['image']);
    if ($image) {
        $orig = '/admin/Uploads/' . $image;
        $comp = '/admin/Uploads/compressed/' . $image;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $comp)) {
            $images[] = ['id'=>0,'src'=>$comp,'is_primary'=>true];
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $orig)) {
            $images[] = ['id'=>0,'src'=>$orig,'is_primary'=>true];
        }
    }
}

// Determine initial main image: primary first, else first image, else default
$mainImageSrc = $displayImgPath; // start with computed display
$initialIndex = 0;
if (!empty($images)) {
    $primaryFound = null;
    foreach ($images as $idx => $imgEntry) {
        if ($imgEntry['is_primary']) { $primaryFound = $imgEntry['src']; $initialIndex = $idx; break; }
    }
    $mainImageSrc = $primaryFound ?? $images[0]['src'];
}
 $mainImageSrc = htmlspecialchars($mainImageSrc, ENT_QUOTES, 'UTF-8');


// ----------------------- PRICE CALCULATION -----------------------
    $discountRate=round((float) $product['discount']);
    $grossPrice = round($product['price']); // integer
    $netPrice = round($product['price'] / (1 + $gstRate / 100)); // integer
    $gstAmount = $grossPrice - $netPrice; // integer by logic
    $discountAmount = round($netPrice * ((float) $product['discount'] / 100)); // integer
    $simpleDiscountedPrice = round($grossPrice * (1 - ((float) $product['discount'] / 100))); // integer
    // Fetch Category
$catStmt = $conn->prepare("SELECT name FROM categories WHERE id=?");
$catStmt->execute([$product['category_id']]);
$categoryName = $catStmt->fetchColumn() ?: "Unknown";

// Fetch Brand
$brandStmt = $conn->prepare("SELECT name FROM brands WHERE id=?");
$brandStmt->execute([$product['brand_id']]);
$brandName = $brandStmt->fetchColumn() ?: "No Brand";
                   
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']); ?> - RGreenMart</title>
    <link rel="icon" type="image/png" href="./images/LOGO.jpg">
    <link rel="stylesheet" href="./Styles.css">
    <script src="cart.js"></script>
    <!-- Bootstrap CSS for carousel and components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
       <?php require_once $_SERVER["DOCUMENT_ROOT"] . "/includes/header.php"; ?>
   
<div class=" p-6 m-10 bg-white shadow-lg rounded-xl mb-5">

    <!-- Product Main Block -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

        <!-- LEFT: IMAGE + THUMBNAILS -->
        <div class="w-full image-area flex gap-4 items-start">
            <div id="thumbsColumn" class="thumbs flex flex-col items-center gap-3" style="min-width:120px;">
                <?php if (!empty($images)): ?>
                    <?php foreach($images as $i => $imgEntry): ?>
                        <div class="thumb-box <?php if($imgEntry['is_primary']) echo 'primary'; ?>" data-bs-target="#productCarousel" data-bs-slide-to="<?= $i ?>" role="button" aria-label="View image <?= $i+1 ?>" style="width:100px;height:100px;">
                            <img src="<?= htmlspecialchars($imgEntry['src'], ENT_QUOTES, 'UTF-8') ?>" alt="thumb" style="width:100%;height:100%;object-fit:cover;border-radius:6px;display:block;" />
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="thumb-box" style="width:100px;height:100px;">
                        <img src="<?= htmlspecialchars($displayImgPath, ENT_QUOTES, 'UTF-8') ?>" alt="thumb" style="width:100%;height:100%;object-fit:cover;border-radius:6px;display:block;" />
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex-1 product-main">
                <div id="productCarousel" class="carousel slide">
                    <div class="carousel-inner">
                        <?php foreach($images as $i => $imgEntry): ?>
                            <div class="carousel-item <?= ($i === $initialIndex) ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($imgEntry['src'], ENT_QUOTES, 'UTF-8') ?>" class="d-block product-image" alt="Product image <?= $i+1 ?>">
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($images)): ?>
                            <div class="carousel-item active">
                                <img src="<?= htmlspecialchars($displayImgPath, ENT_QUOTES, 'UTF-8') ?>" class="d-block product-image" alt="Product image">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- RIGHT: DETAILS -->
        <div class="space-y-4">

            <!-- NAME -->
            <h1 class="text-3xl font-semibold text-gray-800">
                <?= htmlspecialchars($product['name']); ?>
            </h1>

            <!-- BRAND + CATEGORY -->
            <!-- <p class="text-gray-600 text-sm">Brand: 
                <span class="font-semibold text-gray-700"><?= htmlspecialchars($brandName); ?></span>
            </p>

            <p class="text-gray-600 text-sm">Category: 
                <span class="font-semibold text-gray-700"><?= htmlspecialchars($categoryName); ?></span>
            </p> -->
                 <p class="text-gray-600 text-sm"><?= nl2br($product['description']); ?></p>

            <!-- PRICE SECTION -->
            <div class="flex items-center gap-3">
                <span class="text-3xl font-bold text-green-600">
                    ₹<?= number_format($simpleDiscountedPrice); ?>
                </span>

                <?php if ($discountRate > 0): ?>
                <span class="text-gray-500 line-through text-lg">
                    ₹<?= number_format($grossPrice); ?>
                </span>
                <span class="px-2 py-1 bg-green-700 text-white text-xs rounded">
                    <?= $discountRate; ?>% OFF
                </span>
                <?php endif; ?>
            </div>

            <!-- STOCK -->
            <p class="text-sm">
                <span class="font-semibold">Stock:</span> 
                <?= $product['stock'] > 0 ? '<span class="text-green-600">Available</span>' : '<span class="text-red-600">Out of stock</span>' ?>
            </p>

            <!-- QUANTITY -->
            <div>
                <p class="text-sm font-semibold mb-1">Choose Quantity:</p>

                <div class="flex items-center w-32 border rounded-lg shadow-sm bg-gray-50">
                    <button onclick="adjust(-1)" 
                        class="px-3 py-2 text-lg font-bold hover:bg-gray-200">−</button>

                    <input id="qty" type="number" value="1" min="1"
                        class="w-full text-center border-x py-2">

                    <button onclick="adjust(1)" 
                        class="px-3 py-2 text-lg font-bold hover:bg-gray-200">+</button>
                </div>
            </div>

            <!-- ADD TO CART -->
            <button onclick="sendToCart()"
                class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-lg">
                Add to Cart <i class="fas fa-shopping-cart ml-2"></i>
            </button>

        </div>
    </div>

    <!-- FULL DETAILS SECTION -->
<div class="mt-12 space-y-10">

    <!-- DESCRIPTION -->
    <?php if(!empty($product['description'])): ?>
    <div class="p-6 bg-gradient-to-br from-white to-blue-50 rounded-2xl border border-blue-100 shadow-md">
        <h2 class="text-xl font-semibold text-gray-900 mb-3">Description</h2>
        <p class="text-gray-700 leading-relaxed"><?= nl2br($product['description']); ?></p>
    </div>
    <?php endif; ?>

    <!-- NUTRITION -->
    <?php if(!empty($product['nutrition'])): ?>
    <div class="p-6 bg-gradient-to-br from-white to-green-50 rounded-2xl border border-green-100 shadow-md">
        <h2 class="text-xl font-semibold text-gray-900 mb-3">Nutritional Information</h2>
        <p class="text-gray-700 leading-relaxed"><?= nl2br($product['nutrition']); ?></p>
    </div>
    <?php endif; ?>


    <!-- PRODUCT DETAILS -->
    <div class="p-6 bg-gradient-to-br from-white to-green-50 rounded-2xl border border-green-100 shadow-md">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Product Details</h2>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-gray-800">

    <?php if($product['weight']): ?>
    <div >
        <p class="text-sm text-gray-500">Weight</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['weight']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['packaging_type']): ?>
    <div >
        <p class="text-sm text-gray-500">Packaging</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['packaging_type']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['product_form']): ?>
    <div >
        <p class="text-sm text-gray-500">Form</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['product_form']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['origin']): ?>
    <div >
        <p class="text-sm text-gray-500">Origin</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['origin']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['grade']): ?>
    <div >
        <p class="text-sm text-gray-500">Grade</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['grade']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['purity']): ?>
    <div >
        <p class="text-sm text-gray-500">Purity</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['purity']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['flavor']): ?>
    <div >
        <p class="text-sm text-gray-500">Flavor</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['flavor']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['shelf_life']): ?>
    <div >
        <p class="text-sm text-gray-500">Shelf Life</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['shelf_life']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['storage_instructions']): ?>
    <div >
        <p class="text-sm text-gray-500">Storage</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['storage_instructions']); ?></p>
    </div>
    <?php endif; ?>

    <?php if($product['expiry_info']): ?>
    <div >
        <p class="text-sm text-gray-500">Expiry</p>
        <p class="text-lg font-semibold pl-3"><?= htmlspecialchars($product['expiry_info']); ?></p>
    </div>
    <?php endif; ?>

</div>


    </div>

</div>



</div>

<script>
function adjust(val) {
    let qty = document.getElementById("qty");
    qty.value = Math.max(1, parseInt(qty.value) + val);
}

const PRODUCT_DATA = {
    id: <?= $id; ?>,
    name: "<?= addslashes($product['name']); ?>",
    oldamt: <?= $grossPrice; ?>,  
    discountRate: <?= $discountRate; ?>,
    gstRate: <?= $gstRate ?? 0 ?>,
    price: <?= $simpleDiscountedPrice; ?>, 
    image: "<?= $mainImageSrc; ?>"
};

function sendToCart() {
    const quantity = parseInt(document.getElementById("qty").value) || 1;

    addToCart({
        ...PRODUCT_DATA,
        quantity: quantity
    });
}
</script>
  <?php require_once $_SERVER["DOCUMENT_ROOT"] ."/includes/footer.php"; ?>
    
    <div id="toast-container"></div>
    <!-- Bootstrap JS (bundle includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    .thumb-box { position:relative; cursor:pointer; border:2px solid #e5e7eb; border-radius:6px; width:100px; height:100px; overflow:hidden; display:flex; align-items:center; justify-content:center; }
    .thumb-box img { width:100%; height:100%; object-fit:cover; border-radius:6px; display:block; }
    .thumb-box.selected { border-color: #16a34a; box-shadow:0 0 0 3px rgba(16,185,129,0.12); }

    /* Layout: on small screens, stack main image above and show thumbnails horizontally below */
    .image-area { display:flex; gap:1rem; align-items:flex-start; }
    .thumbs { flex: 0 0 auto; }
    .product-main { flex:1 1 auto; }

    /* Desktop: main image fixed at 500x500 and vertical thumb column with scrollbar */
    @media (min-width: 768px) {
        .product-main { width:500px; height:500px; background:#fff; border-radius:12px; padding:10px;border:1px solid #e5e7eb;  }
        /* Ensure carousel container honors height so images center properly (subtract padding) */
        .product-main .carousel, .product-main .carousel-inner, .product-main .carousel-item { height:480px; }
        .product-main .product-image { width:100%; height:100%; object-fit:contain; display:block; margin:0 auto; }

        .thumbs { min-width:120px; max-height:520px; overflow-y:auto; padding-right:6px; }
        .thumbs .thumb-box { width:90px; height:90px; margin-bottom:8px; }
        /* Narrow but visible scrollbar */
        .thumbs::-webkit-scrollbar { width:8px; }
        .thumbs::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius:4px; }
    }

    /* Mobile: stack vertically and make thumbs horizontal scrollable below */
    @media (max-width: 767px) {
        .image-area { flex-direction: column; }
        .thumbs { width:100%; display:flex; flex-direction:row; gap:0.5rem; overflow-x:auto; padding:0.5rem 0; }
        .thumbs .thumb-box { width:70px; height:70px; flex:0 0 auto; }
        .product-main  { width:100%; height:auto; max-height:350px; }
    }

    /* Minimal fallback for carousel visibility if Bootstrap CSS fails to load */
    .carousel-item { display: none; }
    .carousel-item.active { display: block; }

    /* Carousel control styling: rounded dark translucent background and vertically centered left/right inside the product panel */
    .product-main .carousel { position: relative; }
    .product-main .carousel-control-prev,
    .product-main .carousel-control-next {
        position: absolute; top: 50%; transform: translateY(-50%); width:44px; height:44px; border-radius:50%; background: rgba(0,0,0,0.35); display:flex; align-items:center; justify-content:center; box-shadow: 0 6px 18px rgba(0,0,0,0.18); border: none; opacity:1; transition: transform .15s ease, background .15s ease, box-shadow .15s ease; z-index:20;
    }
    .product-main .carousel-control-prev { left: 10px; right: auto; }
    .product-main .carousel-control-next { right: 10px; left: auto; }
    .product-main .carousel-control-prev:hover,
    .product-main .carousel-control-next:hover,
    .product-main .carousel-control-prev:focus,
    .product-main .carousel-control-next:focus {
        transform: translateY(-50%) scale(1.03); box-shadow: 0 10px 30px rgba(0,0,0,0.22); background: rgba(0,0,0,0.5);
    }
    .product-main .carousel-control-prev-icon, .product-main .carousel-control-next-icon { background-size: 20px 20px; filter: none; }

    /* Thumbnails (carousel indicators) appearance: rounded, subtle shadow and background */
    .thumb-box { background: #fff; border-radius:12px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); transition: box-shadow .15s ease, transform .12s ease; }
    .thumb-box:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }

    </style>
    <script>
    (function(){
        const thumbs = Array.from(document.querySelectorAll('#thumbsColumn .thumb-box'));
        const carouselEl = document.getElementById('productCarousel');
        if (!carouselEl) return;

        // Initialize Bootstrap carousel with no auto-ride
        const bsCarousel = new bootstrap.Carousel(carouselEl, { interval: false });

        const carouselItems = Array.from(carouselEl.querySelectorAll('.carousel-item'));
        const initialIndex = <?= intval($initialIndex ?? 0) ?>;

        // Do not mark any thumbnail as "selected" on initial load; selection appears after user interaction or carousel navigation.

        // When carousel slides, update thumbnail selection
        carouselEl.addEventListener('slid.bs.carousel', function(e) {
            const active = carouselEl.querySelector('.carousel-item.active');
            const idx = carouselItems.indexOf(active);
            thumbs.forEach(t => t.classList.remove('selected'));
            if (thumbs[idx]) {
                thumbs[idx].classList.add('selected');
                try { thumbs[idx].scrollIntoView({behavior:'smooth', inline:'center', block:'nearest'}); } catch(err){}
            }
        });

        // Clicking a thumbnail uses Bootstrap data attributes to change slide; also provide immediate feedback
        thumbs.forEach((t, i) => {
            t.addEventListener('click', function() {
                bsCarousel.to(i);
                thumbs.forEach(x => x.classList.remove('selected'));
                t.classList.add('selected');
                try { t.scrollIntoView({behavior:'smooth', inline:'center', block:'nearest'}); } catch(err){}
            });
        });

        // Ensure initial carousel slide is correct
        bsCarousel.to(initialIndex);
    })();
    </script>
</body>
</html>
