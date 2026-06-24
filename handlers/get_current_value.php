<?php
// handlers/get_current_value.php – AJAX handler to fetch current weather parameters
header('Content-Type: application/json');
require __DIR__ . '/../service/database_login.php';

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$hour = isset($_GET['hour']) ? (int)$_GET['hour'] : null;

if (empty($type) || empty($date)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter type dan date wajib diisi.'
    ]);
    exit;
}

// Validasi format tanggal
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Format tanggal tidak valid (harus YYYY-MM-DD).'
    ]);
    exit;
}

$response = [
    'status' => 'success',
    'exists' => false,
    'value' => null
];

try {
    switch ($type) {
        case 'temperature':
            $stmt = $mysqli->prepare("SELECT dry_bulb_temp FROM temperature_datalog WHERE DATE(data_timestamp) = ? AND HOUR(data_timestamp) = ? LIMIT 1");
            $stmt->bind_param("si", $date, $hour);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $response['exists'] = true;
                $response['value'] = $row['dry_bulb_temp'];
            }
            $stmt->close();
            break;

        case 'humidity':
            $stmt = $mysqli->prepare("SELECT humidity_temp FROM humidity_datalog WHERE DATE(data_timestamp) = ? AND HOUR(data_timestamp) = ? LIMIT 1");
            $stmt->bind_param("si", $date, $hour);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $response['exists'] = true;
                $response['value'] = $row['humidity_temp'];
            }
            $stmt->close();
            break;

        case 'wind':
            $stmt = $mysqli->prepare("SELECT wind_speed, wind_direction FROM wind_datalog WHERE DATE(data_timestamp) = ? AND HOUR(data_timestamp) = ? LIMIT 1");
            $stmt->bind_param("si", $date, $hour);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $response['exists'] = true;
                $response['value'] = $row['wind_speed'];
                $response['wind_direction'] = $row['wind_direction'];
            }
            $stmt->close();
            break;

        case 'rainfall':
            $stmt = $mysqli->prepare("SELECT rainfall_temp FROM rainfall_data WHERE DATE(data_timestamp) = ? LIMIT 1");
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $response['exists'] = true;
                $response['value'] = $row['rainfall_temp'];
            }
            $stmt->close();
            break;

        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Parameter type tidak valid.'
            ]);
            exit;
    }

    echo json_encode($response);

} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data: ' . $e->getMessage()
    ]);
}
?>
