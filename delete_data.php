<?php
// delete_data.php – UI and logic to delete weather data records
session_start();
require __DIR__ . '/service/database_login.php';

$type = isset($_GET['type']) ? trim($_GET['type']) : 'temperature';
$valid_types = ['temperature', 'humidity', 'wind', 'rainfall'];
if (!in_array($type, $valid_types)) {
    $type = 'temperature';
}

$message = '';
$status = '';

// ===== PROSES DELETE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $post_type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $date      = isset($_POST['date']) ? trim($_POST['date']) : '';
    $hour      = isset($_POST['hour']) ? (int)$_POST['hour'] : 0;

    if (!in_array($post_type, $valid_types) || empty($date)) {
        $message = "Semua field wajib diisi.";
        $status  = "error";
    } else {
        try {
            $mysqli->begin_transaction();

            if ($post_type === 'temperature') {
                $datalog_id = date('ymd', strtotime($date)) . 'T' . sprintf('%02d', $hour);
                $daily_id   = date('ymd', strtotime($date)) . 'DT';

                // 1. Hapus dari temperature_datalog
                $del = $mysqli->prepare("DELETE FROM temperature_datalog WHERE id_temperature_data = ?");
                $del->bind_param("s", $datalog_id);
                $del->execute();
                $del->close();

                // 2. NULL-kan kolom jam di temperature_data
                $col_name = "`" . $hour . ":00`";
                $update = $mysqli->prepare("UPDATE temperature_data SET $col_name = NULL WHERE id_temperature = ?");
                $update->bind_param("s", $daily_id);
                $update->execute();
                $update->close();

                // 3. Update statistik (min, max, avg)
                $calc = $mysqli->prepare("SELECT MIN(dry_bulb_temp) as mn, MAX(dry_bulb_temp) as mx, AVG(dry_bulb_temp) as av FROM temperature_datalog WHERE DATE(data_timestamp) = ?");
                $calc->bind_param("s", $date);
                $calc->execute();
                $stats = $calc->get_result()->fetch_assoc();
                $calc->close();

                // Jika masih ada data, update statistik. Jika tidak, set NULL
                if ($stats && $stats['mn'] !== null) {
                    $upd = $mysqli->prepare("UPDATE temperature_data SET min_temperature = ?, max_temperature = ?, avg_temperature = ? WHERE id_temperature = ?");
                    $upd->bind_param("ddds", $stats['mn'], $stats['mx'], $stats['av'], $daily_id);
                    $upd->execute();
                    $upd->close();
                } else {
                    // Tidak ada data sama sekali untuk tanggal ini
                    $upd = $mysqli->prepare("UPDATE temperature_data SET min_temperature = NULL, max_temperature = NULL, avg_temperature = NULL WHERE id_temperature = ?");
                    $upd->bind_param("s", $daily_id);
                    $upd->execute();
                    $upd->close();
                }

            } elseif ($post_type === 'humidity') {
                $datalog_id = date('ymd', strtotime($date)) . 'H' . sprintf('%02d', $hour);
                $daily_id   = date('ymd', strtotime($date)) . 'RH';

                // 1. Hapus dari humidity_datalog
                $del = $mysqli->prepare("DELETE FROM humidity_datalog WHERE id_humidity_data = ?");
                $del->bind_param("s", $datalog_id);
                $del->execute();
                $del->close();

                // 2. NULL-kan kolom jam di humidity_data
                $col_name = "`" . $hour . ":00`";
                $update = $mysqli->prepare("UPDATE humidity_data SET $col_name = NULL WHERE id_humidity = ?");
                $update->bind_param("s", $daily_id);
                $update->execute();
                $update->close();

                // 3. Update statistik
                $calc = $mysqli->prepare("SELECT MIN(humidity_temp) as mn, MAX(humidity_temp) as mx, AVG(humidity_temp) as av FROM humidity_datalog WHERE DATE(data_timestamp) = ?");
                $calc->bind_param("s", $date);
                $calc->execute();
                $stats = $calc->get_result()->fetch_assoc();
                $calc->close();

                if ($stats && $stats['mn'] !== null) {
                    $upd = $mysqli->prepare("UPDATE humidity_data SET min_humidity = ?, max_humidity = ?, avg_humidity = ? WHERE id_humidity = ?");
                    $upd->bind_param("iiis", $stats['mn'], $stats['mx'], $stats['av'], $daily_id);
                    $upd->execute();
                    $upd->close();
                } else {
                    $upd = $mysqli->prepare("UPDATE humidity_data SET min_humidity = NULL, max_humidity = NULL, avg_humidity = NULL WHERE id_humidity = ?");
                    $upd->bind_param("s", $daily_id);
                    $upd->execute();
                    $upd->close();
                }

            } elseif ($post_type === 'wind') {
                $datalog_id = date('ymd', strtotime($date)) . 'W' . sprintf('%02d', $hour);
                $daily_id   = date('ymd', strtotime($date)) . 'WD';

                // 1. Hapus dari wind_datalog
                $del = $mysqli->prepare("DELETE FROM wind_datalog WHERE id = ?");
                $del->bind_param("s", $datalog_id);
                $del->execute();
                $del->close();

                // 2. NULL-kan kolom jam di wind_data (kecepatan dan arah)
                $col_name_speed = "`" . $hour . ":00`";
                $col_name_dir = "`" . $hour . ":00_dir`";
                $update = $mysqli->prepare("UPDATE wind_data SET $col_name_speed = NULL, $col_name_dir = NULL WHERE id_wind = ?");
                $update->bind_param("s", $daily_id);
                $update->execute();
                $update->close();

                // 3. Update statistik
                $calc_sp = $mysqli->prepare("SELECT MAX(wind_speed) as mx, AVG(wind_speed) as av FROM wind_datalog WHERE DATE(data_timestamp) = ?");
                $calc_sp->bind_param("s", $date);
                $calc_sp->execute();
                $stats_sp = $calc_sp->get_result()->fetch_assoc();
                $calc_sp->close();

                $calc_dir = $mysqli->prepare("SELECT wind_direction, wind_direction_code FROM wind_datalog WHERE DATE(data_timestamp) = ? ORDER BY wind_speed DESC LIMIT 1");
                $calc_dir->bind_param("s", $date);
                $calc_dir->execute();
                $stats_dir = $calc_dir->get_result()->fetch_assoc();
                $calc_dir->close();

                $calc_mode = $mysqli->prepare("SELECT wind_direction_code, COUNT(*) as cnt FROM wind_datalog WHERE DATE(data_timestamp) = ? AND wind_direction_code != 'Calm' GROUP BY wind_direction_code ORDER BY cnt DESC LIMIT 1");
                $calc_mode->bind_param("s", $date);
                $calc_mode->execute();
                $stats_mode = $calc_mode->get_result()->fetch_assoc();
                $calc_mode->close();

                if ($stats_sp && $stats_sp['mx'] !== null) {
                    $mx_sp       = $stats_sp['mx'] ?? 0;
                    $av_sp       = $stats_sp['av'] ?? 0;
                    $mx_dir      = $stats_dir['wind_direction'] ?? 0;
                    $mx_dir_code = $stats_dir['wind_direction_code'] ?? 'Calm';
                    $mode_code   = $stats_mode['wind_direction_code'] ?? 'Calm';

                    $upd = $mysqli->prepare("UPDATE wind_data SET wind_speed_max = ?, wind_direction_max = ?, direction_max_code = ?, wind_speed_avg = ?, wind_direction_avg_code = ? WHERE id_wind = ?");
                    $upd->bind_param("disdss", $mx_sp, $mx_dir, $mx_dir_code, $av_sp, $mode_code, $daily_id);
                    $upd->execute();
                    $upd->close();
                } else {
                    $upd = $mysqli->prepare("UPDATE wind_data SET wind_speed_max = NULL, wind_direction_max = NULL, direction_max_code = NULL, wind_speed_avg = NULL, wind_direction_avg_code = NULL WHERE id_wind = ?");
                    $upd->bind_param("s", $daily_id);
                    $upd->execute();
                    $upd->close();
                }

            } elseif ($post_type === 'rainfall') {
                $daily_id = date('ymd', strtotime($date)) . 'RF';

                // Hapus dari rainfall_data
                $del = $mysqli->prepare("DELETE FROM rainfall_data WHERE id_rainfall_data = ?");
                $del->bind_param("s", $daily_id);
                $del->execute();
                $del->close();
            }

            $mysqli->commit();
            $message = "Data berhasil dihapus dari database!";
            $status  = "success";

        } catch (Throwable $e) {
            $mysqli->rollback();
            $message = "Gagal menghapus data: " . $e->getMessage();
            $status  = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Data</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Layout/Layout/styleheader.css">
</head>
<body>
    <?php include __DIR__ . '/Layout/Layout/header.html'; ?>

    <div class="delete-container">
        <h1>Hapus Data</h1>
        
        <?php
        $range_query = "SELECT MIN(Tanggal) as min_date, MAX(Tanggal) as max_date FROM (
                            SELECT Tanggal FROM temperature_data
                            UNION ALL
                            SELECT Tanggal FROM humidity_data
                            UNION ALL
                            SELECT DATE as Tanggal FROM wind_data
                            UNION ALL
                            SELECT data_timestamp as Tanggal FROM rainfall_data
                          ) as combined_data";
        $range_result = $mysqli->query($range_query);
        if ($range_result && $range_result->num_rows > 0) {
            $range_row = $range_result->fetch_assoc();
            echo "<p class='data-range-info'>Data yang tersedia pada Database: " . htmlspecialchars($range_row['min_date']) . " sampai " . htmlspecialchars($range_row['max_date']) . "</p>";
            $range_result->close();
        } else {
            echo "<p class='data-range-info'>Tidak ada data yang tersedia.</p>";
        }
        ?>

        <?php if ($message): ?>
            <div class="alert-box alert-<?php echo $status; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form class="delete-form" action="delete_data.php" method="POST" id="deleteForm">
            <div class="form-row">
                <div class="form-col">
                    <label for="paramType">Pilih Data:</label>
                    <select name="type" id="paramType" required>
                        <option value="temperature" <?php echo $type === 'temperature' ? 'selected' : ''; ?>>Suhu (Temperature)</option>
                        <option value="humidity" <?php echo $type === 'humidity' ? 'selected' : ''; ?>>Kelembaban (Humidity)</option>
                        <option value="wind" <?php echo $type === 'wind' ? 'selected' : ''; ?>>Angin (Wind)</option>
                        <option value="rainfall" <?php echo $type === 'rainfall' ? 'selected' : ''; ?>>Curah Hujan (Rainfall)</option>
                    </select>
                </div>
                <div class="form-col">
                    <label for="dateInput">Tanggal:</label>
                    <input type="date" name="date" id="dateInput" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="form-row" id="hourGroup">
                <div class="form-col">
                    <label for="hourInput">Jam (Pukul):</label>
                    <select name="hour" id="hourInput">
                        <?php for($h = 0; $h < 24; $h++): ?>
                            <option value="<?php echo $h; ?>"><?php echo sprintf('%02d:00', $h); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-col"></div>
            </div>

            <div class="current-value-info" id="currentValueInfo">
                <strong id="currentValueText">-</strong>
            </div>

            <button type="submit" name="confirm_delete" class="btn-delete">Hapus Data</button>
        </form>

        <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon">⚠️</div>
            <div class="modal-title">Konfirmasi Hapus</div>
            <div class="modal-message">Yakin ingin menghapus data ini? Tindakan ini <strong>tidak dapat dibatalkan</strong>.</div>
            <div class="modal-detail" id="modalDetail">
                <strong>Data:</strong> <span id="modalDataType">-</span><br>
                <strong>Tanggal:</strong> <span id="modalDate">-</span><br>
                <strong>Jam:</strong> <span id="modalHour">-</span>
            </div>
            <div class="modal-buttons">
                <button class="btn-cancel" id="btnCancel">Batal</button>
                <button class="btn-confirm-delete" id="btnConfirmDelete">Hapus</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/Layout/Layout/footer.html'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paramType = document.getElementById('paramType');
            const dateInput = document.getElementById('dateInput');
            const hourInput = document.getElementById('hourInput');
            const hourGroup = document.getElementById('hourGroup');
            const currentValueInfo = document.getElementById('currentValueInfo');
            const currentValueText = document.getElementById('currentValueText');
            const deleteForm = document.getElementById('deleteForm');
            const deleteModal = document.getElementById('deleteModal');
            const btnCancel = document.getElementById('btnCancel');
            const btnConfirmDelete = document.getElementById('btnConfirmDelete');
            const modalDataType = document.getElementById('modalDataType');
            const modalDate = document.getElementById('modalDate');
            const modalHour = document.getElementById('modalHour');

            function updateUIState() {
                const selected = paramType.value;
                
                if (selected === 'rainfall') {
                    hourGroup.style.display = 'none';
                    hourInput.required = false;
                } else {
                    hourGroup.style.display = 'flex';
                    hourInput.required = true;
                }
                
                fetchCurrentValue();
            }

            function fetchCurrentValue() {
                const type = paramType.value;
                const date = dateInput.value;
                const hour = hourInput.value;

                if (!date) {
                    currentValueInfo.style.display = 'none';
                    return;
                }

                let url = `handlers/get_current_value.php?type=${type}&date=${date}`;
                if (type !== 'rainfall') {
                    url += `&hour=${hour}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        currentValueInfo.style.display = 'block';
                        if (data.status === 'success' && data.exists) {
                            currentValueInfo.className = 'current-value-info';
                            
                            let valStr = data.value;
                            if (type === 'temperature') valStr += ' °C';
                            else if (type === 'humidity') valStr += ' %';
                            else if (type === 'rainfall') valStr += ' mm';
                            else if (type === 'wind') {
                                valStr += ` m/s (Arah: ${data.wind_direction}° / ${data.wind_direction_code ?? '-'})`;
                            }
                            
                            currentValueText.textContent = 'Nilai saat ini: ' + valStr;
                        } else {
                            currentValueInfo.className = 'current-value-info warn';
                            currentValueText.textContent = 'Data tidak ditemukan.';
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching value:', err);
                        currentValueInfo.style.display = 'none';
                    });
            }

            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const type = paramType.options[paramType.selectedIndex].text;
                const date = dateInput.value;
                const hour = hourInput.value;

                const dateFormatted = date ? date.split('-').reverse().join('/') : '-';
                const hourFormatted = hour !== '' ? String(hour).padStart(2, '0') + ':00' : '-';

                modalDataType.textContent = type;
                modalDate.textContent = dateFormatted;
                modalHour.textContent = hourFormatted;

                deleteModal.classList.add('active');
            });

            btnCancel.addEventListener('click', function() {
                deleteModal.classList.remove('active');
            });

            deleteModal.addEventListener('click', function(e) {
                if (e.target === deleteModal) {
                    deleteModal.classList.remove('active');
                }
            });

            btnConfirmDelete.addEventListener('click', function() {
                deleteModal.classList.remove('active');
                deleteForm.submit();
            });

            paramType.addEventListener('change', updateUIState);
            dateInput.addEventListener('change', fetchCurrentValue);
            hourInput.addEventListener('change', fetchCurrentValue);

            updateUIState();
        });
    </script>
</body>
</html>