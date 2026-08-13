import Chart from 'chart.js/auto';

const canvas = document.getElementById('activityChart');
if (canvas && window.dashboardActivity) {
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: Object.keys(window.dashboardActivity),
            datasets: [{
                label: 'Articles publiés',
                data: Object.values(window.dashboardActivity),
                backgroundColor: '#C41E2A', // cidst-red, cohérence de marque
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
    });
}