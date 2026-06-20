<?php
$mysqli = new mysqli("localhost","root","","meteorologi");
if ($mysqli->connect_error) die("Conn fail: " . $mysqli->connect_error);
$start = '2020-01-01';
$end = '2020-03-31';
$sql = "SELECT DATE_FORMAT(ANY_VALUE(w.DATE),'%Y-%m') as bulan,
       GROUP_CONCAT(DISTINCT w.wind_direction_avg_code ORDER BY w.wind_direction_avg_code SEPARATOR ',') as codes,
    (SELECT wd2.wind_direction_avg_code FROM wind_data wd2 WHERE wd2.wind_direction_avg_code IS NOT NULL AND wd2.wind_direction_avg_code != '' AND wd2.wind_direction_avg_code != 'Calm' AND YEAR(wd2.DATE)=YEAR(ANY_VALUE(w.DATE)) AND MONTH(wd2.DATE)=MONTH(ANY_VALUE(w.DATE)) GROUP BY wd2.wind_direction_avg_code ORDER BY COUNT(*) DESC LIMIT 1) as mode_dir
FROM wind_data w
WHERE w.DATE BETWEEN ? AND ?
GROUP BY YEAR(w.DATE), MONTH(w.DATE)
ORDER BY YEAR(w.DATE), MONTH(w.DATE)";
if ($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param('ss',$start,$end);
    $stmt->execute();
    $res = $stmt->get_result();
    echo "<pre>";
    while($r=$res->fetch_assoc()){
        print_r($r);
    }
    echo "</pre>";
    $stmt->close();
} else {
    echo "prepare failed: " . $mysqli->error;
}
$mysqli->close();
?>