// Zone de dépôt générique (drag & drop de fichiers depuis l'Explorateur)
// pour n'importe quelle zone d'upload marquée data-dropzone.
// Injecte les fichiers déposés dans le premier <input type="file"> trouvé
// à l'intérieur, puis déclenche 'change' pour réutiliser les handlers
// déjà existants (previewNewUploads, pdf-thumbnail.js, etc.).

function setInputFiles(input, fileList) {
    const dt = new DataTransfer();
    const files = input.multiple ? [...fileList] : [fileList[0]];
    files.forEach(file => {
        if (file) dt.items.add(file);
    });
    input.files = dt.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function attachDropzone(container) {
    const input = container.querySelector('input[type="file"]');
    if (!input) return;

    ['dragenter', 'dragover'].forEach(evt => {
        container.addEventListener(evt, event => {
            event.preventDefault();
            container.classList.add('ring-2', 'ring-blue-400', 'bg-blue-50');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        container.addEventListener(evt, event => {
            event.preventDefault();
            container.classList.remove('ring-2', 'ring-blue-400', 'bg-blue-50');
        });
    });

    container.addEventListener('drop', event => {
        const files = event.dataTransfer?.files;
        if (files && files.length > 0) {
            setInputFiles(input, files);
        }
    });
}

function scan() {
    document.querySelectorAll('[data-dropzone]').forEach(container => {
        if (container.dataset.dropzoneAttached) return;
        container.dataset.dropzoneAttached = '1';
        attachDropzone(container);
    });
}

document.addEventListener('DOMContentLoaded', scan);

window.FileDropzone = { scan };