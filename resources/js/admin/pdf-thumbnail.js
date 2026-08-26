// Gere les miniatures JPEG pour les fichiers PDF selectionnes (via pdf.js), stockees en JSON dans un input hidden.
// Utilise par : admin/articles/create.blade.php, admin/articles/edit.blade.php, admin/pdf-documents/create.blade.php, admin/pdf-documents/edit.blade.php.
// Maintient un etat des fichiers en memoire, reconstruit input.files via DataTransfer a chaque ajout/retrait.
// Markup attendu : <input class="js-pdf-thumbnail-input" data-preview="..." data-data-input="..." data-max="10">

const PDFJS_VERSION = "3.11.174";
const PDFJS_BASE_URL = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}`;
const state = new WeakMap();
let uniqueId = 0;

function ensurePdfJsLoaded() {
    if (window.pdfjsLib) { return Promise.resolve(); }
    return new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.src = `${PDFJS_BASE_URL}/pdf.min.js`;
        script.onload = () => {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = `${PDFJS_BASE_URL}/pdf.worker.min.js`;
            resolve();
        };
        script.onerror = () => reject(new Error("Impossible de charger pdf.js"));
        document.head.appendChild(script);
    });
}

async function generateThumbnail(file) {
    const arrayBuffer = await file.arrayBuffer();
    const pdf = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
    const page = await pdf.getPage(1);
    const viewport = page.getViewport({ scale: 0.5 });
    const canvas = document.createElement("canvas");
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvasContext: canvas.getContext("2d"), viewport }).promise;
    return canvas.toDataURL("image/jpeg", 0.7);
}

function formatSize(bytes) {
    return (bytes / 1024 / 1024).toFixed(2) + " Mo";
}

function syncInputFiles(input) {
    const entries = state.get(input) ?? [];
    const dataTransfer = new DataTransfer();
    entries.forEach(entry => dataTransfer.items.add(entry.file));
    input.files = dataTransfer.files;
}

function removeFile(input, id) {
    const entries = state.get(input) ?? [];
    state.set(input, entries.filter(entry => entry.id !== id));
    syncInputFiles(input);
    renderCards(input);
}

function renderCards(input) {
    const previewId = input.dataset.preview;
    const dataInputId = input.dataset.dataInput;
    const preview = previewId ? document.getElementById(previewId) : null;
    const dataInput = dataInputId ? document.getElementById(dataInputId) : null;
    const entries = state.get(input) ?? [];
    if (preview) {
        preview.innerHTML = "";
        entries.forEach(entry => {
            const card = document.createElement("div");
            card.className = "relative w-24 border rounded-lg bg-gray-50 shadow-sm overflow-hidden";
            const thumbHtml = entry.thumbnail
                ? `<img src="${entry.thumbnail}" class="w-full h-full object-cover" alt="">`
                : `<span class="text-2xl" aria-hidden="true">&#128196;</span>`;
            card.innerHTML = `<button type="button" data-remove-id="${entry.id}" class="absolute top-1 right-1 w-5 h-5 flex items-center justify-center rounded-full bg-white/90 text-red-600 text-xs font-bold shadow hover:bg-red-50" aria-label="Retirer ce fichier">&times;</button><div class="w-full h-20 flex items-center justify-center bg-white border-b">${thumbHtml}</div><div class="px-1.5 py-1"><p class="text-[11px] font-medium truncate" title="${entry.file.name}">${entry.file.name}</p><p class="text-[10px] text-gray-500">${formatSize(entry.file.size)}</p></div>`;
            card.querySelector("[data-remove-id]").addEventListener("click", () => removeFile(input, entry.id));
            preview.appendChild(card);
        });
    }
    if (dataInput) {
        dataInput.value = JSON.stringify(entries.map(entry => ({ name: entry.file.name, thumbnail: entry.thumbnail })));
    }
}

async function addFiles(input, newFiles) {
    const existing = state.get(input) ?? [];
    const max = parseInt(input.dataset.max || "0", 10);
    let incoming = newFiles;
    if (max > 0 && existing.length + newFiles.length > max) {
        incoming = newFiles.slice(0, Math.max(0, max - existing.length));
        window.alert(`Seuls ${incoming.length} fichier(s) sur ${newFiles.length} ont ete ajoutes (maximum ${max} au total).`);
    }
    const newEntries = incoming.map(file => ({ id: uniqueId++, file, thumbnail: null }));
    state.set(input, [...existing, ...newEntries]);
    syncInputFiles(input);
    renderCards(input);
    if (newEntries.length === 0) { return; }
    await ensurePdfJsLoaded();
    for (const entry of newEntries) {
        try {
            entry.thumbnail = await generateThumbnail(entry.file);
        } catch (err) {
            console.error("Erreur generation miniature PDF pour", entry.file.name, err);
            entry.thumbnail = null;
        }
        renderCards(input);
    }
}

function handleFileChange(event) {
    const input = event.target;
    const newFiles = Array.from(input.files);
    addFiles(input, newFiles);
}

function init() {
    document.querySelectorAll(".js-pdf-thumbnail-input").forEach(input => {
        state.set(input, []);
        input.addEventListener("change", handleFileChange);
    });
}

document.addEventListener("DOMContentLoaded", init);
