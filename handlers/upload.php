<?php
// Menentukan durasi eksekusi
ini_set('max_execution_time', 1800); // 30 menit waktu yang digunakan untuk mengeksekusi data yang di upload

// Koneksi ke Database
require __DIR__ . '/../service/database_login.php';
$conn = $mysqli;


// proses upload
if (isset($_POST["submit"])) {
    $fileType = isset($_POST["fileType"]) ? $_POST["fileType"] : 'humidity_datalog';
    $targetDir = "uploads/";

    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    foreach ($_FILES["filesToUpload"]["tmp_name"] as $key => $tmpName) {
        $fileName = basename($_FILES["filesToUpload"]["name"][$key]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($tmpName, $targetFilePath)) {
            // Process the CSV file in batches
            if (($handle = fopen($targetFilePath, "r")) !== FALSE) {
                fgetcsv($handle); // Skip the header row if present
                $recordCount = 0;
                $errorCount = 0;
                $batchSize = 1000; // Adjust this value based on your server's performance
                $batchData = [];

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $batchData[] = $data;
                    
                    if (count($batchData) >= $batchSize) {
                        processBatch($batchData, $fileType, $conn, $recordCount, $errorCount);
                        $batchData = [];
                    }
                }

                // memproses sisa data yang belum diproses
                if (!empty($batchData)) {
                    processBatch($batchData, $fileType, $conn, $recordCount, $errorCount);
                }

                fclose($handle);
            } else {
                echo "Error opening file: " . htmlspecialchars($fileName) . "<br>";
            }
        } else {
            echo "Error uploading file: " . htmlspecialchars($fileName) . "<br>";
        }
    }
}

$conn->close();

