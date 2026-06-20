<?php
// Database connection parameters
$servername = "localhost";
$username = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$dbname = "meteorologi"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$fileType = $_GET['fileType'];

switch ($fileType) {
    case 'humidity_datalog':
        $sql = "SELECT * FROM humidity_datalog";
        $columns = ["id_humidity_data", "data_timestamp", "humidity_temp"];
        $columnHeaders = ["ID", "Timestamp", "Humidity Temp"];
        break;
        
    case 'rainfall_data':
        $sql = "SELECT * FROM rainfall_data";
        $columns = ["id_rainfall_data", "stasiun", "data_timestamp", "rainfall_temp"];
        $columnHeaders = ["ID", "Station", "Timestamp", "Rainfall Temp"];
        break;
        
    case 'temperature_datalog':
        $sql = "SELECT * FROM temperature_datalog";
        $columns = ["id_temperature_data", "data_timestamp", "dry_bulb_temp"];
        $columnHeaders = ["ID", "Timestamp", "Dry Bulb Temp"];
        break;
        
    case 'wind_datalog':
        $sql = "SELECT * FROM wind_datalog";
        $columns = ["id", "data_timestamp", "wind_direction", "wind_speed"];
        $columnHeaders = ["ID", "Timestamp", "Wind Direction", "Wind Speed"];
        break;
        
    default:
        echo "Unknown file type.";
        exit;
}

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Start the table
    echo "<table><caption style='font-size: 1.5em; font-weight: bold;'>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $fileType))) . " Records</caption><tr>";
    
    // Table headers
    foreach ($columnHeaders as $header) {
        echo "<th>" . htmlspecialchars($header) . "</th>";
    }
    echo "</tr>";
    
    // Table rows
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($columns as $column) {
            echo "<td>" . htmlspecialchars($row[$column]) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No records found for " . htmlspecialchars(ucfirst(str_replace('_', ' ', $fileType))) . ".";
}

$conn->close();
?>
