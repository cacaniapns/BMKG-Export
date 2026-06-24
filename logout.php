<?php
// logout.php
session_start();
session_destroy();

// Hapus cookie
if (isset($_COOKIE['username'])) {
    setcookie("username", "", time() - 3600, "/");
}

header("Location: halaman_login.php");
exit();
?>