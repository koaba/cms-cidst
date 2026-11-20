// Couleurs des graphiques du tableau de bord, lues dans le theme actif (variables --color-*-rgb
// emises par ThemeStyleBuilder). Fonctions pures, testees avec `npm run test:js`.

// Theme par defaut (config/themes.php, rouge du logo) : utilise si une variable manque ou est invalide.
const FALLBACK = {
    '--color-primary-rgb': [196, 30, 42],
    '--color-muted-rgb': [75, 80, 87],
    '--color-border-rgb': [216, 218, 222],
};

/** Triplet « R G B » (entiers 0 a 255) en tableau, ou null s'il est absent ou invalide. */
export function parseRgbTriplet(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const parts = value.trim().split(/\s+/);

    if (parts.length !== 3 || !parts.every((part) => /^\d{1,3}$/.test(part))) {
        return null;
    }

    const channels = parts.map(Number);

    return channels.every((channel) => channel <= 255) ? channels : null;
}

/** @param {(name: string) => string} readVar lit une variable CSS du theme */
export function chartColors(readVar) {
    const channels = (name) => parseRgbTriplet(readVar(name)) ?? FALLBACK[name];
    const rgb = ([r, g, b]) => `rgb(${r}, ${g}, ${b})`;
    const [r, g, b] = channels('--color-primary-rgb');

    return {
        primary: rgb([r, g, b]),
        primaryFill: `rgba(${r}, ${g}, ${b}, 0.1)`,
        text: rgb(channels('--color-muted-rgb')),
        grid: rgb(channels('--color-border-rgb')),
    };
}

export function chartOptions(colors) {
    const axis = { ticks: { color: colors.text }, grid: { color: colors.grid } };

    return {
        plugins: { legend: { display: false } },
        scales: {
            x: axis,
            y: { ...axis, beginAtZero: true, ticks: { ...axis.ticks, stepSize: 1 } },
        },
    };
}
