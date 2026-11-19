// Fonctions pures du glisser-deposer des medias (ordre de la galerie).
// Testees avec `npm run test:js` (le navigateur n'est pas necessaire).

const NETWORK_MESSAGE = '\u00c9chec r\u00e9seau : l\u2019ordre n\u2019a pas \u00e9t\u00e9 enregistr\u00e9.';

/** Ids numeriques des elements [data-media-id], dans l'ordre affiche. */
export function readOrderedIds(elements) {
    return [...elements].map((el) => parseInt(el.dataset.mediaId, 10)).filter(Number.isInteger);
}

export function reorderPayload(type, id, orderedIds) {
    return { mediable_type: type, mediable_id: id, ordered_ids: orderedIds };
}

/** Message a afficher selon la reponse du serveur ('' = succes). */
export function messageForStatus(status, body = {}) {
    if (status >= 200 && status < 300) return '';
    if (status === 422) return body.errors?.ordered_ids?.[0] ?? body.message ?? 'Ordre invalide : l\u2019ordre n\u2019a pas \u00e9t\u00e9 enregistr\u00e9.';
    if (status === 419) return 'Session expir\u00e9e : rechargez la page (l\u2019ordre n\u2019est pas enregistr\u00e9).';
    if (status === 403) return 'Acc\u00e8s refus\u00e9.';

    return `Erreur ${status} : l\u2019ordre n\u2019a pas \u00e9t\u00e9 enregistr\u00e9.`;
}

/** Envoie l'ordre au serveur. Retourne le message d'erreur a afficher, ou '' si tout va bien. */
export async function saveOrder({ url, token, payload, fetchFn = fetch }) {
    try {
        const response = await fetchFn(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            body: JSON.stringify(payload),
        });
        const body = await response.json().catch(() => ({}));

        return messageForStatus(response.status, body);
    } catch {
        return NETWORK_MESSAGE;
    }
}
