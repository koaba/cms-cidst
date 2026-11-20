import test from 'node:test';
import assert from 'node:assert/strict';
import { chartColors, chartOptions, parseRgbTriplet } from './chart-theme-lib.js';

// Variables du theme Sombre, telles que ThemeStyleBuilder les emet (triplets RGB sans virgule).
const sombre = {
    '--color-primary-rgb': ' 255 107 115 ',
    '--color-muted-rgb': '163 170 184',
    '--color-border-rgb': '46 52 64',
};
const readFrom = (vars) => (name) => vars[name] ?? '';

test('lit un triplet RGB, espaces superflus ignores', () => {
    assert.deepEqual(parseRgbTriplet(' 196 30  42 '), [196, 30, 42]);
    assert.deepEqual(parseRgbTriplet('0 0 0'), [0, 0, 0]);
});

test('refuse un triplet absent, incomplet, hors limites ou non numerique', () => {
    for (const bad of ['', '   ', 'abc', '1 2', '1 2 3 4', '256 0 0', '-1 0 0', '1.5 2 3', '#C41E2A', undefined, null]) {
        assert.equal(parseRgbTriplet(bad), null, `devrait refuser ${JSON.stringify(bad)}`);
    }
});

test('les couleurs des graphiques suivent le theme actif', () => {
    assert.deepEqual(chartColors(readFrom(sombre)), {
        primary: 'rgb(255, 107, 115)',
        primaryFill: 'rgba(255, 107, 115, 0.1)',
        text: 'rgb(163, 170, 184)',
        grid: 'rgb(46, 52, 64)',
    });
});

test('sans variable de theme, les couleurs du theme par defaut sont utilisees', () => {
    assert.deepEqual(chartColors(readFrom({})), {
        primary: 'rgb(196, 30, 42)',
        primaryFill: 'rgba(196, 30, 42, 0.1)',
        text: 'rgb(75, 80, 87)',
        grid: 'rgb(216, 218, 222)',
    });
});

test('une variable invalide retombe sur le defaut de ce role seulement', () => {
    const colors = chartColors(readFrom({ ...sombre, '--color-muted-rgb': 'rouge' }));

    assert.equal(colors.text, 'rgb(75, 80, 87)');
    assert.equal(colors.primary, 'rgb(255, 107, 115)');
    assert.equal(colors.grid, 'rgb(46, 52, 64)');
});

test('les options appliquent le texte et la grille du theme aux deux axes', () => {
    const options = chartOptions(chartColors(readFrom(sombre)));

    assert.equal(options.scales.x.ticks.color, 'rgb(163, 170, 184)');
    assert.equal(options.scales.y.ticks.color, 'rgb(163, 170, 184)');
    assert.equal(options.scales.x.grid.color, 'rgb(46, 52, 64)');
    assert.equal(options.scales.y.grid.color, 'rgb(46, 52, 64)');
});

test('les options gardent l axe Y entier a partir de zero et sans legende', () => {
    const options = chartOptions(chartColors(readFrom({})));

    assert.equal(options.scales.y.beginAtZero, true);
    assert.equal(options.scales.y.ticks.stepSize, 1);
    assert.equal(options.plugins.legend.display, false);
});
