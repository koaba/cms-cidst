function persistOrder(container) {
    const url = container.dataset.reorderUrl;
    const modelType = container.dataset.modelType;
    const modelId = container.dataset.modelId;
    const orderedIds = [...container.querySelectorAll('[data-media-id]')]
        .map(el => parseInt(el.dataset.mediaId, 10));

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            Accept: 'application/json',
        },
        body: JSON.stringify({ mediable_type: modelType, mediable_id: modelId, ordered_ids: orderedIds }),
    }).catch(() => console.error('Échec de la sauvegarde de l\'ordre des médias.'));
}

function attachReorder(container) {
    let draggedEl = null;

    container.querySelectorAll('[data-media-id]').forEach(item => {
        item.setAttribute('draggable', 'true');

        item.addEventListener('dragstart', event => {
            draggedEl = item;
            item.classList.add('opacity-50');
            event.dataTransfer.setData('text/plain', item.dataset.mediaId);
            event.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-50');
            draggedEl = null;
        });

        item.addEventListener('dragover', event => {
            event.preventDefault();
            if (!draggedEl || draggedEl === item) return;
            const rect = item.getBoundingClientRect();
            const isBefore = (event.clientX - rect.left) < rect.width / 2;
            container.insertBefore(draggedEl, isBefore ? item : item.nextSibling);
        });

        item.addEventListener('drop', event => {
            event.preventDefault();
            persistOrder(container);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-media-reorder]').forEach(attachReorder);
});