<?php
// Koneksi ke database
$mysqli = new mysqli('localhost', 'root', '', 'meteorologi');

// Periksa koneksi
if ($mysqli->connect_errno) {
    echo "Gagal terhubung ke MySQL: " . $mysqli->connect_error;
    exit();
}

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
