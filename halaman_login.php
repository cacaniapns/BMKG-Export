<?php
include "service/database_login.php";

session_start();

if (isset($_POST['login'])) {
    $email = $_POST['username']; // Ini sebenarnya email
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Hash password dengan SHA2 (sesuai database)
    $hashed_password = hash('sha256', $password);

    // Query ke tabel users (bukan user)
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $hashed_password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Simpan session
        $_SESSION['user_id'] = $data['id'];
        $_SESSION['username'] = $data['email'];
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['login_time'] = time();
        
        if ($remember) {
            // Set cookie untuk email, berlaku 30 hari
            setcookie("username", $email, time() + (86400 * 30), "/");
        } else {
            // Clear cookies jika "Remember Me" tidak dicentang
            if (isset($_COOKIE['username'])) {
                setcookie("username", "", time() - 3600, "/");
            }
        }
        
        // Redirect ke dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Email atau password salah!');</script>";
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
                <img src="gambar/gambar/BMKG.png" alt="Logo" class="form-image">
                <!-- Login Form -->
                <form action="halaman_login.php" method="POST">
                    <h2>Login</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <label for="">Email</label>
                        <input type="email" name="username" required value="<?php echo isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : ''; ?>">
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <label for="">Password</label>
                        <input type="password" name="password" required>
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