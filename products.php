<?php
require_once 'vendor/autoload.php';
require_once __DIR__ . "/includes/env.php";

// Database connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'diwali_db';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

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

$adminName = $shop['name'] ?? 'RGreen Enterprise';
$shopAddress = $shop['shopaddress'] ?? 'Chandragandhi Nagar, Madurai, Tamil Nadu';
$shopPhone = $shop['phone'] ?? '6358986751';
$shopEmail = $shop['email'] ?? 'arunbabuks03@gmail.com';

// Fetch unique categories
$stmt = $conn->prepare("SELECT DISTINCT category FROM items ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch unique brands
$stmt = $conn->prepare("SELECT DISTINCT brand FROM items ORDER BY id ASC");
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Map brands to images and PDF paths
$brandImages = [];
$brandPdfs = [];
foreach ($brands as $index => $brand) {
    $imageMap = [
        0 => '/images/Multi.jpg',
        1 => '/images/Combo.avif',
        2 => '/images/Krishna.jpg',
        3 => '/images/Anil.png',
        4 => '/images/wetwo.png',
        5 => '/images/Sonny.png',
        6 => '/images/Ayyan.png',
    ];
    $pdfMap = [
        'multi_brand' => '/Pricelist/multi_brand.pdf',
        'Anil' => '/Pricelist/anil.pdf',
        'Ayyan' => '/Pricelist/ayyan.pdf',
        'Sonny' => '/Pricelist/sonny.pdf',
        'wetwo' => '/Pricelist/wetwo.pdf',
        'Combo' => '/Pricelist/combo.pdf',
    ];
    $brandImages[$brand] = $imageMap[$index] ?? '/img/default.png';
    // Ensure case-insensitive matching and correct path
    $brandPdfs[$brand] = isset($pdfMap[$brand]) ? $pdfMap[$brand] : '/Pricelist/' . strtolower($brand) . '.pdf';
}

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

    // Display image path
    $displayImgPath = $compressedImgPath && file_exists($compressedImgPath)
        ? htmlspecialchars($compressedImgPath, ENT_QUOTES, 'UTF-8')
        : ($originalImgPath && file_exists($originalImgPath)
            ? htmlspecialchars($originalImgPath, ENT_QUOTES, 'UTF-8')
            : '/images/default.png');

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
        'originalImgPath' => $originalImgPath && file_exists($originalImgPath)
            ? htmlspecialchars($originalImgPath, ENT_QUOTES, 'UTF-8')
            : '/images/default.png',
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
    <title>RGreen Enterprise</title>
    <link rel="icon" type="image/png" href="./images/LOGO.jpg">
    <meta name="keywords"
        content="Deepavali crackers sale 2025, Buy crackers online Deepavali 2025, Diwali crackers offer 2025, Deepavali discount crackers online, Diwali crackers shop near me, Deepavali crackers combo offer 2025, Wholesale Diwali crackers online, Sivakasi crackers online shopping, Diwali crackers home delivery 2025, Best price Diwali crackers online, Cheapest Deepavali crackers online 2025, Eco-friendly Diwali crackers online 2025, Diwali crackers gift box sale 2025, Online cracker booking for Deepavali 2025, Buy Sivakasi crackers for Deepavali 2025, Buy crackers online Chennai Deepavali 2025, Diwali crackers sale Coimbatore 2025, Deepavali crackers shop Madurai 2025, Tirunelveli Deepavali crackers online, Salem Diwali crackers discount 2025, Deepavali crackers gift pack 2025, Green crackers for Diwali 2025, Cheap Diwali crackers online 2025, Buy Diwali crackers online Tamil Nadu 2025, Standard Fireworks Diwali crackers 2025, Ayyan Fireworks branded crackers online, Sony Fireworks crackers sale 2025, Sri Kaliswari branded crackers Deepavali 2025, Rgreen Enterprise crackers sale 2025, Trichy branded crackers discount Diwali 2025">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles/products.css">
   
</head>

<body>
    <div class="sticky-header">
        <?php include "includes/header.php"; ?>
    

    </div>
    <div id="main-body">
        <section id="products" class="py-20 bg-white">
            <div class="section-title">
                <h2 class="blink">SELECT YOUR BRAND!!!</h2>
            </div>
            <div class="brand-buttons">
                <?php foreach ($brands as $index => $brand): ?>
                    <div class="brand-button" data-brand="<?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>"
                        data-pdf="<?php echo htmlspecialchars($brandPdfs[$brand], ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars($brandImages[$brand], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?>">
                        <span><?php echo htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="container">
                <a id="downloadPriceList" class="download-button" href="#" download>SELECT YOUR BRAND AND DOWNLOAD YOUR LIST</a>
                <div class="section-title">
                    <h2>Our Products</h2>
                    <p>Explore our wide range of eco-friendly crackers designed for safe and vibrant celebrations.</p>
                </div>
                <div class="category-buttons" style="text-align:center !important;">
                    <button type="button" class="category-button active" data-category="all">All Categories</button>
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" class="category-button"
                            data-category="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="category-title-container">
                    <h3 id="categoryTitle" style="text-align: center; color: #1f2937; font-weight: bold; margin-bottom: 1rem;">
                        All Products</h3>
                </div>
                <div class="table-container">
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Content</th>
                                <th>Price (Inc. GST)</th>
                                <th>Discounted Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <?php foreach ($processedItems as $idx => $item): ?>
                                <tr data-category="<?php echo htmlspecialchars($item['category_raw'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-brand="<?php echo $item['brand']; ?>" data-idx="<?php echo $idx; ?>">
                                    <td class="id"><?php echo $item['id']; ?></td>
                                    <td>
                                        <div class="image-container" data-img-path="<?php echo $item['displayImgPath']; ?>"
                                            data-original-img-path="<?php echo $item['originalImgPath']; ?>">
                                            <?php if ($item['displayImgPath']): ?>
                                                <img data-src="<?php echo $item['displayImgPath']; ?>"
                                                    alt="<?php echo $item['name']; ?>">
                                            <?php else: ?>
                                                <span>No Image</span>
                                            <?php endif; ?>
                                            <div class="discount-badge">-<?php echo $item['discountRate']; ?>%</div>
                                        </div>
                                    </td>
                                    <td class="name"><?php echo $item['name']; ?></td>
                                    <td class="brand"><?php echo $item['brand']; ?></td>
                                    <td class="category"><?php echo $item['category']; ?></td>
                                    <td class="content">
                                        <?php echo htmlspecialchars($item['pieces'] . ' / ' . $item['items'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td>
                                        <span class="price">₹<?php echo $item['grossPrice']; ?></span>

                                    </td>
                                    <td>
                                        <span class="discounted-price" data-gross-price="<?php echo $item['grossPrice']; ?>">
    ₹<?php echo $item['simpleDiscountedPrice']; ?>
</span>

                                        <span class="discount-amount" data-discount="<?php echo $item['discountAmount']; ?>"
                                            data-discount-rate="<?php echo $item['discountRate']; ?>" style="display: none;"></span>
                                        <span class="gst-amount" data-gst-rate="<?php echo $gstRate; ?>"
                                            style="display: none;">
                                            
                                        </span>
                                    </td>
                                    <td>
                                        <div class="quantity-controls">
                                            <button class="minus" data-idx="<?php echo $idx; ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18 12H6" />
                                                </svg>
                                            </button>
                                            <input type="number" value="0" class="qty" data-idx="<?php echo $idx; ?>" min="0">
                                            <button class="plus" data-idx="<?php echo $idx; ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="item-total">0</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-cards">
                    <?php foreach ($processedItems as $idx => $item): ?>
                        <div class="mobile-card"
                            data-category="<?php echo htmlspecialchars($item['category_raw'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-brand="<?php echo $item['brand']; ?>" data-idx="<?php echo $idx; ?>">
                            <div class="flex items-start space-x-4 mb-4">
                                <div class="image-container" data-img-path="<?php echo $item['displayImgPath']; ?>"
                                    data-original-img-path="<?php echo $item['originalImgPath']; ?>">
                                    <?php if ($item['displayImgPath']): ?>
                                        <img data-src="<?php echo $item['displayImgPath']; ?>" alt="<?php echo $item['name']; ?>">
                                    <?php else: ?>
                                        <span>No Image</span>
                                    <?php endif; ?>
                                    <div class="discount-badge">-<?php echo $item['discountRate']; ?>%</div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="name"><?php echo $item['name']; ?></h3>
                                    <p class="code">Code: ITEM<?php echo sprintf("%03d", $idx + 1); ?></p>
                                    <p class="content">
                                        <?php echo htmlspecialchars($item['pieces'] . ' / ' . $item['items'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <div class="price-container">
                                        <span class="price">₹<?php echo number_format($item['grossPrice'], 2); ?></span>
                                        <span class="discounted-price"
                                            data-gross-price="<?php echo $item['grossPrice']; ?>">₹<?php echo number_format($item['simpleDiscountedPrice'], 2); ?>
                                            (<?php echo $item['discountRate']; ?>%)</span>
                                        <span class="discount-amount" data-discount="<?php echo $item['discountAmount']; ?>"
                                            data-discount-rate="<?php echo $item['discountRate']; ?>" style="display: none;"></span>
                                        <span class="gst-amount" data-gst-rate="<?php echo $gstRate; ?>"
                                            style="display: none;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mb-4 quantity-total-container">
                                <div class="quantity-controls">
                                    <button class="minus" data-idx="<?php echo $idx; ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                        </svg>
                                    </button>
                                    <input type="number" value="0" class="qty" data-idx="<?php echo $idx; ?>" min="0">
                                    <button class="plus" data-idx="<?php echo $idx; ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="total-container">
                                    <p>Total</p>
                                    <p class="item-total">0.00</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="details-container">
                    <div class="summary">
                        <div><span><strong>Total (Inc. GST)</strong></span><span id="total">₹0.00</span></div>
                        <div><span style="color:red">Discount %</span><span id="discountTotal" style="color:red;">₹0.00</span></div>
                        <div><span>Net Rate</span><span id="netRate">₹0.00</span></div>
                        <div>
                            <span class="w-75 coupon-disc" style="color:red">Coupon Discount : <input type="text" id="couponCode"
                                    name="coupon_code" placeholder="Enter coupon code" class="coupon-input ">
                                <button type="button" id="applyCoupon" class="continue-buttons w-25">Apply Coupon</button>
                            </span>
                            <span id="couponDiscount" style="color:red">₹0.00</span>
                        </div>
                        <span style="display:none">
                        <div><span><strong>Overall Total</strong></span><span id="overallTotal">₹0.00</span></div>
                        <div><span><strong>Item Price</strong></span><span id="afterCouponNetRate">₹0.00</span></div>
                        <div><span>Inclusive GST (<?php echo $gstRate; ?>%)</span><span id="gst">₹0.00</span></div></span>
                        <div><span class="finalTotal"><strong>Final Total</strong></span><span id="finalTotal"
                                class="finalTotal">₹0.00</span></div>
                        <div class="minimum-order"><span>Minimum
                                Order</span><span>₹<?php echo number_format($minimumOrder); ?></span></div>
                    </div>
                    <h2 style="text-align: center; color: #1f2937; font-weight: bold; margin-bottom: 1.5rem;">Enter Your Details
                    </h2>
                    <form id="customerDetailsForm" action="pdf_generation.php" method="POST" target="_blank">
                        <div>
                            <label for="customerName">Name <span style="color: #dc2626">*</span></label>
                            <input type="text" id="customerName" name="customer_name" required pattern="^[A-Za-z ]{2,}$"
                                title="Enter a valid name (letters only)">
                        </div>
                        <div>
                            <label for="customerMobile">Mobile Number <span style="color: #dc2626">*</span></label>
                            <input type="tel" id="customerMobile" name="customer_mobile" required pattern="^[6-9][0-9]{9}$"
                                maxlength="10" title="Enter a valid 10-digit mobile number">
                        </div>
                        <div>
                            <label for="customerEmail">Email <span style="color: #dc2626">*</span></label>
                            <input type="email" id="customerEmail" name="customer_email" required
                                title="Enter a valid email address">
                        </div>
                        <div>
                            <label for="customerState">State <span style="color: #dc2626">*</span></label>
                            <input type="text" id="customerState" name="customer_state" required pattern="^[A-Za-z ]{2,}$"
                                title="Enter a valid state">
                        </div>
                        <div>
                            <label for="customerCity">City <span style="color: #dc2626">*</span></label>
                            <input type="text" id="customerCity" name="customer_city" required pattern="^[A-Za-z ]{2,}$"
                                title="Enter a valid city">
                        </div>
                        <div>
                            <label for="customerAddress">Address <span style="color: #dc2626">*</span></label>
                            <textarea id="customerAddress" name="customer_address" required minlength="5"
                                title="Enter your address"></textarea>
                        </div>
                        <input type="hidden" name="ordered_date_time" value="<?php echo date('Y-m-d H:i:s'); ?>">
                        <input type="hidden" name="items_bought" id="itemsBought">
                        <input type="hidden" name="generate_bill" value="true">
                        <input type="hidden" name="coupon_discount" id="couponDiscountHidden" value="0">
                        <input type="hidden" name="coupon_discount_percent" id="couponDiscountPercentHidden" value="0">
                        <input type="hidden" name="coupon_code" id="couponCodeHidden" value="">
                        <button type="submit" class="continue-button" id="continueButton">Continue Estimate</button>
                    </form>
                </div>
            </div>

            <!-- Modal for enlarged image -->
            <div id="imageModal" class="modal">
                <div class="modal-content" style="width:60%;height:60%;margin:0 auto;margin-top:5%;">
                    <span class="modal-close">&times;</span>
                    <img id="modalImage" src="" alt="Enlarged Image" style="width:100%;height:100%;margin:0 auto; padding:20px">
                </div>
            </div>

            <div class="container">
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <div class="card"
                            style="border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 20px;">
                            <p style="font-size: 1.1em; color: #4b5563; margin: 0;">
                                <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>, Sivakasi<br>
                                <span style="font-weight: bold; color: #dc2626;">Shop Address:</span>
                                <?php echo nl2br(htmlspecialchars($shopAddress, ENT_QUOTES, 'UTF-8')); ?><br>
                                <span style="font-weight: bold; color: #dc2626;">Contact:</span>
                                <?php echo htmlspecialchars($shopPhone, ENT_QUOTES, 'UTF-8'); ?> &bull;
                                <span style="font-weight: bold; color: #dc2626;">Email:</span>
                                <?php echo htmlspecialchars($shopEmail, ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Terms and Conditions</h3>
                    <b>Shipping/Transportation</b>
                    <ul>
                        <li>No door delivery.</li>
                        <li>3% Packing charge</li>
                        <li>Transportation charges extra.</li>
                        <li>Transit Insurance/Octroi charges extra.</li>
                        <li>Goods cannot be sent through courier.</li>
                        <li>Shipping of goods solely depends on the transporter.</li>
                        <li>Home Delivery Available – Fast & Safe – Madurai and Sivakasi.</li>
                        <li>Transport Delivery Available – Fast & Safe – Tamil Nadu, Pondicherry, Karnataka, Telangana, and
                            Andhra Pradesh.</li>
                    </ul>
                    <b>Payment Terms</b>
                    <ul>
                        <li>Your order will be processed only after 100% payment in advance.</li>
                    </ul>
                    <b>Tax</b>
                    <ul>
                        <li>All prices are inclusive of GST.</li>
                    </ul>
                    <b>Modification Terms</b>
                    <ul>
                        <li>Due to the nature of the Fireworks Industry, the products are subject to modification.</li>
                    </ul>
                    <b>Other Terms</b>
                    <ul>
                        <li>E. & O.E. (Errors and Omissions Excepted).</li>
                        <li>By placing an order, you agree that you are 18 years & above.</li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Delivery Timeline</h3>
                    <table>
                        <tr>
                            <th>Region</th>
                            <th>Delivery Time</th>
                        </tr>
                        <tr>
                            <td>Tamil Nadu</td>
                            <td>4 Days</td>
                        </tr>
                        <tr>
                            <td>South India</td>
                            <td>7 Days</td>
                        </tr>
                    </table>
                </div>
            </div>
        </section>

        <script>
    // Precomputed items data from PHP
    const itemsData = <?php echo json_encode($processedItems); ?>;
    const gstRate = <?php echo $gstRate; ?>;
    const brandPdfs = <?php echo json_encode($brandPdfs); ?>;

    let selectedBrand = 'all';
    let selectedCategory = 'all';

    // Cache DOM elements
    const qtyInputs = document.querySelectorAll('.qty');
    const tableRows = document.querySelectorAll('#productsTable tbody tr');
    const mobileCards = document.querySelectorAll('.mobile-card');
    const totalDisplay = document.getElementById('total');
    const discountTotalDisplay = document.getElementById('discountTotal');
    const netRateDisplay = document.getElementById('netRate');
    const couponDiscountDisplay = document.getElementById('couponDiscount');
    const gstDisplay = document.getElementById('gst');
    const sumgstDisplay = document.getElementById('sumgst');
    const overallTotalDisplay = document.getElementById('overallTotal');
    const afterCouponNetRateDisplay = document.getElementById('afterCouponNetRate');
    const finalTotalDisplay = document.getElementById('finalTotal');
    const netTotalSpan = document.querySelector('.net_total');
    const discountTotalSpan = document.querySelector('.discount_total');
    const subTotalSpan = document.querySelector('.sub_total');
    const totalProductsCount = document.querySelector('.total_products_count');
    const downloadPriceList = document.getElementById('downloadPriceList');

    // Category header management
    function rebuildCategoryHeaders() {
        const tbody = document.querySelector('#productsTable tbody');
        if (!tbody) return;

        tbody.querySelectorAll('.category-header').forEach(row => row.remove());

        const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => !row.classList.contains('category-header'));

        let lastCategory = null;

        rows.forEach(row => {
            const category = row.dataset.category;
            if (row.classList.contains('hidden')) return;
            if (category !== lastCategory) {
                lastCategory = category;
                const headerRow = document.createElement('tr');
                headerRow.classList.add('category-header');
                const headerCell = document.createElement('td');
                headerCell.setAttribute('colspan', '10');
                headerCell.textContent = category;
                headerRow.appendChild(headerCell);
                tbody.insertBefore(headerRow, row);
            }
        });
    }

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Lazy load images
    function lazyLoadImages() {
        const images = document.querySelectorAll('img[data-src]');
        images.forEach(img => {
            const container = img.closest('.image-container');
            if (container && container.closest('.hidden') === null) {
                img.src = img.dataset.src;
                img.classList.add('loaded');
                img.removeAttribute('data-src');
            }
        });
    }

    function updateQuantity(idx, change) {
        const inputs = document.querySelectorAll(`.qty[data-idx="${idx}"]`);
        let newValue = 0;
        inputs.forEach(input => {
            const currentValue = parseInt(input.value) || 0;
            newValue = Math.max(0, currentValue + change);
            input.value = newValue;
        });

        // Toggle 'selected' class based on quantity
        document.querySelectorAll(`[data-idx="${idx}"]`).forEach(row => {
            if (newValue > 0) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        });

        debouncedRecalcTotals();
    }

    function resetQuantities() {
        qtyInputs.forEach(input => input.value = 0);
        document.querySelectorAll('.action-button').forEach(button => button.disabled = true);
        document.getElementById('couponCode').value = '';
        document.getElementById('couponCodeHidden').value = '';
        document.getElementById('couponDiscountHidden').value = '0';
        document.getElementById('couponDiscountPercentHidden').value = '0';
        debouncedRecalcTotals();
    }

    function recalcTotals() {
                let subtotal = 0;
                let totalDiscountAmount = 0;
                let totalNetRate = 0;
                let totalGst = 0;
                let totalItems = 0;
                let finalTotal = 0;

                Object.entries(itemsData).forEach(([idx, item]) => {
                    const qtyInput = document.querySelector(`.qty[data-idx="${idx}"]`);
                    const qty = qtyInput ? parseInt(qtyInput.value) || 0 : 0;
                    if (qty === 0) return;

                    const { grossPrice, discountRate, netPrice, discountAmount, simpleDiscountedPrice } = item;
                    const itemTotal = Math.round(simpleDiscountedPrice * qty); // Match pdf_generation.php

                    document.querySelectorAll(`[data-idx="${idx}"]`).forEach(row => {
                        const totalEl = row.querySelector('.item-total');
                        if (totalEl) totalEl.textContent = itemTotal; // Display integer
                        const actionButton = row.querySelector('.action-button');
                        if (actionButton) actionButton.disabled = qty === 0;
                    });

                    subtotal += grossPrice * qty;
                    totalDiscountAmount += discountAmount * qty;
                    totalNetRate += (netPrice - discountAmount) * qty;
                    totalGst += (netPrice - discountAmount) * (gstRate / 100) * qty;
                    finalTotal += itemTotal; // Sum of item totals for finalTotal
                    totalItems += qty;
                });

                let couponDiscount = parseFloat(document.getElementById('couponDiscountHidden').value) || 0;
                const couponDiscountPercent = parseFloat(document.getElementById('couponDiscountPercentHidden').value) || 0;
                if (couponDiscountPercent > 0) {
                    couponDiscount = Math.round((totalNetRate * couponDiscountPercent) / 100); // Integer
                    document.getElementById('couponDiscountHidden').value = couponDiscount;
                }
                const discountedNetRate = Math.round(totalNetRate - couponDiscount); // Integer
                const finalGst = Math.round(discountedNetRate * (gstRate / 100)); // Integer
                const displayDiscount = Math.round(subtotal - (totalNetRate + totalGst)); // Integer

                totalDisplay.textContent = '₹' + Math.round(subtotal);
                discountTotalDisplay.textContent = '- ₹' + displayDiscount;
                netRateDisplay.textContent =  Math.round(totalNetRate + totalGst);
                couponDiscountDisplay.textContent = '- ₹' + couponDiscount;
                gstDisplay.textContent = '₹' + finalGst;
                sumgstDisplay.textContent = '₹' + finalGst;
                overallTotalDisplay.textContent = '₹' + finalTotal;
                finalTotalDisplay.textContent = '₹' + finalTotal;
                netTotalSpan.textContent =  subtotal;
                discountTotalSpan.textContent = '- ' + displayDiscount;
                subTotalSpan.textContent = Math.round(totalNetRate + totalGst);
                totalProductsCount.textContent = totalItems;
                afterCouponNetRateDisplay.textContent = '₹' + discountedNetRate;
            }

    const debouncedRecalcTotals = debounce(recalcTotals, 100);

    function applyCoupon() {
        const couponCode = document.getElementById('couponCode').value.trim();
        if (!couponCode) {
            alert('Please enter a coupon code.');
            return;
        }

        fetch('checkcoupon.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'coupon_code=' + encodeURIComponent(couponCode)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const couponDiscount = (netRate * data.discount_percent) / 100;
                    document.getElementById('couponCodeHidden').value = couponCode;
                    document.getElementById('couponDiscountHidden').value = couponDiscount.toFixed(2);
                    document.getElementById('couponDiscountPercentHidden').value = data.discount_percent;
                    alert('Coupon applied successfully! Discount: ' + data.discount_percent + '%');
                } else {
                    document.getElementById('couponCodeHidden').value = '';
                    document.getElementById('couponDiscountHidden').value = '0';
                    document.getElementById('couponDiscountPercentHidden').value = '0';
                    alert('Invalid or expired coupon code.');
                }
                debouncedRecalcTotals();
            })
            .catch(error => {
                console.error('Error checking coupon:', error);
                alert('Error applying coupon. Please try again.');
                document.getElementById('couponCodeHidden').value = '';
                document.getElementById('couponDiscountHidden').value = '0';
                document.getElementById('couponDiscountPercentHidden').value = '0';
                debouncedRecalcTotals();
            });
    }

    function attachEventListeners() {
        document.querySelector('.table-container').addEventListener('click', function (event) {
            const target = event.target.closest('.minus, .plus');
            if (!target) return;
            const idx = target.dataset.idx;
            const change = target.classList.contains('minus') ? -1 : 1;
            updateQuantity(idx, change);
        });

        document.querySelector('.mobile-cards').addEventListener('click', function (event) {
            const target = event.target.closest('.minus, .plus');
            if (!target) return;
            const idx = target.dataset.idx;
            const change = target.classList.contains('minus') ? -1 : 1;
            updateQuantity(idx, change);
        });

        const debouncedInputHandler = debounce(function (event) {
            const idx = event.target.dataset.idx;
            const value = parseInt(event.target.value) || 0;
            document.querySelectorAll(`.qty[data-idx="${idx}"]`).forEach(inp => {
                inp.value = Math.max(0, value);
            });
            debouncedRecalcTotals();
        }, 100);

        document.querySelector('.table-container').addEventListener('input', function (event) {
            if (event.target.classList.contains('qty')) {
                debouncedInputHandler(event);
            }
        });

        document.querySelector('.mobile-cards').addEventListener('input', function (event) {
            if (event.target.classList.contains('qty')) {
                debouncedInputHandler(event);
            }
        });

        document.getElementById('applyCoupon').addEventListener('click', applyCoupon);
    }

    function collectItems() {
        const items = [];
        Object.entries(itemsData).forEach(([idx, item]) => {
            const qtyInput = document.querySelector(`.qty[data-idx="${idx}"]`);
            const quantity = qtyInput ? parseInt(qtyInput.value) || 0 : 0;
            if (quantity > 0) {
                items.push({
                    id: item.id,                        // Product ID
                    name: item.name,                    // Product Name
                    brand: item.brand,                  // Brand
                    category: item.category,            // Category
                    pieces: item.pieces,                // Pieces (part of Content)
                    items: item.items,                  // Items (part of Content)
                    grossPrice: item.grossPrice,        // Price (Inc. GST)
                    simpleDiscountedPrice: item.simpleDiscountedPrice, // Discounted Price
                    quantity: quantity,                 // Quantity
                    discount: item.discountRate         // Discount Rate (for reference)
                });
            }
        });
        return items;
    }

 

 

    document.querySelectorAll('.brand-button').forEach(button => {
        button.addEventListener('click', function () {
            document.querySelectorAll('.brand-button').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const brand = this.dataset.brand;
            resetQuantities();
            document.querySelector('.category-buttons').style.display = 'flex';
            updateCategoryButtons(brand);
            applyFilters();
        });
    });

    window.addEventListener('load', function () {
        const brandButtons = document.querySelectorAll('.brand-button');
        if (brandButtons.length > 0) {
            brandButtons[0].click();
        }

        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const closeModal = document.querySelector('.modal-close');

        document.querySelectorAll('.image-container').forEach(container => {
            container.addEventListener('click', function () {
                const originalImgPath = this.dataset.originalImgPath;
                if (originalImgPath) {
                    modal.style.display = 'flex';
                    modalImg.src = originalImgPath;
                }
            });
        });

        closeModal.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        lazyLoadImages();
        rebuildCategoryHeaders();
        debouncedRecalcTotals();
    });

    document.getElementById('customerDetailsForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const finalTotal = parseFloat(finalTotalDisplay.textContent.replace('₹', '').replace(',', '')) || 0;
        const minimumOrder = <?php echo $minimumOrder; ?>;

        if (finalTotal < minimumOrder) {
            alert("Please select more crackers to meet the minimum order amount of ₹" + minimumOrder.toFixed(2));
            return;
        }

        const items = collectItems();
        if (items.length === 0) {
            alert("Please select at least one item to proceed");
            return;
        }

        document.getElementById('itemsBought').value = JSON.stringify(items);
        this.submit();
    });

    attachEventListeners();
</script>
        <?php include "includes/footer.php"; ?>
</body>

</html>