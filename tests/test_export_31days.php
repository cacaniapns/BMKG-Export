<?php
// Test 31-day hourly export pagination

// Generate dummy data for 31 days
$data = [];
$startDate = new DateTime('2024-01-01');
$endDate = new DateTime('2024-01-31');
$interval = new DateInterval('P1D');
$period = new DatePeriod($startDate, $interval, $endDate);

foreach ($period as $date) {
    $dateStr = $date->format('Y-m-d');
    $row = [
        'tanggal' => $dateStr,
    ];
    
    // Add 24 hourly temperature values
    for ($h = 0; $h < 24; $h++) {
        $row['temp_' . $h] = 25 + rand(-5, 5);
    }
    
    // Add 24 hourly humidity values
    for ($h = 0; $h < 24; $h++) {
        $row['humid_' . $h] = 70 + rand(-20, 20);
    }
    
    // Add 24 hourly wind direction values
    for ($h = 0; $h < 24; $h++) {
        $row['wind_dir_' . $h] = rand(0, 360);
    }
    
    // Add 24 hourly wind speed values
    for ($h = 0; $h < 24; $h++) {
        $row['wind_speed_' . $h] = rand(0, 15);
    }
    
    $data[] = $row;
}

// Create form to submit to export
echo '<form method="POST" action="export_hourly_pdf.php" target="_blank">';
echo '<input type="hidden" name="data" value="' . htmlspecialchars(urlencode(serialize($data))) . '">';
echo '<input type="hidden" name="variables" value="temperature,humidity,wind">';
echo '<input type="hidden" name="start_date" value="2024-01-01">';
echo '<input type="hidden" name="end_date" value="2024-01-31">';
echo '<input type="hidden" name="time_type" value="hourly">';
echo '<h1>Test 31-Day Hourly Export</h1>';
echo '<p>Data: 31 days (Jan 1-31, 2024)</p>';
echo '<p>Variables: Temperature, Humidity, Wind Direction, Wind Speed</p>';
echo '<p>Expected pages: ~10 (3 for temp + 2 for humidity + 2 for wind dir + 2 for wind speed + 1 for signature)</p>';
echo '<button type="submit" style="padding: 10px 20px; font-size: 16px;">Generate PDF</button>';
echo '</form>';
?>
