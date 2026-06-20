<?php
// Koneksi ke database
$mysqli = new mysqli('localhost', 'root', '', 'meteorologi');

// Periksa koneksi
if ($mysqli->connect_errno) {
    echo "Gagal terhubung ke MySQL: " . $mysqli->connect_error;
    exit();
}

// Validasi input
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    // Dapatkan array ID dari POST
    $ids = $_POST['ids'];
    $dataType = filter_input(INPUT_POST, 'data_type', FILTER_SANITIZE_STRING);

    // Validasi jenis data
    if (!in_array($dataType, ['rainfall', 'temperature', 'humidity', 'wind'])) {
        die('Jenis data tidak valid.');
    }

    // Mulai transaksi
    $mysqli->begin_transaction();

    try {
        // Tentukan query DELETE berdasarkan jenis data
        if ($dataType === 'temperature') {
            $query = "DELETE FROM temperature_datalog WHERE id_temperature_data = ?";
        } elseif ($dataType === 'humidity') {
            $query = "DELETE FROM humidity_datalog WHERE id_humidity_data = ?";
        } elseif ($dataType === 'wind') {
            $query = "DELETE FROM wind_datalog WHERE id = ?";
        } else {
            // Untuk rainfall, hapus dulu baris terkait di parameter_cuaca
            $deleteParameterCuacaQuery = "DELETE FROM parameter_cuaca WHERE id_rainfall_data = ?";
            $stmtParameterCuaca = $mysqli->prepare($deleteParameterCuacaQuery);

            foreach ($ids as $id) {
                // Bind parameter untuk parameter_cuaca
                $stmtParameterCuaca->bind_param('i', $id);
                // Eksekusi statement untuk parameter_cuaca
                $stmtParameterCuaca->execute();
            }
            $stmtParameterCuaca->close();

            // Hapus dari rainfall_data
            $query = "DELETE FROM rainfall_data WHERE id_rainfall_data = ?";
        }

        // Persiapkan statement
        if ($stmt = $mysqli->prepare($query)) {
            // Loop untuk setiap ID yang akan dihapus
            foreach ($ids as $id) {
                // Bind parameter
                $stmt->bind_param('i', $id);
                // Eksekusi statement
                $stmt->execute();
            }

            // Commit transaksi
            $mysqli->commit();

            echo "Data berhasil dihapus.";
        } else {
            throw new Exception("Error: " . $mysqli->error);
        }
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi kesalahan
        $mysqli->rollback();
        echo "Terjadi kesalahan: " . $e->getMessage();
    }

} else {
    echo "Tidak ada data yang dipilih untuk dihapus.";
}

// Tutup koneksi
$mysqli->close();
?>
