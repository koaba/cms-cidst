import { readOrderedIds, reorderPayload, saveOrder } from './media-reorder-lib.js';

async function persistOrder(container) {
    const status = container.querySelector('[data-reorder-status]');
    const orderedIds = readOrderedIds(container.querySelectorAll('[data-media-id]'));
    const message = await saveOrder({
        url: container.dataset.reorderUrl,
        token: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        payload: reorderPayload(container.dataset.modelType, container.dataset.modelId, orderedIds),
    });

    if (!status) return;

    status.textContent = message;
    status.classList.toggle('hidden', message === '');
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