function persistOrder(container) {
    const url = container.dataset.reorderUrl;
    const orderedIds = [...container.querySelectorAll('[data-block-id]')]
        .map(el => parseInt(el.dataset.blockId, 10));

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            Accept: 'application/json',
        },
        body: JSON.stringify({ order: orderedIds }),
    }).catch(() => console.error('Échec de la sauvegarde de l\'ordre des blocs.'));
}

function attachReorder(container) {
    let draggedEl = null;

    container.querySelectorAll('[data-block-id]').forEach(item => {
        item.setAttribute('draggable', 'true');

        item.addEventListener('dragstart', event => {
            draggedEl = item;
            item.classList.add('opacity-50');
            event.dataTransfer.setData('text/plain', item.dataset.blockId);
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
            const isBefore = (event.clientY - rect.top) < rect.height / 2;
            container.insertBefore(draggedEl, isBefore ? item : item.nextSibling);
        });

        item.addEventListener('drop', event => {
            event.preventDefault();
            persistOrder(container);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-page-blocks-reorder]').forEach(attachReorder);
});