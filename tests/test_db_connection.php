<?php
/**
 * MySQL Diagnostics
 * Cek apakah MySQL berjalan dan apa yang tersedia
 */

echo "<h1>Diagnosa MySQL</h1>";

// Test 1: Coba koneksi tanpa database tertentu
echo "<h2>1. Test Koneksi ke MySQL Server (tanpa database)</h2>";
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect('localhost', 'root', '');

if ($conn) {
    echo "<p style='color: green; font-weight: bold;'>✓ MySQL Server AKTIF!</p>";
    
    // Tampilkan versi
    echo "<p><strong>MySQL Version:</strong> " . $conn->server_info . "</p>";
    
    // Lihat database apa saja yang ada
    echo "<h3>Database yang Tersedia:</h3>";
    $result = $conn->query("SHOW DATABASES;");
    if ($result) {
        $databases = [];
        while ($row = $result->fetch_row()) {
            $databases[] = $row[0];
        }
        echo "<ul>";
        foreach ($databases as $db) {
            echo "<li><code>" . htmlspecialchars($db) . "</code>";
            if ($db === 'meteorologi') {
                echo " <strong style='color: green;'>(TARGET DATABASE DITEMUKAN!)</strong>";
            }
            echo "</li>";
        }
        echo "</ul>";
    }
    
    // Cek user yang ada
    echo "<h3>User MySQL yang Terdaftar:</h3>";
    $result = $conn->query("SELECT User, Host FROM mysql.user;");
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>User</th><th>Host</th></tr>";
        while ($row = $result->fetch_row()) {
            echo "<tr><td>" . htmlspecialchars($row[0]) . "</td><td>" . htmlspecialchars($row[1]) . "</td></tr>";
        }
        echo "</table>";
    }
    
    $conn->close();
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ MySQL Server TIDAK MERESPONS</p>";
    echo "<p style='background-color: #ffcccc; padding: 10px; border-radius: 5px;'>";
    echo "<strong>Solusi:</strong><br>";
    echo "1. Pastikan Laragon sudah dijalankan (klik Start All di Laragon)<br>";
    echo "2. Tunggu 2-3 detik sampai MySQL start<br>";
    echo "3. Refresh halaman ini<br>";
    echo "<br>";
    echo "Jika masih error, coba:<br>";
    echo "- Buka Laragon > Tombol Menu (≡) > MySQL > Start<br>";
    echo "- Atau buka phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>";
    echo "</p>";
    exit;
}

// Test 2: Coba akses ke database meteorologi
echo "<h2>2. Test Akses ke Database 'meteorologi'</h2>";
$conn = @mysqli_connect('localhost', 'root', '', 'meteorologi');

if ($conn) {
    echo "<p style='color: green; font-weight: bold;'>✓ Database 'meteorologi' ACCESSIBLE!</p>";
    
    // Lihat tabel apa saja
    echo "<h3>Tabel dalam database:</h3>";
    $result = $conn->query("SHOW TABLES;");
    if ($result && $result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_row()) {
            echo "<li><code>" . htmlspecialchars($row[0]) . "</code></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ Database ada tapi kosong (tidak ada tabel).</p>";
    }
    
    $conn->close();
    
    echo "<p style='background-color: #ccffcc; padding: 10px; border-radius: 5px;'>";
    echo "<strong>✓ Semua OK! Database siap digunakan.</strong><br>";
    echo "Edit <code>c:\\laragon\\www\\BMKG\\service\\service\\database_login.php</code> dengan:<br>";
    echo "<code style='display: block; margin-top: 5px;'>";
    echo "\$hostname = \"localhost\";<br>";
    echo "\$username = \"root\";<br>";
    echo "\$password = \"\";<br>";
    echo "\$database_name = \"meteorologi\";<br>";
    echo "</code>";
    echo "</p>";
} else {
    echo "<p style='color: orange;'>⚠ Tidak bisa akses 'meteorologi'</p>";
    echo "<p>Kemungkinan database belum dibuat. Langkah membuat database:</p>";
    echo "<ol>";
    echo "<li>Buka <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
    echo "<li>Login jika diminta (biasanya User: root, Password: kosong)</li>";
    echo "<li>Klik <strong>New</strong> atau <strong>Create Database</strong></li>";
    echo "<li>Masukkan nama: <code>meteorologi</code></li>";
    echo "<li>Klik Create</li>";
    echo "<li>Refresh halaman ini</li>";
    echo "</ol>";
}

?>
