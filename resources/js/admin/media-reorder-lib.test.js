import test from 'node:test';
import assert from 'node:assert/strict';
import { messageForStatus, readOrderedIds, reorderPayload, saveOrder } from './media-reorder-lib.js';

const item = (mediaId) => ({ dataset: { mediaId } });

test('lit les ids des medias dans l ordre affiche et ignore les valeurs non numeriques', () => {
    assert.deepEqual(readOrderedIds([item('12'), item('7'), item('abc'), item('3')]), [12, 7, 3]);
});

test('le corps du POST reprend le type, l id et les ids ordonnes', () => {
    assert.deepEqual(reorderPayload('article', '5', [3, 1]), { mediable_type: 'article', mediable_id: '5', ordered_ids: [3, 1] });
});

test('un statut 2xx ne produit aucun message', () => {
    assert.equal(messageForStatus(200), '');
    assert.equal(messageForStatus(204), '');
});

test('un 422 affiche le message du serveur pour ordered_ids', () => {
    assert.equal(messageForStatus(422, { errors: { ordered_ids: ['Il faut au moins 2 medias.'] } }), 'Il faut au moins 2 medias.');
});

test('un 422 sans detail garde un message generique', () => {
    assert.match(messageForStatus(422, {}), /Ordre/);
});

test('419, 403 et les autres erreurs ont chacun leur message', () => {
    assert.match(messageForStatus(419), /Session/);
    assert.match(messageForStatus(403), /refus/);
    assert.match(messageForStatus(500), /500/);
});

const fakeFetch = (status, body, calls) => async (url, options) => {
    calls.push({ url, options });

    return { status, json: async () => body };
};

test('saveOrder envoie le POST JSON avec le jeton CSRF et rend un message vide en cas de succes', async () => {
    const calls = [];
    const payload = reorderPayload('article', '5', [3, 1]);

    const message = await saveOrder({ url: '/admin/media/reorder', token: 'tok', payload, fetchFn: fakeFetch(200, { status: 'ok' }, calls) });

    assert.equal(message, '');
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/admin/media/reorder');
    assert.equal(calls[0].options.method, 'POST');
    assert.equal(calls[0].options.headers['X-CSRF-TOKEN'], 'tok');
    assert.deepEqual(JSON.parse(calls[0].options.body), payload);
});

test('saveOrder rend le message d une erreur serveur meme si la reponse n est pas du JSON', async () => {
    const fetchFn = async () => ({ status: 500, json: async () => { throw new SyntaxError('not json'); } });

    assert.match(await saveOrder({ url: '/x', token: '', payload: {}, fetchFn }), /500/);
});

test('saveOrder signale un echec reseau', async () => {
    const fetchFn = async () => { throw new TypeError('Failed to fetch'); };

    assert.match(await saveOrder({ url: '/x', token: '', payload: {}, fetchFn }), /seau/);
});
