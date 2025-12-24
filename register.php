<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$success = "";

/* --------------------------------
   STEP 1: REGISTRATION + SEND OTP
---------------------------------*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {

    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $mobile   = trim($_POST["mobile"]);
    $password = trim($_POST["password"]);

    if (!$name || !$email || !$password) {
        $errors[] = "All required fields are mandatory";
    }

    if (!$errors) {
        $check = $conn->prepare("SELECT id FROM users WHERE email=? OR mobile=?");
        $check->execute([$email, $mobile]);

        if ($check->rowCount() > 0) {
            $errors[] = "Email or Mobile already registered";
        } else {

            $hash = password_hash($password, PASSWORD_BCRYPT);

            $conn->prepare(
                "INSERT INTO users (name,email,mobile,password_hash)
                 VALUES (?,?,?,?)"
            )->execute([$name, $email, $mobile, $hash]);

            $userId = $conn->lastInsertId();
            $_SESSION['verify_uid'] = $userId;

            // Generate OTP
            $otp = rand(100000, 999999);
            $otpHash = password_hash($otp, PASSWORD_BCRYPT);

            $conn->prepare(
                "INSERT INTO password_resets (user_id, otp_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))"
            )->execute([$userId, $otpHash]);

            // Send Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = MAIL_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_MAIL;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                $mail->setFrom(SMTP_MAIL, "Ecommerce App");
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Email Verification OTP";
                $mail->Body = "
                    <h3>Email Verification</h3>
                    <p>Your OTP:</p>
                    <h2>$otp</h2>
                    <p>Valid for 10 minutes.</p>
                ";

                $mail->send();
                $success = "OTP sent to your email";

            } catch (Exception $e) {
                $errors[] = "Mail error: " . $mail->ErrorInfo;
            }
        }
    }
}

/* --------------------------
   STEP 2: VERIFY OTP
---------------------------*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify'])) {

    $otp = $_POST['otp'];
    $userId = $_SESSION['verify_uid'] ?? null;

    $stmt = $conn->prepare("
        SELECT * FROM password_resets
        WHERE user_id=? AND used=0 AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($otp, $row['otp_hash'])) {
        $errors[] = "Invalid or expired OTP";
    } else {
        $conn->prepare("UPDATE password_resets SET used=1 WHERE id=?")
             ->execute([$row['id']]);

        unset($_SESSION['verify_uid']);
        $success = "Email verified successfully! You can now login.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register </title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body >
<?php include "includes/header.php"; ?>
 <div class="flex items-center justify-center bg-gray-100 p-8">
    
<div class="bg-white p-8 rounded shadow w-full max-w-md">

    <h2 class="text-2xl font-bold mb-4 text-center">
        <?= isset($_SESSION['verify_uid']) ? "Verify OTP" : "Create Account" ?>
    </h2>

    <?php if ($errors): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
            <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['verify_uid'])): ?>

    <!-- REGISTER FORM -->
    <form method="POST" class="space-y-4">
        <input type="text" name="name" placeholder="Full Name" required class="w-full p-3 border rounded">
        <input type="email" name="email" placeholder="Email" required class="w-full p-3 border rounded">
        <input type="text" name="mobile" placeholder="Mobile" class="w-full p-3 border rounded">
        <input type="password" name="password" placeholder="Password" required class="w-full p-3 border rounded">
        <button name="register" class="w-full bg-green-600 text-white p-3 rounded">
            Register & Send OTP
        </button>
    </form>

    <?php else: ?>

    <!-- OTP FORM -->
    <form method="POST" class="space-y-4">
        <input type="text" name="otp" placeholder="Enter OTP" required class="w-full p-3 border rounded">
        <button name="verify" class="w-full bg-blue-600 text-white p-3 rounded">
            Verify OTP
        </button>
    </form>

    <?php endif; ?>

</div>
    </div>
    </div>
  <?php include "includes/footer.php"; ?>
</body>
</html>
