<?php
require_once "dbconf.php";

$info = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = trim($_POST["user_input"]);

    if (empty($input)) {
        $error = "Enter your email or mobile number.";
    } else {
        $sql = "SELECT * FROM users WHERE email = ? OR mobile = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$input, $input]);

        if ($stmt->rowCount() === 0) {
            $error = "No account found with this email or mobile.";
        } else {
            // Here you can send OTP or password reset email
            $info = "A password reset link / OTP has been sent!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body >

 <?php include "includes/header.php"; ?>
 <div class="flex items-center justify-center bg-gray-100 p-8">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">

        <h2 class="text-2xl font-bold mb-4 text-center">Forgot Password</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($info): ?>
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4"><?= $info ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">

            <input type="text" name="user_input" placeholder="Enter Email or Mobile"
                   class="w-full p-3 border rounded-lg">

            <button class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-700">
                Continue
            </button>

            <p class="text-center text-sm mt-3">
                Remember your password?
                <a href="login.php" class="text-green-600 font-medium">Login</a>
            </p>

        </form>

    </div>
</div>
  <?php include "includes/footer.php"; ?>
</body>
</html>
