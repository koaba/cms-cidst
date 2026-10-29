import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    Filler,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
);

const baseOptions = {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
};

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
        options: baseOptions,
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
                tension: 0,
            }],
        },
        options: baseOptions,
    });
}
