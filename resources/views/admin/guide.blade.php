<x-admin.layout title="Guide">
    <h1 class="text-2xl font-bold mb-6">Guide</h1>

    <section id="guide-menus" class="mb-10" aria-labelledby="guide-menus-title">
        <h2 id="guide-menus-title" class="text-lg font-semibold mb-3">Vos menus</h2>
        <dl class="space-y-2">
            @foreach ($menus as $item)
                <div class="flex flex-col sm:flex-row sm:gap-4">
                    <dt class="font-medium sm:w-48 shrink-0"><a href="{{ route($item['route']) }}" class="link link-hover">{{ $item['label'] }}</a></dt>
                    <dd class="opacity-70">{{ $item['description'] }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section id="guide-glossaire" aria-labelledby="guide-glossaire-title">
        <h2 id="guide-glossaire-title" class="text-lg font-semibold mb-3">Glossaire</h2>
        <dl class="space-y-3">
            <div data-term="slug">
                <dt class="font-medium">Slug</dt>
                <dd class="opacity-70">Partie de l'adresse web d'une page ou d'un article, en minuscules sans accents, par exemple <code>mon-article</code>.</dd>
            </div>
            <div data-term="brouillon">
                <dt class="font-medium">Brouillon</dt>
                <dd class="opacity-70">Contenu enregistr&eacute; mais invisible pour les visiteurs, tant qu'il n'est pas publi&eacute;.</dd>
            </div>
            <div data-term="programmee">
                <dt class="font-medium">Programm&eacute;e</dt>
                <dd class="opacity-70">Page publi&eacute;e avec une date future : elle appara&icirc;tra automatiquement &agrave; cette date.</dd>
            </div>
            <div data-term="publiee">
                <dt class="font-medium">Publi&eacute;e</dt>
                <dd class="opacity-70">Contenu visible par tous les visiteurs du site.</dd>
            </div>
        </dl>
    </section>
</x-admin.layout>
