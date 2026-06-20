<?php
// Debug wind_datalog structure and sample data

$mysqli = new mysqli("localhost", "root", "", "meteorologi");

if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Check table structure
echo "<h2>Struktur wind_datalog:</h2>";
$result = $mysqli->query("DESCRIBE wind_datalog");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check sample data for 2024-01
echo "<h2>Sample data wind_datalog untuk 2024-01:</h2>";
$result = $mysqli->query("SELECT * FROM wind_datalog WHERE DATE(data_timestamp) BETWEEN '2024-01-01' AND '2024-01-05' LIMIT 20");
echo "<table border='1'><tr>";
if ($result && $result->num_fields > 0) {
    $field = $result->fetch_fields();
    foreach ($field as $f) {
        echo "<th>" . $f->name . "</th>";
    }
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val) . "</td>";
        }
        echo "</tr>";
    }
}
echo "</table>";

// Check distinct wind_direction_code values
echo "<h2>Distinct wind_direction_code values:</h2>";
$result = $mysqli->query("SELECT DISTINCT wind_direction_code FROM wind_datalog LIMIT 30");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    echo $row['wind_direction_code'] . "\n";
}
echo "</pre>";

// Try the subquery
echo "<h2>Test subquery untuk bulan 2024-01:</h2>";
$result = $mysqli->query("SELECT AVG(CAST(wind_direction_code AS UNSIGNED)) as avg_dir FROM wind_datalog WHERE CAST(wind_direction_code AS UNSIGNED) != 0 AND wind_direction_code != 'Calm' AND YEAR(data_timestamp) = 2024 AND MONTH(data_timestamp) = 1");
$row = $result->fetch_assoc();
echo "Result: " . ($row['avg_dir'] ?? 'NULL') . "<br>";

$mysqli->close();
?>
