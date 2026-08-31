// Gère les blocs dynamiques du formulaire article : galerie, diaporamas, vidéos.
// Utilisé par resources/views/admin/articles/create.blade.php et edit.blade.php.

const MAX_DIAPORAMAS = 4;
const MAX_VIDEOS = 5;

const DIAPORAMA_CONTAINER_ID = 'diaporamas-container';
const VIDEO_CONTAINER_ID = 'videos-container';
const ADD_DIAPORAMA_BTN_ID = 'add-diaporama-btn';
const ADD_VIDEO_BTN_ID = 'add-video-btn';

let diaporamaCount = 0;
let videoCount = 0;
let videoNewContainerId = VIDEO_CONTAINER_ID;

function previewNewUploads(input, containerId) {
    const container = document.getElementById(containerId);
    [...input.files].forEach(file => {
        const chip = document.createElement('div');
        chip.className = 'text-xs bg-gray-100 border rounded px-2 py-1';
        chip.textContent = file.name;
        container.appendChild(chip);
    });
}

function addExistingMedia(items, containerId, fieldName) {
    const container = document.getElementById(containerId);
    items.forEach(item => {
        const chip = document.createElement('div');
        chip.className = 'relative inline-block';
        chip.innerHTML = `
            <img src="${item.url}" class="w-16 h-16 object-cover rounded border" title="${item.name}">
            <input type="hidden" name="${fieldName}" value="${item.id}">
        `;
        container.appendChild(chip);
    });
}

function pickExistingMedia(containerId, fieldName) {
    MediaPicker.open({
        mode: 'multiple',
        onConfirm: items => addExistingMedia(items, containerId, fieldName),
    });
}

function toggleVideoSource(index, type) {
    document.getElementById(`video-${index}-upload`).classList.toggle('hidden', type !== 'upload');
    document.getElementById(`video-${index}-external`).classList.toggle('hidden', type !== 'external');
}

function countDiaporamas() {
    return document.getElementById(DIAPORAMA_CONTAINER_ID)?.children.length || 0;
}

function countVideos() {
    const ids = new Set([VIDEO_CONTAINER_ID, videoNewContainerId]);
    let total = 0;
    ids.forEach(id => {
        total += document.getElementById(id)?.children.length || 0;
    });
    return total;
}

function updateAddButtons() {
    const diaporamaBtn = document.getElementById(ADD_DIAPORAMA_BTN_ID);
    const videoBtn = document.getElementById(ADD_VIDEO_BTN_ID);
    if (diaporamaBtn) diaporamaBtn.disabled = countDiaporamas() >= MAX_DIAPORAMAS;
    if (videoBtn) videoBtn.disabled = countVideos() >= MAX_VIDEOS;
}

function addDiaporama() {
    if (countDiaporamas() >= MAX_DIAPORAMAS) return;
    const index = diaporamaCount++;
    const wrapper = document.createElement('div');
    wrapper.className = 'border rounded p-3';
    wrapper.id = `diaporama-${index}`;
    wrapper.innerHTML = `
        <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
            <input type="text" name="diaporamas[${index}][title]" placeholder="Titre du diaporama (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
            <button type="button" onclick="ArticleForm.removeDiaporama(${index})" class="text-red-600 text-sm">Supprimer</button>
        </div>
        <div id="diaporama-${index}-selected" class="flex flex-wrap gap-2 mb-2"></div>
        <div class="flex gap-2">
            <label class="text-sm border rounded px-3 py-2 cursor-pointer bg-gray-50 hover:bg-gray-100">
                + Uploader
                <input type="file" name="diaporamas[${index}][images][]" accept="image/*" multiple class="hidden" onchange="ArticleForm.previewNewUploads(this, 'diaporama-${index}-selected')">
            </label>
            <button type="button" class="text-sm border rounded px-3 py-2 bg-gray-50 hover:bg-gray-100" onclick="ArticleForm.pickExistingMedia('diaporama-${index}-selected', 'diaporamas[${index}][existing_media][]')">
                Choisir depuis la médiathèque
            </button>
        </div>
    `;
    document.getElementById(DIAPORAMA_CONTAINER_ID).appendChild(wrapper);
    updateAddButtons();
}

function removeDiaporama(index) {
    document.getElementById(`diaporama-${index}`)?.remove();
    updateAddButtons();
}

function addVideo() {
    if (countVideos() >= MAX_VIDEOS) return;
    const index = videoCount++;
    const wrapper = document.createElement('div');
    wrapper.className = 'border rounded p-3';
    wrapper.id = `video-${index}`;
    wrapper.innerHTML = `
        <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
            <input type="text" name="videos[${index}][title]" placeholder="Titre de la vidéo (optionnel)" class="border rounded p-2 text-sm flex-1 mr-2">
                        <button type="button" onclick="ArticleForm.removeVideo(${index})" class="text-red-600 text-sm">Supprimer</button>
        </div>
              <label class="flex items-center gap-1 text-xs text-gray-600 mb-2">
            <input type="checkbox" name="videos[${index}][apply_watermark]" value="1" ${videoWatermarkDefault ? 'checked' : ''}>
            Appliquer le filigrane
        </label>
        <div class="flex gap-4 mb-2 text-sm">
            <label class="flex items-center gap-1">
                <input type="radio" name="videos[${index}][source_type]" value="upload" checked onchange="ArticleForm.toggleVideoSource(${index}, 'upload')">
                Upload
            </label>
            <label class="flex items-center gap-1">
                <input type="radio" name="videos[${index}][source_type]" value="external" onchange="ArticleForm.toggleVideoSource(${index}, 'external')">
                Lien externe
            </label>
        </div>
        <div id="video-${index}-upload">
            <input type="file" name="videos[${index}][file]" accept="video/mp4,video/webm" class="w-full border rounded p-2 text-sm">
        </div>
        <div id="video-${index}-external" class="hidden">
            <input type="url" name="videos[${index}][url]" placeholder="https://youtube.com/..." class="w-full border rounded p-2 text-sm">
        </div>
    `;
    document.getElementById(videoNewContainerId).appendChild(wrapper);
    updateAddButtons();
}

function removeVideo(index) {
    document.getElementById(`video-${index}`)?.remove();
    updateAddButtons();
}

let videoWatermarkDefault = false;
function init() {
    const diaporamaContainer = document.getElementById(DIAPORAMA_CONTAINER_ID);
    diaporamaCount = parseInt(diaporamaContainer?.dataset.initialCount || '0', 10);
    const videoContainer = document.getElementById(VIDEO_CONTAINER_ID);
    videoCount = parseInt(videoContainer?.dataset.initialCount || '0', 10);
    videoNewContainerId = videoContainer?.dataset.newContainer || VIDEO_CONTAINER_ID;
    videoWatermarkDefault = videoContainer?.dataset.watermarkDefault === '1';
    updateAddButtons();
}

document.addEventListener('DOMContentLoaded', init);

window.ArticleForm = {
    previewNewUploads,
    addExistingMedia,
    pickExistingMedia,
    addDiaporama,
    removeDiaporama,
    addVideo,
    removeVideo,
    toggleVideoSource,
};