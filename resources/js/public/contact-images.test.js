import test from 'node:test';
import assert from 'node:assert/strict';
import { SUPPORTED_TYPES, encodingAttempts, fitWithin, outputName } from './contact-images-lib.js';

test('reduit une photo de telephone paysage a 3000 px', () => {
    assert.deepEqual(fitWithin(4032, 3024, 3000), { width: 3000, height: 2250 });
});

test('reduit une photo portrait en gardant les proportions', () => {
    assert.deepEqual(fitWithin(3024, 4032, 3000), { width: 2250, height: 3000 });
});

test('ne touche pas une image deja assez petite et ne l agrandit jamais', () => {
    assert.deepEqual(fitWithin(800, 600, 3000), { width: 800, height: 600 });
    assert.deepEqual(fitWithin(3000, 2000, 3000), { width: 3000, height: 2000 });
});

test('garde au moins un pixel sur le petit cote', () => {
    assert.deepEqual(fitWithin(4000, 1, 3000), { width: 3000, height: 1 });
});

test('les types acceptes sont ceux du serveur', () => {
    assert.deepEqual(SUPPORTED_TYPES, ['image/jpeg', 'image/png', 'image/webp']);
});

test('un jpeg est encode en jpeg a qualite decroissante', () => {
    const attempts = encodingAttempts('image/jpeg');

    assert.equal(attempts.length, 3);
    assert.ok(attempts.every((a) => a.type === 'image/jpeg'));
    assert.deepEqual(attempts.map((a) => a.quality), [0.85, 0.7, 0.55]);
});

test('un png reste png d abord puis repasse en jpeg s il est trop lourd', () => {
    const attempts = encodingAttempts('image/png');

    assert.deepEqual(attempts[0], { type: 'image/png' });
    assert.ok(attempts.slice(1).every((a) => a.type === 'image/jpeg'));
    assert.equal(attempts.length, 4);
});

test('le nom de fichier prend l extension du type reellement encode', () => {
    assert.equal(outputName('photo.jpeg', 'image/jpeg'), 'photo.jpg');
    assert.equal(outputName('capture.png', 'image/jpeg'), 'capture.jpg');
    assert.equal(outputName('sans-extension', 'image/webp'), 'sans-extension.webp');
    assert.equal(outputName('.png', 'image/png'), 'image.png');
    assert.equal(outputName('mon.fichier.final.png', 'image/png'), 'mon.fichier.final.png');
});
