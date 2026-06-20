function createChart(labels, data1, data2, data3, labelsData) {
    var ctx = document.getElementById("myChart").getContext("2d");

    // Cek jika tidak ada data
    if (!labels.length) {
        console.warn('No data available to display.');
        return;
    }

    new Chart(ctx, {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: labelsData[0],
                data: data1,
                borderColor: "rgba(75, 192, 192, 1)",
                backgroundColor: "rgba(75, 192, 192, 0.2)",
                fill: false
            },
            ...(data2 ? [{
                label: labelsData[1],
                data: data2,
                borderColor: "rgba(255, 99, 132, 1)",
                backgroundColor: "rgba(255, 99, 132, 0.2)",
                fill: false
            }] : []),
            ...(data3 ? [{
                label: labelsData[2],
                data: data3,
                borderColor: "rgba(54, 162, 235, 1)",
                backgroundColor: "rgba(54, 162, 235, 0.2)",
                fill: false
            }] : [])]
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
                export: {
                    // Konfigurasi plugin ekspor
                    enabled: true,
                    fileName: 'chart_export',
                    format: 'png' // Format file, bisa 'png', 'jpeg', dll.
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
}


