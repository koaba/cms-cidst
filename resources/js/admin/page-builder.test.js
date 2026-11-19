import test from 'node:test';
import assert from 'node:assert/strict';
import { applyDesign, buildPayload, mapErrors, pageBuilder } from './page-builder.js';

// Le composant est un objet simple (Alpine l'enveloppe seulement dans le navigateur) :
// on appelle ses methodes directement, avec un faux window / document / fetch.

function builder(overrides = {}) {
    const b = pageBuilder();
    b.saveUrl = '/admin/pages/1/builder';
    b.version = 'v1';
    b.blocks = [
        { uid: 'b5', id: 5, type: 'texte', locked: false, data: { content: 'A' }, mediaId: null },
        { uid: 'b6', id: 6, type: 'texte', locked: false, data: { content: 'B' }, mediaId: null },
    ];

    return Object.assign(b, overrides);
}

function stubBrowser({ confirm = () => true, fetch = null } = {}) {
    const calls = { confirm: 0, reload: 0, fetch: [] };

    globalThis.window = {
        confirm: () => { calls.confirm++; return confirm(); },
        location: { reload: () => { calls.reload++; } },
    };
    globalThis.document = { querySelector: () => null };

    if (fetch) {
        globalThis.fetch = async (url, init) => { calls.fetch.push({ url, init }); return fetch(url, init); };
    }

    return calls;
}

const reply = (status, body) => ({ status, ok: status >= 200 && status < 300, json: async () => body });

// ------------------------------------------------------------ code de suppression : corps du POST

test('n envoie pas le code tant qu aucune suppression n est en attente', () => {
    const b = builder({ requiresDeleteCode: true, deleteCode: '4242', pendingDeletions: 0 });

    assert.equal('delete_code' in b.requestBody(), false);
});

test('envoie le code quand Publication a une suppression en attente', () => {
    const b = builder({ requiresDeleteCode: true, deleteCode: '4242', pendingDeletions: 1 });

    assert.equal(b.requestBody().delete_code, '4242');
});

test('n envoie jamais le code a un Super Admin, meme avec une suppression en attente', () => {
    const b = builder({ requiresDeleteCode: false, deleteCode: '4242', pendingDeletions: 2 });

    assert.equal('delete_code' in b.requestBody(), false);
});

// ------------------------------------------------------------ suppression d'un bloc

test('supprimer un bloc existant demande confirmation et compte une suppression en attente', () => {
    const calls = stubBrowser({ confirm: () => true });
    const b = builder();

    b.remove(0);

    assert.equal(calls.confirm, 1);
    assert.equal(b.pendingDeletions, 1);
    assert.deepEqual(b.blocks.map((block) => block.id), [6]);
    assert.equal(b.dirty, true);
});

test('refuser la confirmation ne supprime rien', () => {
    stubBrowser({ confirm: () => false });
    const b = builder();

    b.remove(0);

    assert.equal(b.pendingDeletions, 0);
    assert.equal(b.blocks.length, 2);
    assert.equal(b.dirty, false);
});

test('supprimer un bloc pas encore enregistre ne demande rien et ne compte pas', () => {
    const calls = stubBrowser();
    const b = builder();
    b.blocks.push({ uid: 'n1', id: null, type: 'citation', locked: false, data: {}, mediaId: null });

    b.remove(2);

    assert.equal(calls.confirm, 0);
    assert.equal(b.pendingDeletions, 0);
    assert.equal(b.blocks.length, 2);
});

test('un bloc verrouille ne se supprime pas', () => {
    const calls = stubBrowser();
    const b = builder();
    b.blocks[0].locked = true;

    b.remove(0);

    assert.equal(calls.confirm, 0);
    assert.equal(b.pendingDeletions, 0);
    assert.equal(b.blocks.length, 2);
});

// ------------------------------------------------------------ enregistrement

test('apres un enregistrement reussi, le code et le compteur de suppressions sont remis a zero', async () => {
    const calls = stubBrowser({ fetch: () => reply(200, { success: true, version: 'v2', blocks: [{ id: 6, type: 'texte', order: 0 }] }) });
    const b = builder({ requiresDeleteCode: true, deleteCode: '4242', pendingDeletions: 1 });
    b.blocks.splice(0, 1);

    await b.save();

    assert.equal(JSON.parse(calls.fetch[0].init.body).delete_code, '4242');
    assert.equal(b.pendingDeletions, 0);
    assert.equal(b.deleteCode, '');
    assert.equal(b.version, 'v2');
    assert.equal(b.statusKind, 'ok');
    assert.equal(b.saving, false);
});

