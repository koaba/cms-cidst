// Fonctions pures de la reduction des photos du formulaire de contact.
// Testees avec `npm run test:js` (le navigateur n'est pas necessaire).

export const SUPPORTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

const EXTENSIONS = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp' };

/** Dimensions reduites pour que le plus grand cote ne depasse pas maxSide. Jamais d'agrandissement. */
export function fitWithin(width, height, maxSide) {
    const scale = Math.min(1, maxSide / Math.max(width, height));

    return {
        width: Math.max(1, Math.round(width * scale)),
        height: Math.max(1, Math.round(height * scale)),
    };
}

/** Essais d'encodage, du plus fidele au plus leger. Un PNG trop lourd repasse en JPEG. */
export function encodingAttempts(type) {
    const qualities = [0.85, 0.7, 0.55];

    if (type === 'image/png') {
        return [{ type: 'image/png' }, ...qualities.map((quality) => ({ type: 'image/jpeg', quality }))];
    }

    return qualities.map((quality) => ({ type, quality }));
}

/** Nom de fichier avec l'extension du type reellement encode. */
export function outputName(name, type) {
    const base = name.replace(/\.[^./\\]+$/, '') || 'image';

    return `${base}.${EXTENSIONS[type]}`;
}
