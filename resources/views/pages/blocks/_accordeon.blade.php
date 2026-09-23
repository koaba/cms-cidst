@php($items = $block->children)

<div>
    @if(!empty($data['title']))
        <h2 class="text-xl font-semibold mb-4">{{ $data['title'] }}</h2>
    @endif

    <style>
        #accordeon-{{ $block->id }} .accordeon-content {
            display: none;
            padding: 1rem;
        }
        #accordeon-{{ $block->id }} .accordeon-item.is-open .accordeon-content {
            display: block;
        }
        #accordeon-{{ $block->id }} .accordeon-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            font-weight: 600;
        }
        #accordeon-{{ $block->id }} .accordeon-item.is-open .accordeon-icon {
            transform: rotate(45deg);
        }
        #accordeon-{{ $block->id }} .accordeon-icon {
            transition: transform 0.2s ease;
        }
    </style>

    <div id="accordeon-{{ $block->id }}">
        @foreach($items as $item)
            <div class="accordeon-item border rounded mb-2">
                <div class="accordeon-header" onclick="this.parentElement.classList.toggle('is-open')">
                    <span>{{ $item->data['title'] }}</span>
                    <span class="accordeon-icon">+</span>
                </div>
                <div class="accordeon-content">
                    @foreach($item->children as $content)
                        @include('pages.blocks._' . $content->type, ['data' => $content->data, 'media' => $content->media, 'block' => $content])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>