// Reduit les photos choisies dans le formulaire de contact avant l'envoi :
// une photo de telephone (4032 x 3024 px) depasse la limite du serveur.
// Sans JavaScript, le formulaire fonctionne comme avant et le serveur valide les fichiers.
import { SUPPORTED_TYPES, encodingAttempts, fitWithin, outputName } from './contact-images-lib.js';

const input = document.getElementById('images');
const status = document.getElementById('images_status');
let run = 0;

async function decode(file) {
    try {
        return await createImageBitmap(file, { imageOrientation: 'from-image' });
    } catch (error) {
        return createImageBitmap(file);
    }
}

function encode(canvas, attempt) {
    return new Promise((resolve) => canvas.toBlob(resolve, attempt.type, attempt.quality));
}

async function shrink(file, maxSide, maxBytes) {
    if (!SUPPORTED_TYPES.includes(file.type)) {
        return file;
    }

    const bitmap = await decode(file);
    const { width, height } = fitWithin(bitmap.width, bitmap.height, maxSide);

    if (width === bitmap.width && height === bitmap.height && file.size <= maxBytes) {
        bitmap.close();

        return file;
    }

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d');
    let best = null;

    for (const attempt of encodingAttempts(file.type)) {
        context.clearRect(0, 0, width, height);

        if (attempt.type === 'image/jpeg') {
            context.fillStyle = '#fff';
            context.fillRect(0, 0, width, height);
        }

        context.drawImage(bitmap, 0, 0, width, height);
        const blob = await encode(canvas, attempt);

        // Un navigateur qui ne sait pas encoder le type demande renvoie un autre type (ou rien).
        if (!blob || !SUPPORTED_TYPES.includes(blob.type)) {
            continue;
        }

        best = blob;

        if (blob.size <= maxBytes) {
            break;
        }
    }

    bitmap.close();

    return best
        ? new File([best], outputName(file.name, best.type), { type: best.type, lastModified: Date.now() })
        : file;
}

async function onChange() {
    const maxSide = Number(input.dataset.maxSide);
    const maxBytes = Number(input.dataset.maxBytes);

    if (!maxSide || !maxBytes || input.files.length === 0) {
        return;
    }

    const current = ++run;
    const submit = input.form.querySelector('[type="submit"]');
    submit.disabled = true;
    status.textContent = 'Préparation des photos…';

    try {
        const originals = Array.from(input.files);
        const results = [];

        for (const file of originals) {
            results.push(await shrink(file, maxSide, maxBytes));
        }

        if (current !== run) {
            return;
        }

        const transfer = new DataTransfer();
        results.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        status.textContent = results.some((file, i) => file !== originals[i])
            ? 'Photo(s) réduite(s) automatiquement avant l’envoi.'
            : '';
    } catch (error) {
        // On garde les fichiers d'origine : le serveur les validera et expliquera tout refus.
        status.textContent = '';
    } finally {
        if (current === run) {
            submit.disabled = false;
        }
    }
}

if (input && status && 'createImageBitmap' in window && typeof DataTransfer === 'function') {
    input.addEventListener('change', onChange);
}
