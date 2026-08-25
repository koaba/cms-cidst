// Génère des miniatures JPEG pour les fichiers PDF sélectionnés (via pdf.js),
// stockées en JSON dans un input hidden, envoyées au serveur pour éviter de
// re-générer les miniatures côté back (upload seul ne suffit pas pour la 1re page).
//
// Utilisé par : admin/articles/edit.blade.php, admin/pdf-documents/create.blade.php,
// admin/pdf-documents/edit.blade.php.
//
// Markup attendu sur l'input file :
// <input type="file" class="js-pdf-thumbnail-input"
//        data-preview="ID_DU_CONTENEUR_MINIATURES"
//        data-data-input="ID_DE_L_INPUT_HIDDEN_JSON"
//        data-file-list="ID_DE_LA_LISTE_OPTIONNELLE">  (data-file-list est optionnel)

const PDFJS_VERSION = '3.11.174';
const PDFJS_BASE_URL = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}`;

function ensurePdfJsLoaded() {
    if (window.pdfjsLib) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `${PDFJS_BASE_URL}/pdf.min.js`;
        script.onload = () => {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = `${PDFJS_BASE_URL}/pdf.worker.min.js`;
            resolve();
        };
        script.onerror = () => reject(new Error('Impossible de charger pdf.js'));
        document.head.appendChild(script);
    });
}

async function generateThumbnail(file) {
    const arrayBuffer = await file.arrayBuffer();
    const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 0.5 });

    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

    return canvas.toDataURL('image/jpeg', 0.7);
}

async function handleFileChange(event) {
    const input = event.target;
    const files = Array.from(input.files);

    const previewId = input.dataset.preview;
    const dataInputId = input.dataset.dataInput;
    const fileListId = input.dataset.fileList;

    const preview = previewId ? document.getElementById(previewId) : null;
    const dataInput = dataInputId ? document.getElementById(dataInputId) : null;
    const fileList = fileListId ? document.getElementById(fileListId) : null;

    if (fileList) {
        fileList.innerHTML = '';
        files.forEach(file => {
            const li = document.createElement('li');
            li.textContent = `📄 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} Mo)`;
            fileList.appendChild(li);
        });
    }

    if (!preview || !dataInput) {
        return;
    }

    await ensurePdfJsLoaded();

    preview.innerHTML = '';
    const thumbnails = [];

    for (const file of files) {
        try {
            const dataUrl = await generateThumbnail(file);
            thumbnails.push({ name: file.name, thumbnail: dataUrl });

            const img = document.createElement('img');
            img.src = dataUrl;
            img.className = 'w-16 h-20 object-cover rounded border';
            preview.appendChild(img);
        } catch (err) {
            console.error('Erreur génération miniature PDF pour', file.name, err);
            thumbnails.push({ name: file.name, thumbnail: null });
        }
    }

    dataInput.value = JSON.stringify(thumbnails);
}

function init() {
    document.querySelectorAll('.js-pdf-thumbnail-input').forEach(input => {
        input.addEventListener('change', handleFileChange);
    });
}

document.addEventListener('DOMContentLoaded', init);