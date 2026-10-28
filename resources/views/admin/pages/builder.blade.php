<x-admin.layout title="Page Builder">
    @vite(['resources/js/admin/page-builder.js'])

    <div class="mb-4">
        <a href="{{ route('admin.pages.edit', $page) }}" class="text-sm text-cidst-muted hover:underline">&larr; Retour à la page</a>
        <h1 class="mt-1 font-display text-2xl font-bold text-cidst-ink">Page Builder</h1>
        <p class="text-sm text-cidst-muted">{{ $page->title }}</p>
    </div>

    <div data-page-builder></div>

    <noscript>
        <p class="rounded-lg border border-cidst-border bg-white p-4 text-sm">
            Le Page Builder nécessite JavaScript.
            <a href="{{ $builder['classicUrl'] }}" class="font-medium text-cidst-red underline">Utiliser l'éditeur classique</a>
        </p>
    </noscript>

    <template id="page-builder-template">
        <div x-data="pageBuilder" data-config="{{ json_encode($builder) }}"
             x-on:keydown.window.ctrl.s.prevent="save()" x-on:keydown.window.meta.s.prevent="save()">

            {{-- Barre d'actions --}}
            <div class="sticky top-16 z-30 mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-cidst-border bg-white/95 p-3 shadow-sm backdrop-blur">
                <a href="{{ $builder['classicUrl'] }}" class="rounded-lg border border-cidst-border px-3 py-2 text-sm font-medium text-cidst-ink hover:border-cidst-red hover:text-cidst-red">Éditeur classique</a>
                <p role="status" aria-live="polite" class="min-w-0 flex-1 text-sm"
                   x-bind:class="statusKind === 'ok' ? 'text-green-700' : (statusKind === 'error' ? 'text-red-600' : 'text-cidst-muted')"
                   x-text="status || (dirty ? 'Modifications non enregistrées' : '')"></p>
                <button type="button"
                        class="rounded-lg bg-cidst-red px-4 py-2 text-sm font-semibold text-white hover:bg-cidst-red/90 focus:outline-none focus:ring-2 focus:ring-cidst-red/40 disabled:cursor-not-allowed disabled:opacity-50"
                        x-bind:disabled="saving || !dirty" x-on:click="save()">
                    <span x-text="saving ? 'Enregistrement…' : 'Enregistrer'"></span>
                </button>
            </div>

            <div class="grid items-start gap-4 lg:grid-cols-[13rem_minmax(0,1fr)_21rem]">

                {{-- Palette --}}
                <aside aria-label="Blocs disponibles" class="rounded-xl border border-cidst-border bg-white p-3 lg:sticky lg:top-36">
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-cidst-muted">Ajouter un bloc</h2>
                    <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
                        <template x-for="item in palette" x-bind:key="item.type">
                            <button type="button" draggable="true"
                                    class="shrink-0 rounded-lg border border-cidst-border bg-cidst-bg px-3 py-2 text-left text-sm font-medium text-cidst-ink hover:border-cidst-red hover:text-cidst-red focus:outline-none focus:ring-2 focus:ring-cidst-red/40"
                                    x-on:click="add(item.type)"
                                    x-on:dragstart="paletteDrag(item.type, $event)"
                                    x-on:dragend="paletteType = null"
                                    x-text="'+ ' + item.label"></button>
                        </template>
                    </div>
                </aside>

                {{-- Canevas --}}
                <section aria-label="Contenu de la page" class="min-h-48 space-y-2 pb-24 lg:pb-0"
                         x-on:dragover.prevent x-on:drop.prevent="dropFromPalette()">
                    <template x-if="blocks.length === 0">
                        <p class="rounded-xl border-2 border-dashed border-cidst-border bg-white p-8 text-center text-sm text-cidst-muted">
                            Cette page est vide. Cliquez sur un bloc de la palette, ou glissez-le ici.
                        </p>
                    </template>

                    <template x-for="(block, index) in blocks" x-bind:key="block.uid">
                        <div role="button" tabindex="0" draggable="true"
                             class="flex items-start gap-3 rounded-xl border p-3 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-cidst-red/40"
                             x-bind:class="(selectedUid === block.uid ? 'border-cidst-red ring-2 ring-cidst-red/30' : 'border-cidst-border') + ' ' + (block.locked ? 'bg-cidst-bg' : 'bg-white') + ' ' + (dragIndex === index ? 'opacity-50' : 'cursor-grab')"
                             x-bind:aria-pressed="selectedUid === block.uid"
                             x-on:click="select(block.uid)"
                             x-on:keydown.enter.self.prevent="select(block.uid)"
                             x-on:keydown.space.self.prevent="select(block.uid)"
                             x-on:dragstart="dragStart(index, $event)"
                             x-on:dragover.prevent="dragOver(index)"
                             x-on:drop.prevent.stop="drop(index)"
                             x-on:dragend="dragIndex = null">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-cidst-ink" x-text="block.label"></span>
                                    <span x-show="block.locked" class="rounded bg-cidst-ink/10 px-2 py-0.5 text-xs text-cidst-muted">Verrouillé</span>
                                    <span x-show="block.id === null" class="rounded bg-cidst-red/10 px-2 py-0.5 text-xs text-cidst-red">Nouveau</span>
                                </div>
                                <p class="mt-1 truncate text-sm text-cidst-muted" x-text="summary(block)"></p>
                                <p x-show="blockError(block.uid)" class="mt-1 text-xs font-medium text-red-600" x-text="blockError(block.uid)"></p>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <button type="button" class="rounded p-1.5 text-cidst-muted hover:bg-cidst-bg hover:text-cidst-ink disabled:opacity-30"
                                        aria-label="Monter le bloc" x-bind:disabled="index === 0" x-on:click.stop="move(index, -1)">&uarr;</button>
                                <button type="button" class="rounded p-1.5 text-cidst-muted hover:bg-cidst-bg hover:text-cidst-ink disabled:opacity-30"
                                        aria-label="Descendre le bloc" x-bind:disabled="index === blocks.length - 1" x-on:click.stop="move(index, 1)">&darr;</button>
                                <button type="button" x-show="!block.locked" class="rounded p-1.5 text-red-600 hover:bg-red-50"
                                        aria-label="Supprimer le bloc" x-on:click.stop="remove(index)">&times;</button>
                            </div>
                        </div>
                    </template>
                </section>

                {{-- Inspecteur : colonne à droite sur grand écran, tiroir en bas sur mobile --}}
                <aside aria-label="Propriétés du bloc"
                       class="fixed inset-x-0 bottom-0 z-40 max-h-[75vh] overflow-y-auto rounded-t-2xl border border-cidst-border bg-white p-4 shadow-2xl lg:sticky lg:top-36 lg:z-auto lg:max-h-[calc(100vh-10rem)] lg:rounded-xl lg:shadow-sm"
                       x-bind:class="current ? '' : 'hidden lg:block'">

                    <p x-show="!current" class="text-sm text-cidst-muted">Sélectionnez un bloc pour modifier ses propriétés.</p>

                    <template x-if="current">
                        <div class="space-y-4" x-on:input="markDirty()" x-on:change="markDirty()">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="font-display text-lg font-semibold text-cidst-ink" x-text="current.label"></h2>
                                <button type="button" class="rounded p-1.5 text-cidst-muted hover:bg-cidst-bg lg:hidden" aria-label="Fermer" x-on:click="close()">&times;</button>
                            </div>

                            <template x-if="current.locked">
                                <div class="space-y-3 text-sm text-cidst-muted">
                                    <p>Ce bloc est géré par l'éditeur classique. Le Page Builder le conserve tel quel, avec son contenu imbriqué : vous pouvez seulement le déplacer.</p>
                                    <a x-bind:href="current.editUrl" class="inline-block rounded-lg border border-cidst-border px-3 py-2 font-medium text-cidst-ink hover:border-cidst-red hover:text-cidst-red">Modifier dans l'éditeur classique</a>
                                </div>
                            </template>

                            <p x-show="!current.locked && types[current.type]?.note" class="rounded-lg bg-cidst-bg p-2 text-xs text-cidst-muted" x-text="types[current.type]?.note"></p>

                            <template x-for="field in fields" x-bind:key="current.uid + ':' + field.key">
                                <div class="space-y-1">
                                    <label x-show="field.type !== 'checkbox'" class="block text-sm font-medium text-cidst-ink" x-bind:for="'field-' + field.key">
                                        <span x-text="field.label"></span><span x-show="field.required" class="text-cidst-red"> *</span>
                                    </label>

                                    <template x-if="field.type === 'text'">
                                        <input type="text" x-bind:id="'field-' + field.key" x-bind:maxlength="field.max" x-model="current.data[field.key]"
                                               class="w-full rounded-lg border-cidst-border text-sm focus:border-cidst-red focus:ring-cidst-red"
                                               x-bind:class="fieldError(field.key) ? 'border-red-500' : ''">
                                    </template>

                                    <template x-if="field.type === 'textarea'">
                                        <textarea rows="7" x-bind:id="'field-' + field.key" x-model="current.data[field.key]"
                                                  class="w-full rounded-lg border-cidst-border text-sm focus:border-cidst-red focus:ring-cidst-red"
                                                  x-bind:class="fieldError(field.key) ? 'border-red-500' : ''"></textarea>
                                    </template>

                                    <template x-if="field.type === 'number'">
                                        <input type="number" x-bind:id="'field-' + field.key" x-bind:min="field.min" x-bind:max="field.max" x-model.number="current.data[field.key]"
                                               class="w-full rounded-lg border-cidst-border text-sm focus:border-cidst-red focus:ring-cidst-red"
                                               x-bind:class="fieldError(field.key) ? 'border-red-500' : ''">
                                    </template>

                                    <template x-if="field.type === 'select'">
                                        <select x-bind:id="'field-' + field.key" x-model="current.data[field.key]"
                                                class="w-full rounded-lg border-cidst-border text-sm focus:border-cidst-red focus:ring-cidst-red">
                                            <template x-for="option in field.options" x-bind:key="option.value">
                                                <option x-bind:value="option.value" x-text="option.label"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="field.type === 'checkbox'">
                                        <label class="flex items-center gap-2 text-sm text-cidst-ink">
                                            <input type="checkbox" x-model="current.data[field.key]" class="rounded border-cidst-border text-cidst-red focus:ring-cidst-red">
                                            <span x-text="field.label"></span>
                                        </label>
                                    </template>

                                    <p x-show="field.help" class="text-xs text-cidst-muted" x-text="field.help"></p>
                                    <p x-show="fieldError(field.key)" class="text-xs font-medium text-red-600" x-text="fieldError(field.key)"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </aside>
            </div>
        </div>
    </template>
</x-admin.layout>
