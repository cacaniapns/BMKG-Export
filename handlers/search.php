<?php
// Koneksi ke database
require __DIR__ . '/../service/database_login.php';


// Periksa apakah request menggunakan metode GET dan parameter tanggal ada
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['start_date']) && isset($_GET['end_date'])) {
    // Dapatkan tanggal mulai dan tanggal selesai dari input
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];

    // Redirect ke halaman data.php dengan parameter tanggal
    header("Location: data.php?start_date=$startDate&end_date=$endDate");
    exit();
}

// Tutup koneksi
$mysqli->close();
?>
