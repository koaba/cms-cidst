<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Slider;
use App\Traits\HasOrphanMediaCleanup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SliderController extends Controller
{
    use HasOrphanMediaCleanup;

    private const MAX_SLIDERS = 5;

    public function index()
    {
        $sliders = Slider::orderBy('order')->get();
        return view('admin.sliders.index', compact('sliders'));
    }

    public function create()
    {
        return view('admin.sliders.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateSlider($request);

        if (Slider::count() >= self::MAX_SLIDERS) {
            return back()
                ->withInput()
                ->withErrors(['image' => 'Le nombre maximum de sliders (' . self::MAX_SLIDERS . ') est déjà atteint. Supprimez-en un avant d\'en créer un nouveau.']);
        }

        [$path, $media] = $this->resolveImage($request);

        $validated['image'] = $path;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        $slider = Slider::create($validated);
        $slider->media()->attach($media->id, ['order' => 0]);

        return redirect()->route('admin.sliders.index')->with('success', 'Slider créé.');
    }

    public function edit(Slider $slider)
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $validated = $this->validateSlider($request, isUpdate: true);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        if ($request->hasFile('image') || $request->filled('existing_media_id')) {
            $this->detachAndPruneOrphanMedia($slider);

            if ($slider->image && Storage::disk('public')->exists($slider->image)) {
                Storage::disk('public')->delete($slider->image);
            }

            [$path, $media] = $this->resolveImage($request);

            $validated['image'] = $path;
            $slider->media()->attach($media->id, ['order' => 0]);
        }

        $slider->update($validated);

        return redirect()->route('admin.sliders.index')->with('success', 'Slider mis à jour.');
    }

    public function destroy(Slider $slider)
    {
        $this->detachAndPruneOrphanMedia($slider);

        if ($slider->image && Storage::disk('public')->exists($slider->image)) {
            Storage::disk('public')->delete($slider->image);
        }

        $slider->delete();

        return redirect()->route('admin.sliders.index')->with('success', 'Slider supprimé.');
    }

    public function show(Slider $slider)
    {
        abort(404);
    }

    /* ------------------------------------------------------------------ */

    private function validateSlider(Request $request, bool $isUpdate = false): array
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'existing_media_id' => 'nullable|integer|exists:media,id',
            'link_url' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $validator->after(function ($validator) use ($request, $isUpdate) {
            $hasNewImage = $request->hasFile('image') || $request->filled('existing_media_id');
            if (!$isUpdate && !$hasNewImage) {
                $validator->errors()->add('image', 'Une image est requise : uploadez un fichier ou choisissez-en une depuis la médiathèque.');
            }
        });

        return $validator->validate();
    }

    /**
     * Retourne [path, Media] selon que l'image vient d'un upload direct
     * ou d'une sélection existante dans la médiathèque.
     */
   private function resolveImage(Request $request): array
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('sliders', 'public');

            $media = Media::create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            return [$media->path, $media];
        }

        $media = Media::findOrFail($request->input('existing_media_id'));

        return [$media->path, $media];
    }
     }