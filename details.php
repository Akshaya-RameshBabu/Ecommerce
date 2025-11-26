<?php
require_once 'vendor/autoload.php';

require_once __DIR__ . "./includes/env.php";
require_once __DIR__ . "/admin/config.php";
$minimumOrder = 200; 


$gstRate = isset($settings['gst_rate']) ? floatval($settings['gst_rate']) : 18;
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
    <link rel="stylesheet" href="./Styles.css">
   
</head>

<body>
        <?php include "./includes/header.php"; ?>
    


                <div class="details-container m-5">
                    <div class="summary">
                        <div><span><strong>Total (Inc. GST)</strong></span><span id="total">₹0.00</span></div>
                        <div><span style="color:red">Discount %</span><span id="discountTotal" style="color:red;">₹0.00</span></div>
                        <div><span>Net Rate</span><span id="netRate">₹0.00</span></div>
                        <!-- <div>
                            <span class="w-75 coupon-disc" style="color:red">Coupon Discount : <input type="text" id="couponCode"
                                    name="coupon_code" placeholder="Enter coupon code" class="coupon-input ">
                                <button type="button" id="applyCoupon" class="continue-buttons w-25">Apply Coupon</button>
                            </span>
                            <span id="couponDiscount" style="color:red">₹0.00</span>
                        </div> -->
                        <span style="display:none">
                        <div><span><strong>Overall Total</strong></span><span id="overallTotal">₹0.00</span></div>
                        <div><span><strong>Item Price</strong></span><span id="afterCouponNetRate">₹0.00</span></div>
                        <div><span>Inclusive GST (<?php echo $gstRate; ?>%)</span><span id="gst">₹0.00</span></div></span>
                        <div><span class="finalTotal"><strong>Final Total</strong></span><span
                                class="finalTotal" id="finalTotal">₹0.00</span></div>
                        <div class="minimum-order"><span>Minimum
                                Order</span><span>₹<?php echo number_format($minimumOrder); ?></span></div>
                    </div>
                    <h2 style="text-align: center; color: #1f2937; font-weight: bold; margin-bottom: 1.5rem;">Enter Your Details
                    </h2>
                    <form id="customerDetailsForm" action="place_order.php" method="POST" target="_blank">
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

    
        </section>

        <script>
             const gstRate = <?php echo $gstRate; ?>;

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
    const overallTotalDisplay = document.getElementById('overallTotal');
    const afterCouponNetRateDisplay = document.getElementById('afterCouponNetRate');
    const finalTotalDisplay = document.getElementById('finalTotal');
    const downloadPriceList = document.getElementById('downloadPriceList');

 


  
    function getCartItems() {
    return JSON.parse(localStorage.getItem("cart")) || [];
}


    function recalcTotals() {
    console.log("Recalculating totals...");

    let subtotal = 0;
    let totalDiscountAmount = 0;
    let totalNetRate = 0;
    let totalGst = 0;
    let totalItems = 0;
    let finalTotal = 0;

    const cartItems = getCartItems();

    cartItems.forEach((item) => {
        const qty = item.quantity || 0;
        if (qty === 0) return;

        const grossPrice = parseFloat(item.oldamt) || 0;
        const discountRate = parseFloat(item.discountRate) || 0;
        const gstRate = parseFloat(item.gst) || 0;

        // 1️⃣ Calculate discount
        const discountAmount = Math.round((grossPrice * discountRate) / 100);
        const discountedPrice = grossPrice - discountAmount;

        // 2️⃣ GST on discounted amount
        const gstAmount = Math.round((discountedPrice * gstRate) / 100);

        // 3️⃣ Final price per unit
        const finalUnitPrice = discountedPrice + gstAmount;

        // 4️⃣ Line total
        const itemTotal = Math.round(finalUnitPrice * qty);
     // Update totals
        subtotal += grossPrice * qty;
        totalDiscountAmount += discountAmount * qty;
        totalNetRate += discountedPrice * qty;
        totalGst += gstAmount * qty;
        finalTotal += itemTotal;
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
                // couponDiscountDisplay.textContent = '- ₹' + couponDiscount;
                 gstDisplay.textContent = '₹' + finalGst;
                overallTotalDisplay.textContent = '₹' + finalTotal;
                finalTotalDisplay.textContent = '₹' + finalTotal;
                afterCouponNetRateDisplay.textContent = '₹' + discountedNetRate;
            }
  function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    const debouncedRecalcTotals = debounce(recalcTotals, 100);

    function applyCoupon() {
        console.log("Applying coupon...");
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

   

  function collectItems() {
        const itemsData = getCartItems();
        const items = [];
        itemsData.forEach((item) => {
           console.log(item);
           let grossPrice=parseFloat(item.oldamt) || 0;
           let simpleDiscountedPrice=item.price;
                items.push({
                    id: item.id,                        // Product ID
                    name: item.name,                    // Product Name
                    brand: item.brand,                  // Brand
                    category: item.category,            // Category
                    pieces: item.pieces,                // Pieces (part of Content)
                    items: item.items,                  // Items (part of Content)
                    grossPrice: grossPrice,        // Price (Inc. GST)
                    simpleDiscountedPrice: simpleDiscountedPrice, // Discounted Price
                    quantity: item.quantity,                 // Quantity
                    discount: item.discountRate         // Discount Rate (for reference)
                });
            
        });
        return items;
    }



  

   

 


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
recalcTotals();
//  document.addEventListener("DOMContentLoaded", function () {
//     document.getElementById('applyCoupon').addEventListener('click', applyCoupon);
// });

</script>
        <?php include "includes/footer.php"; ?>
</body>

</html>