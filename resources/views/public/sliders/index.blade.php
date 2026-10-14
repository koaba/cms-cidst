<x-layout>
    <div class="grid gap-6 p-6">
        @foreach($sliders as $slider)
            <div class="relative rounded-lg overflow-hidden shadow-md">
                @if($slider->link_url)
                    <a href="{{ $slider->link_url }}">
                @endif

                @php $sliderMedia = $slider->media->first(); @endphp
@if($sliderMedia)
    <x-media-img :media="$sliderMedia" :alt="$slider->title" sizes="100vw" class="w-full h-64 object-cover" />
@else
    <img src="{{ Storage::url($slider->image) }}" alt="{{ $slider->title }}" loading="lazy" decoding="async" class="w-full h-64 object-cover">
@endif

                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-4">
                    <h2 class="text-xl font-bold">{{ $slider->title }}</h2>
                    @if($slider->subtitle)
                        <p>{{ $slider->subtitle }}</p>
                    @endif
                </div>

                @if($slider->link_url)
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</x-layout>