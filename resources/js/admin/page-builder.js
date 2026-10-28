/**
 * Page Builder (admin) : logique uniquement.
 * Les classes Tailwind vivent dans la vue Blade : le scan Tailwind ne lit pas resources/js.
 */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const clone = (value) => JSON.parse(JSON.stringify(value));

/** Blocs verrouilles : id et type seulement. Nouveaux blocs : sans id. */
export function buildPayload(blocks) {
    return blocks.map((block) => (block.locked
        ? { id: block.id, type: block.type }
        : { id: block.id ?? undefined, type: block.type, data: block.data }));
}

/**
 * Convertit les erreurs Laravel (`blocks.N.data.champ`, `blocks.N.id`...)
 * en { uid: { champ: [messages] }, _global: [messages] }.
 */
export function mapErrors(errors, uids) {
    const out = {};

    for (const [key, messages] of Object.entries(errors)) {
        const match = key.match(/^blocks\.(\d+)\.(?:data\.)?(.+)$/);
        const uid = match ? uids[Number(match[1])] : null;

        if (!uid) {
            (out._global ??= []).push(...messages);
            continue;
        }

        ((out[uid] ??= {})[match[2]] ??= []).push(...messages);
    }

    return out;
}

export function pageBuilder() {
    return {
        types: {},
        saveUrl: '',
        blocks: [],
        selectedUid: null,
        errors: {},
        dirty: false,
        saving: false,
        status: '',
        statusKind: 'idle',
        dragIndex: null,
        paletteType: null,
        seq: 0,

        init() {
            const config = JSON.parse(this.$root.dataset.config);
            this.types = config.types;
            this.saveUrl = config.saveUrl;
            this.blocks = config.blocks.map((raw) => this.hydrate(raw));

            window.addEventListener('beforeunload', (event) => {
                if (this.dirty) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
        },

        hydrate(raw) {
            return {
                uid: `b${raw.id}`,
                id: raw.id,
                type: raw.type,
                label: raw.label,
                locked: raw.locked,
                editUrl: raw.editUrl ?? null,
                childrenCount: raw.childrenCount ?? 0,
                data: raw.locked ? {} : { ...clone(this.types[raw.type]?.defaults ?? {}), ...(raw.data ?? {}) },
            };
        },

        get palette() {
            return Object.entries(this.types)
                .filter(([, definition]) => definition.creatable)
                .map(([type, definition]) => ({ type, label: definition.label }));
        },

        get current() {
            return this.blocks.find((block) => block.uid === this.selectedUid) ?? null;
        },

        get fields() {
            return this.current && !this.current.locked ? (this.types[this.current.type]?.fields ?? []) : [];
        },

        summary(block) {
            const clip = (text, max = 90) => {
                const value = String(text ?? '').trim();
                return value.length > max ? `${value.slice(0, max)}…` : value;
            };

            if (block.locked) {
                return block.childrenCount > 0 ? `${block.childrenCount} bloc(s) imbriqué(s)` : 'Bloc complexe';
            }

            const data = block.data;
            switch (block.type) {
                case 'texte': return clip(data.title || data.content);
                case 'banniere_hero': return clip(data.titre);
                case 'bouton': return clip(data.label);
                case 'citation': return clip(data.content);
                case 'separateur': return data.style === 'epais' ? 'Trait épais' : 'Trait fin';
                default: return '';
            }
        },

        select(uid) { this.selectedUid = uid; },
        close() { this.selectedUid = null; },

        markDirty() {
            this.dirty = true;
            this.status = '';
            this.statusKind = 'idle';
        },

        setStatus(text, kind) {
            this.status = text;
            this.statusKind = kind;
        },

        insertionIndex() {
            const index = this.blocks.findIndex((block) => block.uid === this.selectedUid);
            return index === -1 ? this.blocks.length : index + 1;
        },

        add(type, at = null) {
            const definition = this.types[type];
            if (!definition?.creatable) return;

            const block = {
                uid: `n${++this.seq}`,
                id: null,
                type,
                label: definition.label,
                locked: false,
                editUrl: null,
                childrenCount: 0,
                data: clone(definition.defaults),
            };

            this.blocks.splice(at ?? this.insertionIndex(), 0, block);
            this.selectedUid = block.uid;
            this.markDirty();
        },

        move(index, delta) {
            const target = index + delta;
            if (target < 0 || target >= this.blocks.length) return;

            const [block] = this.blocks.splice(index, 1);
            this.blocks.splice(target, 0, block);
            this.markDirty();
        },

        remove(index) {
            const block = this.blocks[index];
            if (!block || block.locked) return;
            if (block.id && !window.confirm('Supprimer ce bloc ? La suppression sera appliquée à l\'enregistrement.')) return;

            this.blocks.splice(index, 1);
            if (this.selectedUid === block.uid) this.selectedUid = null;
            this.markDirty();
        },

        dragStart(index, event) {
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(index));
        },

        dragOver(index) {
            if (this.paletteType || this.dragIndex === null || this.dragIndex === index) return;

            const [block] = this.blocks.splice(this.dragIndex, 1);
            this.blocks.splice(index, 0, block);
            this.dragIndex = index;
            this.markDirty();
        },

        drop(index) {
            if (this.paletteType) {
                this.add(this.paletteType, index + 1);
                this.paletteType = null;
            }
            this.dragIndex = null;
        },

        paletteDrag(type, event) {
            this.paletteType = type;
            event.dataTransfer.effectAllowed = 'copy';
            event.dataTransfer.setData('text/plain', type);
        },

        dropFromPalette() {
            if (!this.paletteType) return;
            this.add(this.paletteType, this.blocks.length);
            this.paletteType = null;
        },

        fieldError(key) {
            return this.errors[this.selectedUid]?.[key]?.[0] ?? '';
        },

        blockError(uid) {
            const errors = this.errors[uid];
            return errors ? (Object.values(errors)[0]?.[0] ?? '') : '';
        },

        async save() {
            if (this.saving) return;

            this.saving = true;
            this.errors = {};
            this.setStatus('Enregistrement…', 'info');
            const uids = this.blocks.map((block) => block.uid);

            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({ blocks: buildPayload(this.blocks) }),
                });
                const body = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.errors = mapErrors(body.errors ?? {}, uids);
                    const first = Object.keys(this.errors).find((key) => key !== '_global');
                    if (first) this.selectedUid = first;
                    this.setStatus(this.errors._global?.[0] ?? 'Certains blocs sont invalides. Rien n\'a été enregistré.', 'error');
                } else if (response.status === 419) {
                    this.setStatus('Session expirée : rechargez la page (vos modifications ne sont pas enregistrées).', 'error');
                } else if (response.status === 403) {
                    this.setStatus('Accès refusé.', 'error');
                } else if (!response.ok) {
                    this.setStatus(`Erreur ${response.status} : rien n'a été enregistré.`, 'error');
                } else {
                    this.applySaved(body.blocks ?? []);
                }
            } catch {
                this.setStatus('Échec réseau : rien n\'a été enregistré.', 'error');
            } finally {
                this.saving = false;
            }
        },

        /** Relie les ids serveur par position ; recharge si la page a divergé (autre onglet). */
        applySaved(saved) {
            const aligned = saved.length === this.blocks.length
                && saved.every((item, index) => item.type === this.blocks[index].type);

            this.dirty = false;

            if (!aligned) {
                window.location.reload();
                return;
            }

            saved.forEach((item, index) => { this.blocks[index].id = item.id; });
            this.setStatus('Enregistré.', 'ok');
        },
    };
}

if (typeof document !== 'undefined') {
    const mount = document.querySelector('[data-page-builder]');
    const template = document.getElementById('page-builder-template');

    if (mount && template && window.Alpine) {
        // Alpine est deja demarre (app.js) : on enregistre le composant, puis on
        // insere le markup, que Alpine initialise a l'ajout au DOM.
        window.Alpine.data('pageBuilder', pageBuilder);
        mount.replaceChildren(template.content.cloneNode(true));
    }
}
