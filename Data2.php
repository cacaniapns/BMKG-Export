<?php
// Koneksi ke database
require __DIR__ . '/service/database_login.php';


// Ambil data dari database
$startDate = $_GET['start_date'] ?? '2023-01-01';
$endDate = $_GET['end_date'] ?? '2023-12-31';
$dataType = $_GET['data_type'] ?? 'temperature'; // Pilihan data
$chartType = $_GET['chart_type'] ?? 'line'; // Pilihan grafik

// Query untuk semua jenis data
$sql = "SELECT d.tanggal, d.max_temperature AS max_temp, d.min_temperature AS min_temp, d.avg_temperature AS avg_temp,
               h.max_humidity, h.min_humidity, h.avg_humidity,
               r.rainfall_temp,
               w.wind_speed_max, w.wind_speed_avg
        FROM temperature_data AS d
        JOIN humidity_data AS h ON d.tanggal = h.tanggal
        JOIN rainfall_data AS r ON d.tanggal = r.data_timestamp
        JOIN wind_data AS w ON d.tanggal = w.DATE
        WHERE d.tanggal BETWEEN ? AND ?";

if (!$stmt = $mysqli->prepare($sql)) {
    die("Prepare failed: " . $mysqli->error);
}

if (!$stmt->bind_param('ss', $startDate, $endDate)) {
    die("Bind param failed: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$chartLabels = array_column($data, 'tanggal');

// Siapkan data sesuai dengan jenis data yang dipilih
$chartData = [];
switch ($dataType) {
    case 'temperature':
        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Max Temperature',
                    'data' => array_column($data, 'max_temp'),
                    'borderColor' => "rgba(75, 192, 192, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(75, 192, 192, 0.2)" : "rgba(75, 192, 192, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ],
                [
                    'label' => 'Min Temperature',
                    'data' => array_column($data, 'min_temp'),
                    'borderColor' => "rgba(255, 99, 132, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(255, 99, 132, 0.2)" : "rgba(255, 99, 132, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ],
                [
                    'label' => 'Avg Temperature',
                    'data' => array_column($data, 'avg_temp'),
                    'borderColor' => "rgba(54, 162, 235, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(54, 162, 235, 0.2)" : "rgba(54, 162, 235, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ]
            ]
        ];
        break;
    case 'humidity':
        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Max Humidity',
                    'data' => array_column($data, 'max_humidity'),
                    'borderColor' => "rgba(75, 192, 192, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(75, 192, 192, 0.2)" : "rgba(75, 192, 192, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ],
                [
                    'label' => 'Min Humidity',
                    'data' => array_column($data, 'min_humidity'),
                    'borderColor' => "rgba(255, 99, 132, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(255, 99, 132, 0.2)" : "rgba(255, 99, 132, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ],
                [
                    'label' => 'Avg Humidity',
                    'data' => array_column($data, 'avg_humidity'),
                    'borderColor' => "rgba(54, 162, 235, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(54, 162, 235, 0.2)" : "rgba(54, 162, 235, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ]
            ]
        ];
        break;
    case 'rainfall':
        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Rainfall',
                    'data' => array_column($data, 'rainfall_temp'),
                    'borderColor' => "rgba(75, 192, 192, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(75, 192, 192, 0.2)" : "rgba(75, 192, 192, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ]
            ]
        ];
        break;
    case 'wind':
        $chartData = [
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Max Wind Speed',
                    'data' => array_column($data, 'wind_speed_max'),
                    'borderColor' => "rgba(75, 192, 192, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(75, 192, 192, 0.2)" : "rgba(75, 192, 192, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ],
                [
                    'label' => 'Avg Wind Speed',
                    'data' => array_column($data, 'wind_speed_avg'),
                    'borderColor' => "rgba(54, 162, 235, 1)",
                    'backgroundColor' => $chartType === 'bar' ? "rgba(54, 162, 235, 0.2)" : "rgba(54, 162, 235, 0)",
                    'fill' => $chartType === 'line' ? false : true
                ]
            ]
        ];
        break;
}

// Tutup koneksi database
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Data Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="Layout/Layout/styleheader.css">
</head>
<body>
<?php include __DIR__ . "/Layout/Layout/header.html"; ?>
    <div class="weather-chart-container">
        <h1>Weather Data Chart</h1>

        <!-- Form untuk memilih rentang tanggal, jenis data, dan jenis grafik -->
        <form class="weather-form">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            
            <label for="data_type">Data Type:</label>
            <select id="data_type" name="data_type">
                <option value="temperature" <?php echo $dataType === 'temperature' ? 'selected' : ''; ?>>Temperature</option>
                <option value="humidity" <?php echo $dataType === 'humidity' ? 'selected' : ''; ?>>Humidity</option>
                <option value="rainfall" <?php echo $dataType === 'rainfall' ? 'selected' : ''; ?>>Rainfall</option>
                <option value="wind" <?php echo $dataType === 'wind' ? 'selected' : ''; ?>>Wind</option>
            </select>
            
            <label for="chart_type">Chart Type:</label>
            <select id="chart_type" name="chart_type">
                <option value="line" <?php echo $chartType === 'line' ? 'selected' : ''; ?>>Line</option>
                <option value="bar" <?php echo $chartType === 'bar' ? 'selected' : ''; ?>>Bar</option>
            </select>
            
            <button type="submit">Update Chart</button>
        </form>

        <canvas class="chart_canvas" width="800" height="400"></canvas>
        <div class="no-data-message" style="display: none;">No data available for the selected date range.</div>
        <button class="export_button">Export Chart as PNG</button>

        <!-- Menyimpan data untuk grafik -->
        <script class="chart_labels" type="application/json"><?php echo json_encode($chartLabels); ?></script>
        <script class="chart_data" type="application/json"><?php echo json_encode($chartData); ?></script>
        <script class="chart_type" type="application/json"><?php echo json_encode($chartType); ?></script>
    </div>

    <script>
        function createChart(labels, chartData, chartType) {
            var ctx = document.querySelector(".chart_canvas").getContext("2d");

            // Cek jika tidak ada data
            if (!labels.length) {
                document.querySelector('.no-data-message').style.display = 'block';
                return;
            }

            document.querySelector('.no-data-message').style.display = 'none';

            // Debugging: log data
            console.log("Chart Data:", chartData);
            console.log("Labels:", labels);

            var myChart = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: chartData.datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return `${tooltipItem.dataset.label}: ${tooltipItem.raw}`;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            formatter: (value, context) => {
                                return value;
                            },
                            color: '#444',
                            anchor: 'end',
                            align: 'top'
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: "Tanggal"
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: "Nilai"
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

            return myChart;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const labels = JSON.parse(document.querySelector('.chart_labels').textContent);
            const chartData = JSON.parse(document.querySelector('.chart_data').textContent);
            const chartType = JSON.parse(document.querySelector('.chart_type').textContent);

            const myChart = createChart(labels, chartData, chartType);

            // Ekspor sebagai gambar
            document.querySelector('.export_button').addEventListener('click', function() {
                const imageUrl = myChart.toBase64Image();
                const link = document.createElement('a');
                link.href = imageUrl;
                link.download = 'chart.png';
                link.click();
            });
        });

        // Update chart saat form disubmit
        document.querySelector('.weather-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const queryString = new URLSearchParams(formData).toString();
            window.location.search = queryString;
        });
    </script>
<?php include __DIR__ . "/Layout/Layout/footer.html"; ?>
</body>
</html>
