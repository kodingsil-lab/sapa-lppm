document.addEventListener('DOMContentLoaded', function () {
    const chartEl = document.getElementById('dosenChart');
    if (chartEl && window.Chart) {
        const labels = JSON.parse(chartEl.dataset.labels || '[]');
        const values = JSON.parse(chartEl.dataset.values || '[]');

        new Chart(chartEl, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pengajuan',
                    data: values,
                    backgroundColor: '#0d6efd',
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 2 }
                    }
                }
            }
        });
    }
});
