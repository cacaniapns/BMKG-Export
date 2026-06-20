<?php
include "service/database_login.php";

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Use prepared statements for security
    $stmt = $db->prepare("SELECT * FROM user WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        header("location: dashboard.php");
        
        if ($remember) {
            // Set cookie for username, with a 30-day expiration
            setcookie("username", $username, time() + (86400 * 30), "/"); 
            // Set cookie for password if desired (note: storing passwords in cookies is not recommended)
            // setcookie("password", $password, time() + (86400 * 30), "/"); 
        } else {
            // Clear cookies if "Remember Me" is not checked
            if (isset($_COOKIE['username'])) {
                setcookie("username", "", time() - 3600, "/");
            }
        }
    } else {
        echo "Email atau password salah!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Login</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <section>
        <div class="form-box">
            <div class="form-value">
                <img src="gambar/BMKG.png" alt="Logo" class="form-image">
                <!-- Login Form -->
                <form action="halaman_login.php" method="POST">
                    <h2>Login</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="email" name="username" required value="<?php echo isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : ''; ?>">
                        <label for="">Email</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="password" required>
                        <label for="">Password</label>
                    </div>
                    <div class="forget">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <label for=""><input type="checkbox" name="remember" <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>Remember Me</label>
                        <a href="reset_password.php">Forget Password</a>
                    </div>
                    <button type="submit" name="login">Log In</button>
                </form>
            </div>
        </div>
    </section>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