function processBatch($batchData, $fileType, $conn, &$recordCount, &$errorCount) {
    $insertData = [];
    foreach ($batchData as $data) {
        switch ($fileType) {
            case 'humidity_datalog':
                if (count($data) !== 2) {
                    $errorCount++;
                    continue 2;
                }
                $timestamp = date('Y-m-d H:i:s', strtotime($data[0]));
                $humidity_temp = $data[1];
                $insertData[] = "('$timestamp', '$humidity_temp')";
                break;

            case 'rainfall_data':
                if (count($data) !== 3) {
                    $errorCount++;
                    continue 2;
                }
                $stasiun = $data[0];
                $timestamp = date('Y-m-d H:i:s', strtotime($data[1]));
                $rainfall_temp = $data[2];
                $insertData[] = "('$stasiun', '$timestamp', '$rainfall_temp')";
                break;

            case 'temperature_datalog':
                if (count($data) !== 2) {
                    $errorCount++;
                    continue 2;
                }
                $timestamp = date('Y-m-d H:i:s', strtotime($data[0]));
                $dry_bulb_temp = $data[1];
                $insertData[] = "('$timestamp', '$dry_bulb_temp')";
                break;

            case 'wind_datalog':
                if (count($data) !== 3) {
                    $errorCount++;
                    continue 2;
                }
                $timestamp = date('Y-m-d H:i:s', strtotime($data[0]));
                $wind_direction = $data[1];
                $wind_speed = $data[2];
                $insertData[] = "('$timestamp','$wind_direction','$wind_speed')";
                break;

            default:
                $errorCount++;
                continue 2;
        }
    }

    if (!empty($insertData)) {
        $columns = '';
        $updateFields = '';
        switch ($fileType) {
            case 'humidity_datalog':
                $columns = '(data_timestamp, humidity_temp)';
                $updateFields = 'humidity_temp = VALUES(humidity_temp)';
                break;
            case 'rainfall_data':
                $columns = '(stasiun, data_timestamp, rainfall_temp)';
                $updateFields = 'rainfall_temp = VALUES(rainfall_temp)';
                break;
            case 'temperature_datalog':
                $columns = '(data_timestamp, dry_bulb_temp)';
                $updateFields = 'dry_bulb_temp = VALUES(dry_bulb_temp)';
                break;
            case 'wind_datalog':
                $columns = '(data_timestamp, wind_direction, wind_speed)';
                $updateFields = ' wind_direction = VALUES(wind_direction), wind_speed = VALUES(wind_speed)';
                break;
        }

        $sql = "INSERT INTO $fileType $columns VALUES " . implode(', ', $insertData) .
               " ON DUPLICATE KEY UPDATE $updateFields";

        if ($conn->query($sql) === TRUE) {
            $recordCount += count($insertData);
        } else {
            $errorCount += count($insertData);
            echo "Error: " . $conn->error . "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files and View Data</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../Layout/Layout/styleheader.css">
    <style>
        .main-container {
            display: flex;
            gap: 20px;
            margin: 30px auto;
            width: 95%;
            max-width: 1400px;
            padding: 0 20px;
        }

        .upload-box {
            flex: 1;
            padding: 30px;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            background: linear-gradient(135deg, #f5fff5 0%, #f0fdf4 100%);
            box-shadow: 0 4px 6px rgba(76, 175, 80, 0.1);
        }

        .delete-box {
            flex: 1;
            padding: 30px;
            border: 2px solid #f44336;
            border-radius: 10px;
            background: linear-gradient(135deg, #fff5f5 0%, #fdf4f4 100%);
            box-shadow: 0 4px 6px rgba(244, 67, 54, 0.1);
        }

        .upload-box h1 {
            color: #2e7d32;
            margin-top: 0;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }

        .delete-box h1 {
            color: #c62828;
            margin-top: 0;
            border-bottom: 3px solid #f44336;
            padding-bottom: 10px;
        }

        .upload-box label, .delete-box label {
            display: block;
            font-weight: bold;
            margin: 15px 0 8px 0;
            color: #333;
        }

        .upload-box select, .delete-box select,
        .upload-box input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .upload-box select:focus, .delete-box select:focus,
        .upload-box input[type="file"]:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }

        .delete-box select:focus {
            outline: none;
            border-color: #f44336;
            box-shadow: 0 0 5px rgba(244, 67, 54, 0.3);
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-upload {
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-upload:hover {
            background-color: #45a049;
        }

        .btn-load {
            background-color: #2196F3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-load:hover {
            background-color: #0b7dda;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #da190b;
        }

        .btn-reset {
            background-color: #757575;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .btn-reset:hover {
            background-color: #616161;
        }

        .data-display {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin: 15px 0;
            background-color: white;
        }

        .data-display table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-display th {
            background-color: #2e7d32;
            color: white;
            padding: 8px;
            border: 1px solid #ddd;
        }

        .data-display td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .data-display tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .data-display tr:hover {
            background-color: #f0f0f0;
        }

        .delete-box .data-display th {
            background-color: #c62828;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
        }
    </style>
    <script>
        function loadTable(fileType) {
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "./fetch_data.php?fileType=" + fileType, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById("dataContainer").innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        function setDropdownSelection(fileType) {
            var dropdown = document.getElementById("fileType");
            dropdown.value = fileType;
        }

        window.onload = function() {
            var urlParams = new URLSearchParams(window.location.search);
            var fileType = urlParams.get("fileType") || "humidity_datalog";
            
            setDropdownSelection(fileType);
            loadTable(fileType);
            
            document.getElementById("fileType").addEventListener("change", function() {
                loadTable(this.value);
            });
        };
    </script>
</head>
<body>
    <?php include __DIR__ . "/../Layout/Layout/header.html"; ?>

    <div class="upload-container">
        <h1>Upload CSV Files</h1>
        <form action="" method="post" enctype="multipart/form-data">
            <label for="fileType">Select CSV file type:</label>
            <select name="fileType" id="fileType" required>
                <option value="humidity_datalog">Humidity (Kelembaban)</option>
                <option value="rainfall_data">Rainfall (Curah Hujan)</option>
                <option value="temperature_datalog">Temperature (Suhu)</option>
                <option value="wind_datalog">Wind (Angin)</option>
            </select>
            <br><br>
            Select CSV files to upload:
            <input type="file" name="filesToUpload[]" id="filesToUpload" multiple required>
            <br><br>
            <input type="submit" name="submit" value="Upload">
        </form>
    </div>

    <div id="dataContainer" class="data-container">
        <!-- data akan ditampilkan disini oleh JavaScript -->
    </div>
</body>
</html>
