<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Utama</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <?php include "Layout/Layout/header.html"?><hr><br>
    <main>
        <div class="sambutan">
            <h2>SELAMAT DATANG DI WEBSITE BMKG</h2>
            <p>Silahkan pilih menu di bawah ini untuk mengetahui informasi yang sedang anda butuhkan.</p>
        </div>

        <div class="weather-container">
            <div class="weather-box1">
                <h2>Rainfall (Curah Hujan)</h2>
                <p>Curah hujan merupakan ketinggian air hujan yang terkumpul dalam tempat yang datar, tidak menguap, tidak meresap, dan tidak mengalir.</p>
                <button onclick="location.href='handlers/upload.php?fileType=rainfall_data'" class="btnbody">Upload Data</button>
                <button onclick="location.href='data.php?fileType=rainfall_data'" class="btnbody">Print Data</button>
                <button onclick="location.href='export/export_excel.php?data_type=rainfall'" class="btnbody" style="background-color: #ff9800;">Export Data</button>
            </div>
            <div class="weather-box2">
                <h2>Humidity (Kelembapan Udara)</h2>
                <p>Kelembapan udara adalah kandungan uap air yang ada di dalam udara dalam bentuk gas pada suatu tempat.</p>
                <button onclick="location.href='handlers/upload.php?fileType=humidity_datalog'" class="btnbody">Upload Data</button>
                <button onclick="location.href='data.php?fileType=humidity_datalog'" class="btnbody">Print Data</button>
                <button onclick="location.href='export/export_excel.php?data_type=humidity'" class="btnbody" style="background-color: #ff9800;">Export Data</button>
            </div>
            <div class="weather-box3">
                <h2>Wind (Kecepatan Angin)</h2>
                <p>Kecepatan angin adalah laju pergerakan udara di atmosfer, biasanya diukur dalam kilometer per jam (km/jam).</p>
                <button onclick="location.href='handlers/upload.php?fileType=wind_datalog'" class="btnbody">Upload Data</button>
                <button onclick="location.href='data.php?fileType=wind_datalog'" class="btnbody">Print Data</button>
                <button onclick="location.href='export/export_excel.php?data_type=wind'" class="btnbody" style="background-color: #ff9800;">Export Data</button>
            </div>
            <div class="weather-box4">
                <h2>Temperature (Suhu)</h2>
                <p>Temperature adalah ukuran banyaknya energi matahari yang diterima oleh suatu permukaan per satuan luas.</p>
                <button onclick="location.href='handlers/upload.php?fileType=temperature_datalog'" class="btnbody">Upload Data</button>
                <button onclick="location.href='data.php?fileType=temperature_datalog'" class="btnbody">Print Data</button>
                <button onclick="location.href='export/export_excel.php?data_type=temperature'" class="btnbody" style="background-color: #ff9800;">Export Data</button>
            </div>
        </div>
    </main>
    <?php include "Layout/Layout/footer.html"?>
</body>
</html>
