import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
Alpine.start();

const activityCanvas = document.getElementById('activityChart');
if (activityCanvas && window.dashboardActivity) {
    new Chart(activityCanvas, {
        type: 'bar',
        data: {
            labels: Object.keys(window.dashboardActivity),
            datasets: [{
                label: 'Articles publiés',
                data: Object.values(window.dashboardActivity),
                backgroundColor: '#C41E2A',
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
    });
}

const viewsCanvas = document.getElementById('viewsChart');
if (viewsCanvas && window.dashboardViews) {
    new Chart(viewsCanvas, {
        type: 'line',
        data: {
            labels: Object.keys(window.dashboardViews),
            datasets: [{
                label: 'Vues',
                data: Object.values(window.dashboardViews),
                borderColor: '#C41E2A',
                backgroundColor: 'rgba(196, 30, 42, 0.1)',
                fill: true,
                tension: 0.3,
            }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
    });
}