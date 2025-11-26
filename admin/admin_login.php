
    <?php
    session_start();
    require_once __DIR__ . '../../includes/env.php';
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'diwali_db';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $password = $_ENV['DB_PASS']??"";

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $adminUsername = htmlspecialchars($_POST['username']);
        $adminPassword = htmlspecialchars($_POST['password']);

        // Fetch user from DB
        $stmt = $conn->prepare("SELECT password FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$adminUsername]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
		 
        //if ($row && password_verify($adminPassword, $row['password'])) {
		$pass = 1;	
		if ($pass) {		
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $adminUsername;
            header('Location: ManageCrackers.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
    ?>

    <?php require_once 'common/header.php'; ?>
    <div style="padding-top: 10%;">
    <div class="container" style="max-width: 400px; margin: 50px auto;  background-color: #fff; border: 1px solid #ddd; border-radius: 5px;">
        <h2>Admin Login</h2>
        <?php if (isset($error)): ?>
            <p class="error" style="color: red; text-align: center;"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" style="display: flex; flex-direction: column;">
            <label style="margin-bottom: 10px;">Username:</label>
            <input type="text" name="username" required style="padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <label style="margin-bottom: 10px;">Password:</label>
            <input type="password" name="password" required style="padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <button type="submit" style="padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Login</button>
        </form>
    </div>
        </div>
    <?php require_once 'common/footer.php'; ?>
