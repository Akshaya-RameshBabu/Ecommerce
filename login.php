<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";


$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST["identifier"]); // email or mobile
    $password = trim($_POST["password"]);

    // Prepare SQL (check both email and mobile)
    $sql = "SELECT * FROM users WHERE mobile = :id OR email = :id LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["name"];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid login credentials!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
  
<body>
 <?php include "includes/header.php"; ?>
 <div class="flex items-center justify-center bg-gray-100 p-8">
<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
    
    <h2 class="text-2xl font-bold text-center text-green-600 mb-6">Sign In</h2>

    <?php if ($error) { ?>
        <div class="bg-red-100 text-red-600 p-3 rounded mb-4 text-sm">
            <?= $error ?>
        </div>
    <?php } ?>

    <form method="POST">

        <!-- Email / Mobile -->
        <label class="block text-gray-700 font-medium mb-1">Mobile or Email</label>
        <input type="text" name="identifier" required
               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none mb-4"
               placeholder="Enter mobile number or email">

        <!-- Password -->
        <label class="block text-gray-700 font-medium mb-1">Password</label>
        <input type="password" name="password" required
               class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-green-500 outline-none mb-6"
               placeholder="Enter password">
  <p class="mb-4 text-sm">
     <a href="forgot_password.php" class="text-green-600 font-medium">Forgot Password?</a>
    </p>
        <!-- Button -->
        <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white p-3 rounded-lg font-semibold transition">
            Login
        </button>
    </form>

    <p class="text-center text-gray-600 mt-4 text-sm">
        Don't have an account? <a href="register.php" class="text-green-600 font-medium">Register</a>
    </p>

</div>
</div>
  <?php include "includes/footer.php"; ?>
</body>
</html>
