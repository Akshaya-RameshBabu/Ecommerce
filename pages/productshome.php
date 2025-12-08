<?php
require_once 'vendor/autoload.php';

// Fetch GST rate from the settings table
$stmt = $conn->prepare("SELECT gst_rate, last_enquiry_number FROM settings LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$gstRate = isset($settings['gst_rate']) ? floatval($settings['gst_rate']) : 18;
$minimumOrder = 2000; // Minimum order amount

// Fetch shop details from DB
$stmt = $conn->prepare("SELECT name, shopaddress, phone, email FROM admin_details LIMIT 1");
$stmt->execute();
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

$adminName = $shop['name'] ?? 'RGreenMart';
$shopAddress = $shop['shopaddress'] ?? 'Chandragandhi Nagar, Madurai, Tamil Nadu';
$shopPhone = $shop['phone'] ?? '99524 24474';
$shopEmail = $shop['email'] ?? 'sales@rgreenmart.com';

// Fetch data from items table
$stmt = $conn->prepare("SELECT id,name, price, discount, pieces, items, category, brand, image FROM items");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Precompute values for each item
$processedItems = [];

foreach ($items as $idx => $item) {
    $grossPrice = round($item['price']); // integer
    $netPrice = round($item['price'] / (1 + $gstRate / 100)); // integer
    $gstAmount = $grossPrice - $netPrice; // integer by logic
    $discountAmount = round($netPrice * ((float) $item['discount'] / 100)); // integer
    $simpleDiscountedPrice = round($grossPrice * (1 - ((float) $item['discount'] / 100))); // integer

    // Original image path
    $originalImgPath = !empty($item['image']) && !empty($item['brand'])
        ? './admin/Uploads/' . htmlspecialchars($item['brand'], ENT_QUOTES, 'UTF-8') . '/' . basename($item['image'])
        : '';

    // Compressed image path
    $compressedImgPath = !empty($item['image']) && !empty($item['brand'])
        ? './admin/Uploads/compressed/' . htmlspecialchars($item['brand'], ENT_QUOTES, 'UTF-8') . '/' . basename($item['image'])
        : '';

$defaultImage = './images/default.png';

$displayImgPath = file_exists($compressedImgPath) ? $compressedImgPath :
                  (file_exists($originalImgPath) ? $originalImgPath : $defaultImage);

$displayImgPath = htmlspecialchars($displayImgPath, ENT_QUOTES, 'UTF-8');


    $processedItems[$idx] = [
        'id' => htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'),
        'name' => htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'),
        'category' => htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'),
        'category_raw' => $item['category'],
        'brand' => htmlspecialchars($item['brand'], ENT_QUOTES, 'UTF-8'),
        'pieces' => (int) $item['pieces'],
        'items' => (int) $item['items'],
        'grossPrice' => $grossPrice,
        'netPrice' => $netPrice,
        'gstAmount' => $gstAmount,
        'discountAmount' => $discountAmount,
        'simpleDiscountedPrice' => $simpleDiscountedPrice,
       'originalImgPath' => file_exists($originalImgPath)
    ? htmlspecialchars($originalImgPath, ENT_QUOTES, 'UTF-8')
    : './images/default.png',
        'displayImgPath' => $displayImgPath,
        'discountRate' => round((float) $item['discount']), // as integer
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
  <script src="cart.js"></script>
    <link rel="stylesheet" type="text/css" href="./Styles.css">

</head>

<body>
    <section id="hero" class="d-flex align-items-center justify-content-center" style="position: relative; z-index: 1;">
  <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
    <!-- Indicators (small dots) -->
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
      <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>

    <!-- Carousel Inner -->
    <div class="carousel-inner">
      <div class="carousel-item active" data-bs-interval="5000" style=" position: relative;">
        <img src="images/BANNER1.png" class="d-block w-100" alt="Banner 1" style="object-fit: cover; max-height: 80vh;">
      </div>
      <div class="carousel-item" data-bs-interval="5000" style="position: relative;">
        <img src="images/BANNER3.png" class="d-block w-100" alt="Banner 3" style="object-fit: cover; max-height: 80vh;">
      </div>
      <div class="carousel-item" data-bs-interval="5000">
        <img src="images/BANNER2.png" class="d-block w-100" alt="Banner 2" style="object-fit: cover; max-height: 80vh;">
      </div>
    </div>

    <!-- Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</section>
        <?php include "includes/header.php"; ?>

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
            "brand" => $item["brand"],
            "image" => $item["displayImgPath"]
        ];
        ?>

        <a href="index.php?page=product&id=<?= $item['id']; ?>" >

            <div class="card-container"
                 data-category="<?= $item['category_raw']; ?>"
                 data-brand="<?= $item['brand']; ?>"
                 data-idx="<?= $item['id']; ?>"
            >

                <!-- Product Image -->
                <div class="product-image">
                    <img src="<?= $item['displayImgPath']; ?>" alt="<?= $item['name']; ?>">

                    <?php if ($item['discountRate'] > 0): ?>
                        <span class="badge">-<?= $item['discountRate']; ?>%</span>
                    <?php endif; ?>
                </div>

                <!-- Title -->
                <p class="product-title"><?= $item['name']; ?></p>

                <!-- Brand -->
                <p class="brand"><?= $item['brand']; ?></p>

                <!-- Price -->
                <p class="price">
                    <span class="old-price">₹<?= $item['grossPrice']; ?></span>
                    <span class="new-price">₹<?= $item['simpleDiscountedPrice']; ?></span>
                </p>

                <!-- Add to Cart -->
                <button class="add-to-cart-btn"
                        onclick='event.stopPropagation();
                                 event.preventDefault();
                                 saveToCart(<?= json_encode($cartData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>);'
                        title="Add to Cart">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>

            </div>

        </a>
    <?php endforeach; ?>
</div>

    </div>
    </section>

    <script>
function saveToCart(product) {

    // Add default quantity
    product.quantity = 1;

    // Now send it to addToCart()
    addToCart(product);

}
    </script>
    <?php include "includes/footer.php"; ?>
</body>

</html>