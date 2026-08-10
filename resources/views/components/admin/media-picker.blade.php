@once
<div id="media-picker-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded p-4 w-full max-w-2xl max-h-[80vh] flex flex-col">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold" id="media-picker-title">Choisir des images</h3>
            <button type="button" onclick="MediaPicker.close()" class="text-gray-500">&times;</button>
        </div>
        <input type="text" id="media-picker-search" placeholder="Rechercher..."
               class="border rounded p-2 mb-3" oninput="MediaPicker.search(this.value)">
        <div id="media-picker-grid" class="grid grid-cols-4 gap-2 overflow-y-auto flex-1"></div>
        <div id="media-picker-footer" class="hidden justify-end gap-2 mt-3 pt-3 border-t">
            <button type="button" onclick="MediaPicker.close()" class="border rounded px-3 py-2 text-sm">Annuler</button>
            <button type="button" onclick="MediaPicker.confirm()" class="bg-blue-600 text-white rounded px-3 py-2 text-sm">Valider la sélection</button>
        </div>
    </div>
</div>

<script>
    window.MediaPicker = (function () {
        const PICKER_URL = "{{ route('admin.media.picker') }}";
        let mode = 'multiple';
        let onConfirm = () => {};
        let selection = new Map();
        let searchTimeout = null;

        function open({ mode: m = 'multiple', onConfirm: cb = () => {} }) {
            mode = m;
            onConfirm = cb;
            selection = new Map();
            document.getElementById('media-picker-title').textContent =
                mode === 'single' ? 'Choisir une image' : 'Choisir des images';
            document.getElementById('media-picker-footer').classList.toggle('hidden', mode !== 'multiple');
            document.getElementById('media-picker-footer').classList.toggle('flex', mode === 'multiple');
            document.getElementById('media-picker-search').value = '';
            document.getElementById('media-picker-modal').classList.remove('hidden');
            load('');
        }

        function close() {
            document.getElementById('media-picker-modal').classList.add('hidden');
        }

        function search(value) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => load(value), 300);
        }

        function load(query) {
            fetch(`${PICKER_URL}?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => render(data.data || []));
        }

function render(items) {
    const grid = document.getElementById('media-picker-grid');
    grid.innerHTML = '';
    items.forEach(item => {
        const el = document.createElement('div');
        el.className = 'cursor-pointer border-2 rounded overflow-hidden border-transparent hover:border-blue-300 bg-gray-100';
        el.innerHTML = `<img src="${item.url}" loading="lazy" class="w-full h-20 object-cover" title="${item.name}">`;
        el.onclick = () => select(item, el);
        grid.appendChild(el);
    });
}
function select(item, el) {
    if (mode === 'single') {
        onConfirm([item]);
        close();
        return;
    }
    if (selection.has(item.id)) {
        selection.delete(item.id);
        el.classList.remove('border-blue-600');
        el.classList.add('border-transparent');
    } else {
        selection.set(item.id, item);
        el.classList.remove('border-transparent');
        el.classList.add('border-blue-600');
    }
}

        function confirm() {
            onConfirm(Array.from(selection.values()));
            close();
        }

        return { open, close, search, confirm };
    })();
</script>
@endonce