test('un code refuse (422) garde la suppression en attente et affiche le message du serveur', async () => {
    stubBrowser({ fetch: () => reply(422, { errors: { delete_code: ['Code de suppression incorrect.'] } }) });
    const b = builder({ requiresDeleteCode: true, deleteCode: '0000', pendingDeletions: 1 });

    await b.save();

    assert.equal(b.pendingDeletions, 1);
    assert.equal(b.statusKind, 'error');
    assert.equal(b.status, 'Code de suppression incorrect.');
    assert.equal(b.saving, false);
});

test('une version perimee (409) bloque la page et n efface rien', async () => {
    stubBrowser({ fetch: () => reply(409, { code: 'stale' }) });
    const b = builder({ requiresDeleteCode: true, deleteCode: '4242', pendingDeletions: 1 });

    await b.save();

    assert.equal(b.stale, true);
    assert.equal(b.pendingDeletions, 1);
    assert.equal(b.deleteCode, '4242');
});

// ------------------------------------------------------------ ordre et identifiants (bogue du 19/11)

test('un nouveau bloc part sans cle id dans le JSON (contrat dont depend l ordre cote PHP)', () => {
    const payload = JSON.parse(JSON.stringify(buildPayload([
        { id: null, type: 'citation', data: { content: 'x' }, mediaId: null },
        { id: 5, type: 'texte', data: { content: 'A' }, mediaId: null },
        { id: 7, type: 'colonnes', locked: true, data: { secret: 1 } },
    ])));

    assert.equal('id' in payload[0], false);
    assert.equal(payload[1].id, 5);
    assert.deepEqual(payload[2], { id: 7, type: 'colonnes' });
});

test('relie les ids du serveur aux blocs par position quand les types correspondent', async () => {
    stubBrowser({ fetch: () => reply(200, { success: true, blocks: [
        { id: 9, type: 'citation', order: 0 },
        { id: 5, type: 'texte', order: 1 },
    ] }) });
    const b = builder();
    b.blocks = [
        { uid: 'n1', id: null, type: 'citation', locked: false, data: {}, mediaId: null },
        { uid: 'b5', id: 5, type: 'texte', locked: false, data: {}, mediaId: null },
    ];

    await b.save();

    assert.deepEqual(b.blocks.map((block) => block.id), [9, 5]);
    assert.equal(b.dirty, false);
});

test('recharge la page si l ordre ou les types renvoyes par le serveur divergent', async () => {
    const calls = stubBrowser({ fetch: () => reply(200, { success: true, blocks: [
        { id: 5, type: 'texte', order: 0 },
        { id: 9, type: 'citation', order: 1 },
    ] }) });
    const b = builder();
    b.blocks = [
        { uid: 'n1', id: null, type: 'citation', locked: false, data: {}, mediaId: null },
        { uid: 'b5', id: 5, type: 'texte', locked: false, data: {}, mediaId: null },
    ];

    await b.save();

    assert.equal(calls.reload, 1);
    assert.equal(b.blocks[0].id, null);
});

// ------------------------------------------------------------ erreurs et apparence

test('rattache les erreurs de bloc a son uid et les autres a _global', () => {
    const errors = mapErrors({
        'blocks.1.data.content': ['Le contenu est obligatoire.'],
        'blocks.0.id': ['Ce bloc apparait plusieurs fois.'],
        delete_code: ['Code incorrect.'],
    }, ['b5', 'n1']);

    assert.deepEqual(errors.n1, { content: ['Le contenu est obligatoire.'] });
    assert.deepEqual(errors.b5, { id: ['Ce bloc apparait plusieurs fois.'] });
    assert.deepEqual(errors._global, ['Code incorrect.']);
});

test('une valeur d apparence vide retire la cle, et design disparait quand il est vide', () => {
    const data = { content: 'A', design: { align: 'center' } };

    applyDesign(data, 'align', '');

    assert.equal('design' in data, false);
    assert.deepEqual(applyDesign({}, 'align', 'left'), { design: { align: 'left' } });
});