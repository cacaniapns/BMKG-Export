<?php
// data.php - Redirect ke export_data.php

session_start();

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: halaman_login.php");
    exit();
}

// Ambil semua parameter GET
$params = $_GET;

// Bangun query string
$queryString = http_build_query($params);

// Redirect ke export_data.php
if (!empty($queryString)) {
    header("Location: export_data.php?" . $queryString);
} else {
    header("Location: export_data.php");
}
exit();
?>