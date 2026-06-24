<?php
// test_delete_debug.php
require __DIR__ . '/service/database_login.php';

// Parameter yang akan di-test
$date = '2023-01-02'; // Ganti dengan tanggal yang ingin dihapus
$hour = 0; // Ganti dengan jam yang ingin dihapus
$type = 'temperature'; // temperature, humidity, wind, rainfall

echo "<h2>DEBUG DELETE</h2>";

// Format ID sesuai dengan yang digunakan di delete_data.php
$datalog_id = date('ymd', strtotime($date)) . 'T' . sprintf('%02d', $hour);
$daily_id = date('ymd', strtotime($date)) . 'DT';

echo "Date: " . $date . "<br>";
echo "Hour: " . $hour . "<br>";
echo "Type: " . $type . "<br>";
echo "Datalog ID: " . $datalog_id . "<br>";
echo "Daily ID: " . $daily_id . "<br><br>";

// Cek data di temperature_datalog
echo "<h3>1. Cek di temperature_datalog</h3>";
$stmt = $mysqli->prepare("SELECT * FROM temperature_datalog WHERE id_temperature_data = ?");
$stmt->bind_param("s", $datalog_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✅ Data DITEMUKAN di temperature_datalog!<br>";
    while ($row = $result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    
    // Coba delete
    echo "<br><strong>Mencoba menghapus...</strong><br>";
    $del = $mysqli->prepare("DELETE FROM temperature_datalog WHERE id_temperature_data = ?");
    $del->bind_param("s", $datalog_id);
    $del->execute();
    $affected = $del->affected_rows;
    $del->close();
    
    if ($affected > 0) {
        echo "✅ Data BERHASIL dihapus! (" . $affected . " rows affected)<br>";
    } else {
        echo "❌ GAGAL menghapus data! (0 rows affected)<br>";
    }
} else {
    echo "❌ Data TIDAK ditemukan di temperature_datalog!<br>";
    echo "ID yang dicari: " . $datalog_id . "<br>";
    
    // Tampilkan beberapa data yang ada
    echo "<br><strong>Data yang tersedia di temperature_datalog:</strong><br>";
    $all = $mysqli->query("SELECT id_temperature_data, data_timestamp, dry_bulb_temp FROM temperature_datalog LIMIT 10");
    while ($row = $all->fetch_assoc()) {
        echo "- " . $row['id_temperature_data'] . " | " . $row['data_timestamp'] . " | " . $row['dry_bulb_temp'] . "<br>";
    }
}
$stmt->close();

// Cek data di temperature_data
echo "<br><h3>2. Cek di temperature_data</h3>";
$stmt = $mysqli->prepare("SELECT * FROM temperature_data WHERE id_temperature = ?");
$stmt->bind_param("s", $daily_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✅ Data DITEMUKAN di temperature_data!<br>";
    while ($row = $result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "❌ Data TIDAK ditemukan di temperature_data!<br>";
    echo "ID yang dicari: " . $daily_id . "<br>";
}
$stmt->close();

echo "<br><a href='delete_data.php'>Kembali ke Delete Data</a>";
?>