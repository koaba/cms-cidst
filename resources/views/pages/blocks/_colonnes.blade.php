@php
    $columnCount = $data['column_count'] ?? 2;
    $childrenByColumn = $block->childrenGroupedByColumn();
    $gridClass = match($columnCount) {
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-2 lg:grid-cols-3',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        5 => 'sm:grid-cols-2 lg:grid-cols-5',
        6 => 'sm:grid-cols-2 lg:grid-cols-6',
        default => 'sm:grid-cols-2',
    };
@endphp

<div class="my-8">
    @if(!empty($data['title']))
        <h2 class="text-2xl font-bold mb-6 text-center">{{ $data['title'] }}</h2>
    @endif

    <div class="grid grid-cols-1 {{ $gridClass }} gap-6">
        @for($i = 0; $i < $columnCount; $i++)
            <div class="space-y-4">
                @foreach($childrenByColumn->get($i, collect()) as $child)
                    @include('pages.blocks._' . $child->type, ['data' => $child->data, 'media' => $child->media, 'block' => $child])
                @endforeach
            </div>
        @endfor
    </div>
</div>