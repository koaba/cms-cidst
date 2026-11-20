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
import { chartColors, chartOptions } from './chart-theme-lib.js';

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

// Couleurs du theme actif (Clair, Sombre, Bordeaux...) lues sur <html>.
const rootStyle = getComputedStyle(document.documentElement);
const colors = chartColors((name) => rootStyle.getPropertyValue(name));
const baseOptions = chartOptions(colors);

const activityCanvas = document.getElementById('activityChart');
if (activityCanvas && window.dashboardActivity) {
    new Chart(activityCanvas, {
        type: 'bar',
        data: {
            labels: Object.keys(window.dashboardActivity),
            datasets: [{
                label: 'Articles publiés',
                data: Object.values(window.dashboardActivity),
                backgroundColor: colors.primary,
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
                borderColor: colors.primary,
                backgroundColor: colors.primaryFill,
                fill: true,
                tension: 0,
            }],
        },
        options: baseOptions,
    });
}
