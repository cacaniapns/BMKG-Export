<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Data</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Layout/Layout/styleheader.css">
    <style>
        /* Tambahan style untuk full width table */
        .full-width-section {
            width: 100vw;
            max-width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            padding: 0 20px;
            background: #f5f5f5;
        }
        .full-width-section .hourly-table-wrapper {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        .full-width-section .data-table-container {
            margin: 0;
            border-radius: 0;
            box-shadow: none;
        }
        @media (max-width: 768px) {
            .full-width-section {
                padding: 0 10px;
            }
        }

        /* Style notifikasi */
        .alert-box {
            padding: 14px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 0.97em;
            text-align: center;
        }
        .alert-success {
            background-color: #e8f5e9;
            border: 1px solid #a5d6a7;
            color: #2e7d32;
        }
        .alert-error {
            background-color: #ffebee;
            border: 1px solid #ef9a9a;
            color: #c62828;
        }
        .alert-warning {
            background-color: #fff3e0;
            border: 1px solid #ffcc80;
            color: #e65100;
        }
        .alert-info {
            background-color: #e3f2fd;
            border: 1px solid #90caf9;
            color: #0d47a1;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/Layout/Layout/header.html'; ?>
    <?php
    if (isset($_GET['debug']) && $_GET['debug']) {
        header('Content-Type: text/plain');
        echo "PHP Version: " . phpversion() . "\n";
        echo "File mtime: " . date('c', filemtime(__FILE__)) . "\n";
        echo "File MD5: " . md5_file(__FILE__) . "\n";
        exit;
    }
    ?>
    
    <div class="container">
        <h1>Cari Data</h1>
        <?php
        require __DIR__ . '/service/database_login.php';

        $query = "SELECT MIN(Tanggal) as min_date, MAX(Tanggal) as max_date FROM (
                    SELECT Tanggal FROM temperature_data
                    UNION ALL
                    SELECT Tanggal FROM humidity_data
                    UNION ALL
                    SELECT DATE FROM wind_data
                    UNION ALL
                    SELECT data_timestamp as Tanggal FROM rainfall_data
                  ) as combined_data";
        
        $result = $mysqli->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $minDate = $row['min_date'];
            $maxDate = $row['max_date'];
            echo "<p class='data-range-info'>Data yang tersedia pada Database: " . htmlspecialchars($minDate) . " sampai " . htmlspecialchars($maxDate) . "</p>";
        } else {
            echo "<p class='data-range-info'>Tidak ada data yang tersedia.</p>";
        }
        $result->close();

        // ===== CEK APAKAH ADA DATA =====
        $check_data_query = "SELECT COUNT(*) as total FROM (
                                SELECT 1 FROM temperature_data
                                UNION ALL
                                SELECT 1 FROM humidity_data
                                UNION ALL
                                SELECT 1 FROM wind_data
                                UNION ALL
                                SELECT 1 FROM rainfall_data
                              ) as all_data";
        $check_result = $mysqli->query($check_data_query);
        $hasData = false;
        if ($check_result) {
            $row = $check_result->fetch_assoc();
            $hasData = ($row['total'] > 0);
            $check_result->close();
        }
        ?>

        <?php if (!$hasData): ?>
            <div class="alert-box alert-warning">
                ⚠️ Belum ada data yang tersimpan di database. Silakan upload data terlebih dahulu.
            </div>
        <?php endif; ?>

        <form class="search-form" action="export_data.php" method="GET">
            <div class="form-row">
                <div class="form-col">
                    <label for="start_date">Tanggal Mulai:</label>
                    <input type="date" id="start_date" name="start_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-col">
                    <label for="end_date">Tanggal Selesai:</label>
                    <input type="date" id="end_date" name="end_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <fieldset>
                <legend>Pilih Variabel Data (bisa lebih dari satu):</legend>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="variables[]" value="rainfall"> Curah Hujan</label>
                    <label><input type="checkbox" name="variables[]" value="temperature"> Suhu</label>
                    <label><input type="checkbox" name="variables[]" value="humidity"> Kelembapan</label>
                    <label><input type="checkbox" name="variables[]" value="wind"> Angin</label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Tipe Data (pilih salah satu):</legend>
                <div class="radio-group">
                    <label><input type="radio" name="time_type" value="hourly" required> Jam</label>
                    <label><input type="radio" name="time_type" value="daily" required> Hari</label>
                    <label><input type="radio" name="time_type" value="monthly" required> Bulan</label>
                </div>
            </fieldset>

            <button type="submit" class="btn-search">Cari</button>
        </form>

        <script>
            (function(){
                const checkboxes = document.querySelectorAll('input[name="variables[]"]');
                const hourlyRadio = document.querySelector('input[name="time_type"][value="hourly"]');
                const dailyRadio = document.querySelector('input[name="time_type"][value="daily"]');

                function updateHourlyState(){
                    let hasDisallowed = false;
                    checkboxes.forEach(cb => {
                        if (cb.checked && cb.value === 'rainfall') {
                            hasDisallowed = true;
                        }
                    });
                    if (!hourlyRadio) return;
                    if (hasDisallowed) {
                        hourlyRadio.disabled = true;
                        hourlyRadio.closest('label')?.classList?.add('disabled');
                        if (hourlyRadio.checked && dailyRadio) {
                            dailyRadio.checked = true;
                        }
                    } else {
                        hourlyRadio.disabled = false;
                        hourlyRadio.closest('label')?.classList?.remove('disabled');
                    }
                }

                checkboxes.forEach(cb => cb.addEventListener('change', updateHourlyState));
                document.addEventListener('DOMContentLoaded', updateHourlyState);
                updateHourlyState();

                const form = document.querySelector('.search-form');
                const startInput = document.getElementById('start_date');
                const endInput = document.getElementById('end_date');
                if (form) {
                    let msgDiv = document.createElement('div');
                    msgDiv.id = 'date-error';
                    form.appendChild(msgDiv);

                    form.addEventListener('submit', function(e){
                        const s = startInput ? startInput.value.trim() : '';
                        const en = endInput ? endInput.value.trim() : '';
                        if (!s || !en) {
                            e.preventDefault();
                            msgDiv.textContent = 'Harap isi Tanggal Mulai dan Tanggal Selesai.';
                            if (startInput) startInput.focus();
                            return false;
                        }
                        msgDiv.textContent = '';
                        return true;
                    });
                }
            })();
        </script>

        <?php
        $startDate = filter_input(INPUT_GET, 'start_date', FILTER_DEFAULT);
        $endDate = filter_input(INPUT_GET, 'end_date', FILTER_DEFAULT);
        $variables = isset($_GET['variables']) && is_array($_GET['variables']) ? $_GET['variables'] : [];
        $timeType = filter_input(INPUT_GET, 'time_type', FILTER_DEFAULT);

        // Jika tidak ada parameter, set default ke hari ini
        if (empty($startDate) && empty($endDate) && empty($variables) && empty($timeType)) {
            // Tidak ada pencarian, hanya tampilkan form
        } else {
            // Proses pencarian
            $validVariables = ['rainfall', 'temperature', 'humidity', 'wind'];
            $variables = array_filter($variables, function($v) use ($validVariables) {
                return in_array($v, $validVariables);
            });

            if (!in_array($timeType, ['hourly', 'daily', 'monthly'])) {
                $timeType = '';
            }

            if ($timeType === 'hourly' && in_array('rainfall', $variables)) {
                echo '<div class="alert-box alert-error">Tipe "Jam" tidak tersedia untuk Curah Hujan. Pilih "Hari" atau "Bulan".</div>';
            } else {
                function getMergedDataByVariablesAndTime($mysqli, $startDate, $endDate, $variables, $timeType) {
                    if (empty($variables)) {
                        return [];
                    }

                    if ($timeType === 'daily' || $timeType === 'monthly') {
                        $baseTable = null;
                        $baseDateCol = null;
                        
                        if (in_array('temperature', $variables)) {
                            $baseTable = 'temperature_data';
                            $baseDateCol = 't.Tanggal';
                        } elseif (in_array('humidity', $variables)) {
                            $baseTable = 'humidity_data';
                            $baseDateCol = 'h.Tanggal';
                        } elseif (in_array('wind', $variables)) {
                            $baseTable = 'wind_data';
                            $baseDateCol = 'w.DATE';
                        } elseif (in_array('rainfall', $variables)) {
                            $baseTable = 'rainfall_data';
                            $baseDateCol = 'r.data_timestamp';
                        }

                        if ($timeType === 'monthly') {
                            $dateSelect = "ANY_VALUE(DATE_FORMAT($baseDateCol, '%Y-%m-01')) as tanggal";
                            $groupBy = "GROUP BY YEAR($baseDateCol), MONTH($baseDateCol)";
                            $orderByDate = "YEAR($baseDateCol), MONTH($baseDateCol)";
                        } else {
                            $dateSelect = "ANY_VALUE(DATE_FORMAT($baseDateCol, '%Y-%m-%d')) as tanggal";
                            $groupBy = "GROUP BY DATE($baseDateCol)";
                            $orderByDate = "DATE($baseDateCol)";
                        }

                        $query = "SELECT " . $dateSelect;
                        
                        if (in_array('temperature', $variables)) {
                            $fromClause = "FROM temperature_data t";
                            $query .= ", MAX(t.max_temperature) as temp_max, MIN(t.min_temperature) as temp_min, AVG(t.avg_temperature) as temp_avg";
                        } else {
                            $fromClause = "FROM " . $baseTable . " " . (in_array('humidity', $variables) ? 'h' : (in_array('wind', $variables) ? 'w' : 'r'));
                        }
                        
                        if (in_array('temperature', $variables)) {
                            $whereClause = "WHERE t.Tanggal BETWEEN ? AND ?";
                        } elseif (in_array('humidity', $variables)) {
                            $whereClause = "WHERE h.Tanggal BETWEEN ? AND ?";
                        } elseif (in_array('wind', $variables)) {
                            $whereClause = "WHERE w.DATE BETWEEN ? AND ?";
                        } else {
                            $whereClause = "WHERE r.data_timestamp BETWEEN ? AND ?";
                        }
                        
                        if (in_array('humidity', $variables) && !in_array('temperature', $variables)) {
                            $query .= ", MAX(h.max_humidity) as humid_max, MIN(h.min_humidity) as humid_min, AVG(h.avg_humidity) as humid_avg";
                        } elseif (in_array('humidity', $variables)) {
                            $fromClause .= " LEFT JOIN humidity_data h ON DATE(t.Tanggal) = DATE(h.Tanggal)";
                            $query .= ", MAX(h.max_humidity) as humid_max, MIN(h.min_humidity) as humid_min, AVG(h.avg_humidity) as humid_avg";
                        }
                        
                        if (in_array('wind', $variables)) {
                            if (!in_array('temperature', $variables)) {
                                if (in_array('humidity', $variables)) {
                                    $fromClause .= " LEFT JOIN wind_data w ON DATE(h.Tanggal) = DATE(w.DATE)";
                                } else {
                                    $fromClause = "FROM wind_data w";
                                    $whereClause = "WHERE w.DATE BETWEEN ? AND ?";
                                }
                            } else {
                                $fromClause .= " LEFT JOIN wind_data w ON DATE(t.Tanggal) = DATE(w.DATE)";
                            }
                            $query .= ", MAX(w.wind_speed_max) as wind_speed_max, (SELECT MIN(wd_min.wind_speed) FROM wind_datalog wd_min WHERE DATE(wd_min.data_timestamp) = DATE(ANY_VALUE(w.DATE))) as wind_speed_min, ANY_VALUE(w.wind_direction_max) as wind_dir_max, AVG(w.wind_speed_avg) as wind_speed_avg, (SELECT wd2.wind_direction_avg_code FROM wind_data wd2 WHERE wd2.wind_direction_avg_code IS NOT NULL AND wd2.wind_direction_avg_code != '' AND wd2.wind_direction_avg_code != 'Calm' AND DATE(wd2.DATE) = DATE(ANY_VALUE(w.DATE)) GROUP BY wd2.wind_direction_avg_code ORDER BY COUNT(*) DESC LIMIT 1) as wind_dir_avg";
                        }
                        
                        if (in_array('rainfall', $variables)) {
                            if (!in_array('temperature', $variables) && !in_array('humidity', $variables) && !in_array('wind', $variables)) {
                                $fromClause = "FROM rainfall_data r";
                                $whereClause = "WHERE r.data_timestamp BETWEEN ? AND ?";
                            } elseif (in_array('temperature', $variables)) {
                                $fromClause .= " LEFT JOIN rainfall_data r ON DATE(t.Tanggal) = DATE(r.data_timestamp)";
                            } elseif (in_array('humidity', $variables)) {
                                $fromClause .= " LEFT JOIN rainfall_data r ON DATE(h.Tanggal) = DATE(r.data_timestamp)";
                            } elseif (in_array('wind', $variables)) {
                                $fromClause .= " LEFT JOIN rainfall_data r ON DATE(w.DATE) = DATE(r.data_timestamp)";
                            }
                            if ($timeType === 'monthly') {
                                $query .= ", SUM(r.rainfall_temp) as rainfall";
                            } else {
                                $query .= ", AVG(r.rainfall_temp) as rainfall";
                            }
                        }
                        
                        if (in_array('temperature', $variables)) {
                            $orderClause = "ORDER BY t.Tanggal";
                        } elseif (in_array('humidity', $variables)) {
                            $orderClause = "ORDER BY h.Tanggal";
                        } elseif (in_array('wind', $variables)) {
                            $orderClause = "ORDER BY w.DATE";
                        } else {
                            $orderClause = "ORDER BY r.data_timestamp";
                        }
                        
                        $query .= " " . $fromClause . " " . $whereClause . " " . $groupBy . " ORDER BY " . $orderByDate;
                        
                        if ($stmt = $mysqli->prepare($query)) {
                            $stmt->bind_param('ss', $startDate, $endDate);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $data = $result->fetch_all(MYSQLI_ASSOC);
                            $stmt->close();
                            return $data;
                        }
                        
                        return [];
                    } else if ($timeType === 'hourly') {
                        return getHourlyData($mysqli, $startDate, $endDate, $variables);
                    }
                    
                    return [];
                }

                function getHourlyData($mysqli, $startDate, $endDate, $variables) {
                    $allowed = array_intersect($variables, ['temperature', 'humidity', 'wind']);
                    if (empty($allowed)) return [];

                    $hourly = [];
                    $dates = [];

                    if (in_array('temperature', $allowed)) {
                        $sql = "SELECT DATE(data_timestamp) as tanggal, HOUR(data_timestamp) as jam, AVG(dry_bulb_temp) as val FROM temperature_datalog WHERE DATE(data_timestamp) BETWEEN ? AND ? GROUP BY DATE(data_timestamp), HOUR(data_timestamp) ORDER BY DATE(data_timestamp), HOUR(data_timestamp)";
                        if ($stmt = $mysqli->prepare($sql)) {
                            $stmt->bind_param('ss', $startDate, $endDate);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $d = $r['tanggal'];
                                $h = (int)$r['jam'];
                                $hourly[$d]['temperature'][$h] = $r['val'];
                                $dates[$d] = true;
                            }
                            $stmt->close();
                        }
                    }

                    if (in_array('humidity', $allowed)) {
                        $sql = "SELECT DATE(data_timestamp) as tanggal, HOUR(data_timestamp) as jam, AVG(humidity_temp) as val FROM humidity_datalog WHERE DATE(data_timestamp) BETWEEN ? AND ? GROUP BY DATE(data_timestamp), HOUR(data_timestamp) ORDER BY DATE(data_timestamp), HOUR(data_timestamp)";
                        if ($stmt = $mysqli->prepare($sql)) {
                            $stmt->bind_param('ss', $startDate, $endDate);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $d = $r['tanggal'];
                                $h = (int)$r['jam'];
                                $hourly[$d]['humidity'][$h] = $r['val'];
                                $dates[$d] = true;
                            }
                            $stmt->close();
                        }
                    }

                    if (in_array('wind', $allowed)) {
                        $sql = "SELECT DATE(data_timestamp) as tanggal, HOUR(data_timestamp) as jam, AVG(wind_speed) as wind_speed, ANY_VALUE(wind_direction_code) as wind_dir FROM wind_datalog WHERE DATE(data_timestamp) BETWEEN ? AND ? GROUP BY DATE(data_timestamp), HOUR(data_timestamp) ORDER BY DATE(data_timestamp), HOUR(data_timestamp)";
                        if ($stmt = $mysqli->prepare($sql)) {
                            $stmt->bind_param('ss', $startDate, $endDate);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($r = $res->fetch_assoc()) {
                                $d = $r['tanggal'];
                                $h = (int)$r['jam'];
                                $hourly[$d]['wind_speed'][$h] = $r['wind_speed'];
                                $hourly[$d]['wind_dir'][$h] = $r['wind_dir'];
                                $dates[$d] = true;
                            }
                            $stmt->close();
                        }
                    }

                    $dateList = array_keys($dates);
                    sort($dateList);

                    $rows = [];
                    foreach ($dateList as $d) {
                        $row = ['tanggal' => $d];
                        for ($hh = 0; $hh < 24; $hh++) {
                            if (in_array('temperature', $allowed)) {
                                $key = 'temp_' . $hh;
                                $row[$key] = isset($hourly[$d]['temperature'][$hh]) ? round((float)$hourly[$d]['temperature'][$hh], 1) : null;
                            }
                            if (in_array('humidity', $allowed)) {
                                $key = 'humid_' . $hh;
                                $row[$key] = isset($hourly[$d]['humidity'][$hh]) ? round((float)$hourly[$d]['humidity'][$hh], 1) : null;
                            }
                            if (in_array('wind', $allowed)) {
                                $key = 'wind_speed_' . $hh;
                                $row[$key] = isset($hourly[$d]['wind_speed'][$hh]) ? round((float)$hourly[$d]['wind_speed'][$hh], 1) : null;
                                $key = 'wind_dir_' . $hh;
                                $row[$key] = isset($hourly[$d]['wind_dir'][$hh]) ? $hourly[$d]['wind_dir'][$hh] : null;
                            }
                        }
                        $rows[] = $row;
                    }

                    return $rows;
                }

                function degToCompass($deg) {
                    if ($deg === null || $deg === '') return null;
                    $d = floatval($deg);
                    if ($d > 337.5 || $d <= 22.5) return 'N';
                    if ($d <= 67.5) return 'NE';
                    if ($d <= 112.5) return 'E';
                    if ($d <= 157.5) return 'SE';
                    if ($d <= 202.5) return 'S';
                    if ($d <= 247.5) return 'SW';
                    if ($d <= 292.5) return 'W';
                    return 'NW';
                }

                if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['start_date']) && isset($_GET['end_date']) && !empty($variables) && !empty($timeType)) {
                    $startValid = DateTime::createFromFormat('Y-m-d', $startDate);
                    $endValid = DateTime::createFromFormat('Y-m-d', $endDate);
                    if (!$startValid || !$endValid) {
                        echo '<div class="alert-box alert-error">Tanggal tidak valid.</div>';
                    } else {
                        $data = getMergedDataByVariablesAndTime($mysqli, $startDate, $endDate, $variables, $timeType);
                    }

                    if (!empty($data)) {
                        $dataLabel = 'Data dari';
                        if ($timeType === 'hourly') {
                            $dataLabel = 'Data Per-Jam dari';
                        } elseif ($timeType === 'daily') {
                            $dataLabel = 'Data Harian dari';
                        } elseif ($timeType === 'monthly') {
                            $dataLabel = 'Data Bulanan dari';
                        }
                        
                        echo '<h2 class="result-title">' . htmlspecialchars($dataLabel) . ' ' . htmlspecialchars($startDate) . ' sampai ' . htmlspecialchars($endDate) . '</h2>';
                        
                        // ===== TUTUP CONTAINER =====
                        echo '</div>'; // Tutup div.container
                        
                        // ===== MULAI FULL WIDTH SECTION =====
                        echo '<div class="full-width-section">';
                        
                        if ($timeType === 'hourly') {
                            $showTemp = in_array('temperature', $variables);
                            $showHum = in_array('humidity', $variables);
                            $showWind = in_array('wind', $variables);

                            if ($showTemp) {
                                echo '<h3 style="margin-top:50px; padding-left:20px;">Suhu</h3>';
                                echo '<div class="hourly-table-wrapper">';
                                echo '<table class="data-table">';
                                echo '<thead><tr>';
                                echo '<th>Tanggal</th>';
                                for ($h = 0; $h < 24; $h++) echo '<th>' . htmlspecialchars(sprintf('%02d:00', $h)) . '</th>';
                                echo '</tr></thead><tbody>';
                                foreach ($data as $row) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['tanggal'] ?? '') . '</td>';
                                    for ($h = 0; $h < 24; $h++) {
                                        $k = 'temp_' . $h;
                                        $v = isset($row[$k]) && $row[$k] !== null ? $row[$k] : '-';
                                        echo '<td>' . htmlspecialchars($v) . '</td>';
                                    }
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                                echo '</div>';
                            }

                            if ($showHum) {
                                echo '<h3 style="margin-top:50px; padding-left:20px;">Kelembapan</h3>';
                                echo '<div class="hourly-table-wrapper">';
                                echo '<table class="data-table">';
                                echo '<thead><tr>';
                                echo '<th>Tanggal</th>';
                                for ($h = 0; $h < 24; $h++) echo '<th>' . htmlspecialchars(sprintf('%02d:00', $h)) . '</th>';
                                echo '</tr></thead><tbody>';
                                foreach ($data as $row) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['tanggal'] ?? '') . '</td>';
                                    for ($h = 0; $h < 24; $h++) {
                                        $k = 'humid_' . $h;
                                        $v = isset($row[$k]) && $row[$k] !== null ? $row[$k] : '-';
                                        echo '<td>' . htmlspecialchars($v) . '</td>';
                                    }
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                                echo '</div>';
                            }

                            if ($showWind) {
                                echo '<h3 style="margin-top:50px; padding-left:20px;">Angin - Kecepatan Angin (m/s)</h3>';
                                echo '<div class="hourly-table-wrapper">';
                                echo '<table class="data-table">';
                                echo '<thead><tr>';
                                echo '<th>Tanggal</th>';
                                for ($h = 0; $h < 24; $h++) echo '<th>' . htmlspecialchars(sprintf('%02d:00', $h)) . '</th>';
                                echo '</tr></thead><tbody>';
                                foreach ($data as $row) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['tanggal'] ?? '') . '</td>';
                                    for ($h = 0; $h < 24; $h++) {
                                        $k = 'wind_speed_' . $h;
                                        $v = isset($row[$k]) && $row[$k] !== null ? $row[$k] : '-';
                                        echo '<td>' . htmlspecialchars($v) . '</td>';
                                    }
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                                echo '</div>';

                                echo '<h3 style="margin-top:50px; padding-left:20px;">Angin - Arah Angin</h3>';
                                echo '<div class="hourly-table-wrapper">';
                                echo '<table class="data-table">';
                                echo '<thead><tr>';
                                echo '<th>Tanggal</th>';
                                for ($h = 0; $h < 24; $h++) echo '<th>' . htmlspecialchars(sprintf('%02d:00', $h)) . '</th>';
                                echo '</tr></thead><tbody>';
                                foreach ($data as $row) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['tanggal'] ?? '') . '</td>';
                                    for ($h = 0; $h < 24; $h++) {
                                        $k = 'wind_dir_' . $h;
                                        $raw = isset($row[$k]) && $row[$k] !== null ? $row[$k] : '';
                                        $out = '-';
                                        if ($raw !== '' && $raw !== null) {
                                            $s = trim((string)$raw);
                                            if (is_numeric($s)) {
                                                $out = degToCompass((float)$s);
                                            } elseif (preg_match('/([0-9]+(?:\\.[0-9]+)?)/', $s, $m)) {
                                                $out = degToCompass((float)$m[1]);
                                            } else {
                                                $out = strtoupper($s);
                                            }
                                        }
                                        echo '<td>' . htmlspecialchars($out) . '</td>';
                                    }
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                                echo '</div>';
                            }
                        } else {
                            $headers = ['Tanggal'];
                            
                            if (in_array('temperature', $variables)) {
                                if ($timeType === 'monthly') {
                                    $headers[] = 'Rata-Rata Suhu (°C)';
                                } else {
                                    $headers[] = 'Suhu Max (°C)';
                                    $headers[] = 'Suhu Min (°C)';
                                    $headers[] = 'Rata-Rata Suhu (°C)';
                                }
                            }
                            if (in_array('humidity', $variables)) {
                                if ($timeType === 'monthly') {
                                    $headers[] = 'Rata-Rata Kelembapan (%)';
                                } else {
                                    $headers[] = 'Kelembapan Max (%)';
                                    $headers[] = 'Kelembapan Min (%)';
                                    $headers[] = 'Rata-Rata Kelembapan (%)';
                                }
                            }
                            if (in_array('wind', $variables)) {
                                $headers[] = 'Kecepatan Angin Max (m/s)';
                                $headers[] = 'Rata-Rata Kecepatan Angin (m/s)';
                                $headers[] = 'Rata-Rata Arah Angin';
                            }
                            if (in_array('rainfall', $variables)) {
                                $headers[] = 'Curah Hujan (mm)';
                            }
                            
                            $columnMap = [];
                            if (in_array('temperature', $variables)) {
                                if ($timeType === 'monthly') {
                                    $columnMap['Rata-Rata Suhu (°C)'] = 'temp_avg';
                                } else {
                                    $columnMap['Suhu Max (°C)'] = 'temp_max';
                                    $columnMap['Suhu Min (°C)'] = 'temp_min';
                                    $columnMap['Rata-Rata Suhu (°C)'] = 'temp_avg';
                                }
                            }
                            if (in_array('humidity', $variables)) {
                                if ($timeType === 'monthly') {
                                    $columnMap['Rata-Rata Kelembapan (%)'] = 'humid_avg';
                                } else {
                                    $columnMap['Kelembapan Max (%)'] = 'humid_max';
                                    $columnMap['Kelembapan Min (%)'] = 'humid_min';
                                    $columnMap['Rata-Rata Kelembapan (%)'] = 'humid_avg';
                                }
                            }
                            if (in_array('wind', $variables)) {
                                $columnMap['Kecepatan Angin Max (m/s)'] = 'wind_speed_max';
                                $columnMap['Rata-Rata Kecepatan Angin (m/s)'] = 'wind_speed_avg';
                                $columnMap['Rata-Rata Arah Angin'] = 'wind_dir_avg';
                            }
                            if (in_array('rainfall', $variables)) {
                                $columnMap['Curah Hujan (mm)'] = 'rainfall';
                            }
                            
                            echo '<div class="data-table-container">';
                            echo '<table class="data-table">';
                            echo '<thead><tr>';
                            foreach ($headers as $header) {
                                echo "<th>" . htmlspecialchars($header) . "</th>";
                            }
                            echo '</tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($data as $row) {
                                echo "<tr>";
                                $tanggalDisplay = $row['tanggal'] ?? '';
                                if ($timeType === 'monthly' && $tanggalDisplay !== '') {
                                    $parts = explode('-', $tanggalDisplay);
                                    if (count($parts) === 3) {
                                        $bulan = $parts[1];
                                        $tahun = $parts[0];
                                        $monthNames = [
                                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                        ];
                                        $namaBulan = $monthNames[$bulan] ?? $bulan;
                                        $tanggalDisplay = $namaBulan . ' ' . $tahun;
                                    }
                                }
                                echo "<td>" . htmlspecialchars($tanggalDisplay) . "</td>";
                                
                                foreach ($headers as $header) {
                                    if ($header === 'Tanggal') continue;
                                    $column = $columnMap[$header] ?? '';
                                    $value = $row[$column] ?? '';
                                    
                                    if ((strpos($header, 'Rata-Rata') !== false && strpos($header, 'Arah') === false) || $header === 'Curah Hujan (mm)') {
                                        if ($value !== '') {
                                            $value = round((float)$value, 1);
                                        }
                                    }
                                    
                                    if (strpos($header, 'Arah') !== false && $value !== '') {
                                        $s = trim((string)$value);
                                        if (is_numeric($s)) {
                                            $value = degToCompass((float)$s);
                                        } elseif (preg_match('/([0-9]+(?:\\.[0-9]+)?)/', $s, $m)) {
                                            $value = degToCompass((float)$m[1]);
                                        } else {
                                            $value = strtoupper($s);
                                        }
                                    }
                                    
                                    echo "<td>" . htmlspecialchars($value !== '' ? $value : '-') . "</td>";
                                }
                                echo "</tr>";
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                        }

                        // ===== TUTUP FULL WIDTH SECTION =====
                        echo '</div>';
                        
                        // ===== BUKA CONTAINER LAGI =====
                        echo '<div class="container">';

                        $variablesStr = implode(',', $variables);
                        
                        if ($timeType === 'hourly') {
                            $exportAction = 'export/export_hourly_pdf.php';
                        } elseif ($timeType === 'daily') {
                            $exportAction = 'export/export_daily_pdf.php';
                        } elseif ($timeType === 'monthly') {
                            $exportAction = 'export/export_monthly_pdf.php';
                        } else {
                            $exportAction = 'handlers/export.php';
                        }
                        
                        echo '<form class="export-form" id="export-form" action="' . htmlspecialchars($exportAction) . '" method="POST">';
                        echo '<input type="hidden" name="data" value="' . urlencode(serialize($data)) . '">';
                        echo '<input type="hidden" name="variables" value="' . htmlspecialchars($variablesStr) . '">';
                        echo '<input type="hidden" name="time_type" value="' . htmlspecialchars($timeType) . '">';
                        echo '<input type="hidden" name="start_date" value="' . htmlspecialchars($startDate) . '">';
                        echo '<input type="hidden" name="end_date" value="' . htmlspecialchars($endDate) . '">';
                        echo '<label for="export">Ekspor sebagai:</label>';
                        echo '<select name="export" id="export" required onchange="updateExportAction(this.value)">';
                        echo '<option value="">-- Pilih Format --</option>';
                        echo '<option value="pdf">PDF</option>';
                        echo '<option value="excel">Excel</option>';
                        echo '</select>';
                        echo '<button type="submit">Ekspor</button>';
                        echo '</form>';
                        
                        echo '<script>';
                        echo 'function updateExportAction(format) {';
                        echo '  var form = document.getElementById("export-form");';
                        echo '  var timeType = "' . htmlspecialchars($timeType) . '";';
                        echo '  if (format === "excel") {';
                        echo '    form.action = "export/export_excel.php";';
                        echo '  } else if (format === "pdf") {';
                        echo '    if (timeType === "hourly") {';
                        echo '      form.action = "export/export_hourly_pdf.php";';
                        echo '    } else if (timeType === "daily") {';
                        echo '      form.action = "export/export_daily_pdf.php";';
                        echo '    } else if (timeType === "monthly") {';
                        echo '      form.action = "export/export_monthly_pdf.php";';
                        echo '    }';
                        echo '  }';
                        echo '}';
                        echo '</script>';
                    } else {
                        // ===== DATA TIDAK DITEMUKAN =====
                        echo '</div>'; // Tutup div.container
                        echo '<div class="full-width-section" style="padding:40px 20px; text-align:center; background:#f5f5f5;">';
                        echo '<div class="alert-box alert-warning" style="max-width:600px; margin:0 auto;">';
                        echo 'Tidak ada data ditemukan untuk rentang tanggal dan variabel yang dipilih.';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="container">';
                    }
                }
            }
        }
        ?>

        <a href="dashboard.php" class="back-link">← Kembali ke Dashboard</a>
    </div>

    <?php include __DIR__ . '/Layout/Layout/footer.html'; ?>
</body>
</html>