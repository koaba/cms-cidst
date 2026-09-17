@php($childrenByColumn = $block->childrenGroupedByColumn())

<div>
    @if(!empty($data['title']))
        <h2 class="text-xl font-semibold mb-4">{{ $data['title'] }}</h2>
    @endif

    <style>
        #colonnes-{{ $block->id }} { grid-template-columns: repeat({{ $data['column_count'] }}, minmax(0, 1fr)); }
        @media (max-width: 768px) {
            #colonnes-{{ $block->id }} { grid-template-columns: 1fr; }
        }
    </style>

    <div id="colonnes-{{ $block->id }}" class="grid gap-6">
        @for($i = 0; $i < $data['column_count']; $i++)
            <div class="space-y-6">
                @foreach($childrenByColumn->get($i, collect()) as $child)
                    @include('pages.blocks._' . $child->type, ['data' => $child->data, 'media' => $child->media, 'block' => $child])
                @endforeach
            </div>
        @endfor
    </div>
</div>