<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Data</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="Layout/styleheader.css">
    <style>
        /* scroll tabel data per jam */
        .hourly-table-wrapper {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
            max-height: 600px; /* max 20 baris, sisanya scroll ke bawah */
        }
        .hourly-table-wrapper table {
            min-width: 1000px; /* force scroll di layar kecil */
            margin: 0;
        }
        .hourly-table-wrapper table thead tr th:first-child {
            position: sticky;
            left: 0;
            background-color: #009879;
            color: white;
            z-index: 11;
            font-weight: bold;
            min-width: 120px;
            padding: 12px 15px;
            border: 2px solid #007a63;
        }
        .hourly-table-wrapper table tbody tr td:first-child {
            position: sticky;
            left: 0;
            background-color: #f0f0f0;
            color: #333;
            font-weight: bold;
            z-index: 10;
            min-width: 120px;
            padding: 12px 15px;
            border: 2px solid #ddd;
        }
        .hourly-table-wrapper table thead {
            position: sticky;
            top: 0;
            z-index: 12;
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
        // ambil kredensial dari file service kalo ada
        $dbHost = 'localhost';
        $dbUser = 'root';
        $dbPass = '';
        $dbName = 'meteorologi';

        // kalo file kredensial lokal ada, ambil dari sana
        $credFile = __DIR__ . '/service/service/database_login.php';
        if (file_exists($credFile)) {
            include $credFile; // dapet $hostname, $username, $password, $database_name
            if (isset($hostname)) $dbHost = $hostname;
            if (isset($username)) $dbUser = $username;
            if (isset($password)) $dbPass = $password;
        }

        // bikin koneksi mysqli — pakai helper dari file kredensial kalo ada
        if (function_exists('get_db_connection')) {
            $mysqli = get_db_connection();
        } else {
            $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        }

        // cek koneksi
        if (!$mysqli || (is_object($mysqli) && $mysqli->connect_errno)) {
            echo "Gagal terhubung ke MySQL.\nPastikan kredensial di service/service/database_login.php benar dan database 'meteorologi' tersedia.";
            exit();
        }

        // baca range tanggal data di database
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
        // tampilkan range data berdasarkan tanggal
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $minDate = $row['min_date'];
            $maxDate = $row['max_date'];
            echo "<p class='data-range-info'>Data yang tersedia pada Database: " . htmlspecialchars($minDate) . " sampai " . htmlspecialchars($maxDate) . "</p>";
        } else {
            echo "<p class='data-range-info'>Tidak ada data yang tersedia.</p>";
        }

        $result->close();
        ?>

        <form class="search-form" action="data.php" method="GET">
            <label for="start_date">Tanggal Mulai:</label>
            <input type="date" id="start_date" name="start_date" required>
            <label for="end_date">Tanggal Selesai:</label>
            <input type="date" id="end_date" name="end_date" required>

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

            <button type="submit">Cari</button>
        </form>

        <script>
            // disable 'Jam' kalo pilih Curah Hujan atau Angin
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
                // awal / init variabel
                document.addEventListener('DOMContentLoaded', updateHourlyState);
                
                updateHourlyState();
                
                // jangan submit form kalo tanggal kosong
                const form = document.querySelector('.search-form');
                const startInput = document.getElementById('start_date');
                const endInput = document.getElementById('end_date');
                if (form) {
                    let msgDiv = document.createElement('div');
                    msgDiv.id = 'date-error';
                    msgDiv.style.color = '#c00';
                    msgDiv.style.textAlign = 'center';
                    msgDiv.style.marginTop = '10px';
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
        // validasi dan bersihkan input
        $startDate = filter_input(INPUT_GET, 'start_date', FILTER_DEFAULT);
        $endDate = filter_input(INPUT_GET, 'end_date', FILTER_DEFAULT);
        $variables = isset($_GET['variables']) && is_array($_GET['variables']) ? $_GET['variables'] : [];
        $timeType = filter_input(INPUT_GET, 'time_type', FILTER_DEFAULT);

        // validasi variabel yang dipilih
        $validVariables = ['rainfall', 'temperature', 'humidity', 'wind'];
        $variables = array_filter($variables, function($v) use ($validVariables) {
            return in_array($v, $validVariables);
        });

        // validasi tipe waktu
        if (!in_array($timeType, ['hourly', 'daily', 'monthly'])) {
            $timeType = '';
        }

        // server-side: jam ga bisa kalo pilih curah hujan (angin udah support jam dari wind_datalog)
        if ($timeType === 'hourly' && in_array('rainfall', $variables)) {
            die('Tipe "Jam" tidak tersedia untuk Curah Hujan. Pilih "Hari" atau "Bulan".');
        }

        // tanggal divalidasi pas form dikirim (jadi halaman bisa dibuka tanpa parameter)

        // function untuk dapetin data merged berdasarkan variabel dan tipe waktu
        function getMergedDataByVariablesAndTime($mysqli, $startDate, $endDate, $variables, $timeType) {
            if (empty($variables)) {
                return [];
            }

            // Untuk daily dan monthly, merge data dari semua variabel by date
            if ($timeType === 'daily' || $timeType === 'monthly') {
                // Tentukan base table (prefer temperature jika ada)
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
                // Use ANY_VALUE to make the formatted date compatible with ONLY_FULL_GROUP_BY
                // For monthly: group by MONTH and YEAR (aggregate daily data to monthly)
                // For daily: group by DATE (one row per day)
                if ($timeType === 'monthly') {
                    $dateSelect = "ANY_VALUE(DATE_FORMAT($baseDateCol, '%Y-%m-01')) as tanggal";
                    $groupBy = "GROUP BY YEAR($baseDateCol), MONTH($baseDateCol)";
                    $orderByDate = "YEAR($baseDateCol), MONTH($baseDateCol)";
                } else { // daily
                    // Use ANY_VALUE for the same reason as monthly
                    $dateSelect = "ANY_VALUE(DATE_FORMAT($baseDateCol, '%Y-%m-%d')) as tanggal";
                    $groupBy = "GROUP BY DATE($baseDateCol)";
                    $orderByDate = "DATE($baseDateCol)";
                }

                // Build SELECT clause
                $query = "SELECT " . $dateSelect;
                
                // Build FROM clause and aggregated selects
                if (in_array('temperature', $variables)) {
                    $fromClause = "FROM temperature_data t";
                    // Both daily and monthly show max/min/avg (same format)
                    $query .= ", MAX(t.max_temperature) as temp_max, MIN(t.min_temperature) as temp_min, AVG(t.avg_temperature) as temp_avg";
                } else {
                    $fromClause = "FROM " . $baseTable . " " . (in_array('humidity', $variables) ? 'h' : (in_array('wind', $variables) ? 'w' : 'r'));
                }
                
                // Build WHERE clause
                if (in_array('temperature', $variables)) {
                    $whereClause = "WHERE t.Tanggal BETWEEN ? AND ?";
                } elseif (in_array('humidity', $variables)) {
                    $whereClause = "WHERE h.Tanggal BETWEEN ? AND ?";
                } elseif (in_array('wind', $variables)) {
                    $whereClause = "WHERE w.DATE BETWEEN ? AND ?";
                } else {
                    $whereClause = "WHERE r.data_timestamp BETWEEN ? AND ?";
                }
                
                // Add JOINs and SELECTs untuk other variables
                if (in_array('humidity', $variables) && !in_array('temperature', $variables)) {
                    // if humidity is base but not temperature
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
                    // aggregate wind: max of max, min from wind_datalog, avg of avg from wind_data table
                    // For direction average, pick the most frequent direction code (mode) from wind_data excluding 'Calm' and empty
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
                    // For monthly: SUM rainfall, for daily: AVG rainfall
                    if ($timeType === 'monthly') {
                        $query .= ", SUM(r.rainfall_temp) as rainfall";
                    } else {
                        $query .= ", AVG(r.rainfall_temp) as rainfall";
                    }
                }
                
                // Determine order by clause
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
            } 
            // Untuk hourly, perlu struktur data berbeda
            else if ($timeType === 'hourly') {
                return getHourlyData($mysqli, $startDate, $endDate, $variables);
            }
            
            return [];
        }

        // Fungsi khusus untuk hourly data (pivot ke kolom jam 0..23 untuk temp & humidity & wind)
        function getHourlyData($mysqli, $startDate, $endDate, $variables) {
            $allowed = array_intersect($variables, ['temperature', 'humidity', 'wind']);
            if (empty($allowed)) return [];

            $hourly = []; // [date][var][hour] = value
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
                // use raw hourly table for humidity
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

            // Normalize dates: ensure ordered list
            $dateList = array_keys($dates);
            sort($dateList);

            // Build final rows: for each date include hours 0..23 per variable
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

        // Convert degrees to 8-point compass (N, NE, E, SE, S, SW, W, NW)
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

// Helper: compute wind direction average from wind_datalog for a given period, excluding zeros and 'Calm' (deprecated - now handled in SQL)
    function getWindDirectionAvgFromDatalog($mysqli, $periodStr, $timeType) {
        return null; // Computation moved to SQL subquery
        }

        // Periksa apakah request menggunakan metode GET dan parameter ada
        if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['start_date']) && isset($_GET['end_date']) && !empty($variables) && !empty($timeType)) {
            // Validasi tanggal hanya ketika form disubmit
            $startValid = DateTime::createFromFormat('Y-m-d', $startDate);
            $endValid = DateTime::createFromFormat('Y-m-d', $endDate);
            if (!$startValid || !$endValid) {
                echo '<p style="text-align:center;color:#c00;">Tanggal tidak valid.</p>';
            } else {
                $data = getMergedDataByVariablesAndTime($mysqli, $startDate, $endDate, $variables, $timeType);
            }

            // Menampilkan hasil pencarian
            if (!empty($data)) {
                // Tentukan label berdasarkan time type
                $dataLabel = 'Data dari';
                if ($timeType === 'hourly') {
                    $dataLabel = 'Data Per-Jam dari';
                } elseif ($timeType === 'daily') {
                    $dataLabel = 'Data Harian dari';
                } elseif ($timeType === 'monthly') {
                    $dataLabel = 'Data Bulanan dari';
                }
                
                echo '<h2 class="result-title">' . htmlspecialchars($dataLabel) . ' ' . htmlspecialchars($startDate) . ' sampai ' . htmlspecialchars($endDate) . '</h2>';
                
                // Jika tipe hourly: pivot jam 0..23 ke kolom untuk temperature, humidity, dan wind
                if ($timeType === 'hourly') {
                    $showTemp = in_array('temperature', $variables);
                    $showHum = in_array('humidity', $variables);
                    $showWind = in_array('wind', $variables);

                    // Render a separate table for each variable requested
                    if ($showTemp) {
                        echo '<h3 style="margin-top:50px;">Suhu</h3>';
                        echo '<div class="hourly-table-wrapper">';
                        echo '<table class="data-table">';
                        echo '<thead><tr>';
                        echo '<th>Tanggal</th>';
                        for ($h = 0; $h < 24; $h++) echo '<th>' . htmlspecialchars('' . sprintf('%02d:00', $h)) . '</th>';
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
                        echo '<h3 style="margin-top:50px;">Kelembapan</h3>';
                        echo '<div class="hourly-table-wrapper">';
                        echo '<table class="data-table">';
                        echo '<thead><tr>';
                        echo '<th>Tanggal</th>';
                        for ($h = 0; $h < 24; $h++) echo '<th>' . htmlspecialchars('' . sprintf('%02d:00', $h)) . '</th>';
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
                        echo '<h3 style="margin-top:50px;">Angin - Kecepatan Angin (m/s)</h3>';
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

                        echo '<h3 style="margin-top:50px;">Angin - Arah Angin</h3>';
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
                    // Build table headers berdasarkan variabel yang dipilih (daily/monthly)
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
                    
                    // Column mapping
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
                    
                    // Render table gabungan
                    echo '<table class="data-table">';
                    echo '<thead><tr>';
                    foreach ($headers as $header) {
                        echo "<th>" . htmlspecialchars($header) . "</th>";
                    }
                    echo '</tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($data as $row) {
                        echo "<tr>";
                        
                        // Format tanggal berdasarkan time type
                        $tanggalDisplay = $row['tanggal'] ?? '';
                        if ($timeType === 'monthly' && $tanggalDisplay !== '') {
                            // Konversi format YYYY-MM-DD ke "Nama Bulan Tahun"
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
                            
                            // Round average and rainfall values to 1 decimal place (exclude wind direction labels)
                            if ((strpos($header, 'Rata-Rata') !== false && strpos($header, 'Arah') === false) || $header === 'Curah Hujan (mm)') {
                                if ($value !== '') {
                                    $value = round((float)$value, 1);
                                }
                            }
                            
                            // Convert wind direction columns (Arah Angin and Rata-Rata Arah Angin) to compass letters
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
                }

                // Form untuk ekspor
                $variablesStr = implode(',', $variables);
                
                // Tentukan action berdasarkan time_type
                if ($timeType === 'hourly') {
                    $exportAction = 'export/export_hourly_pdf.php';
                } elseif ($timeType === 'daily') {
                    $exportAction = 'export/export_daily_pdf.php';
                } elseif ($timeType === 'monthly') {
                    $exportAction = 'export/export_monthly_pdf.php';
                } else {
                    $exportAction = 'handlers/export.php';
                }
                
                // Tentukan action berdasarkan format export
                echo '<form class="export-form" id="export-form" action="' . htmlspecialchars($exportAction) . '" method="POST">';
                echo '<input type="hidden" name="data" value="' . urlencode(serialize($data)) . '">';
                echo '<input type="hidden" name="variables" value="' . htmlspecialchars($variablesStr) . '">';
                echo '<input type="hidden" name="time_type" value="' . htmlspecialchars($timeType) . '">';
                echo '<input type="hidden" name="start_date" value="' . htmlspecialchars($startDate) . '">';
                echo '<input type="hidden" name="end_date" value="' . htmlspecialchars($endDate) . '">';
                echo '<label for="export">Ekspor sebagai:</label>';
                echo '<select name="export" id="export" required onchange="updateExportAction(this.value)">';
                echo '<option value="">-- Pilih Format --</option>';
                
                // Semua time type (hourly, daily, monthly) sekarang support PDF dan Excel
                echo '<option value="pdf">PDF</option>';
                echo '<option value="excel">Excel</option>';
                
                echo '</select>';
                echo '<button type="submit">Ekspor</button>';
                echo '</form>';
                
                // Script untuk handle export route
                echo '<script>';
                echo 'function updateExportAction(format) {';
                echo '  var form = document.getElementById("export-form");';
                echo '  var timeType = "' . htmlspecialchars($timeType) . '";';
                echo '  ';
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
                echo '<p style="text-align: center; margin: 40px 0; font-size: 1.1em; color: #666;">Tidak ada data ditemukan untuk rentang tanggal dan variabel yang dipilih.</p>';
            }
        }
        ?>
    </div>

    <?php include __DIR__ . '/Layout/Layout/footer.html'; ?>
</body>
</html>
