<?php

include "service/database_login.php";

$notification = "";

if (isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];

    // Update the password
    $update_sql = "UPDATE user SET password='$new_password' WHERE username='$email'";
    if ($db->query($update_sql) === TRUE) {
        $notification = "Password berhasil diubah.";
    } else {
        $notification = "Gagal mengubah password: " . $db->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <section>
        <div class="form-box">
            <div class="form-value">
                <img src="gambar/BMKG.png" alt="Logo" class="form-image">
                <!-- Notification Message -->
                <?php if ($notification): ?>
                    <div class="notification"><?php echo $notification; ?></div>
                <?php endif; ?>
                <!-- Reset Password Form -->
                <form action="reset_password.php" method="POST">
                    <h2>Reset Password</h2>
                    <div class="inputbox">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="email" name="email" required>
                        <label for="">Email</label>
                    </div>
                    <div class="inputbox">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="new_password" required>
                        <label for="">New Password</label>
                    </div>
                    <button type="submit" name="reset_password">Reset Password</button>
                </form>
                <!-- Back to Login Button -->
                <a href="halaman_login.php" class="back-to-login">Back to Login</a>
            </div>
        </div>
    </section>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <style>
        .back-to-login {
            display: block;
            margin-top: 10px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }
        .back-to-login:hover {
            text-decoration: underline;
        }
        .notification {
            margin-bottom: 5px; /* Adjusted to space out from the form */
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</body>
</html>
