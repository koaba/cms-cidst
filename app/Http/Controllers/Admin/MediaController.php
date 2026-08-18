<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;


class MediaController extends Controller
{
    private function search(Request $request)
    {
        $term = trim($request->query('q', ''));

       return Media::query()
    ->with('mediables')
    ->when($term !== '', fn ($query) => $query->where('original_name', 'like', "%{$term}%"))
    ->latest();
    }
   public function index(Request $request)
    {
        return view('admin.media.index', [
            'media' => $this->search($request)->paginate(24)->withQueryString(),
            'search' => trim($request->query('q', '')),
        ]);
    }

    public function picker(Request $request)
    {
        $media = $this->search($request)
            ->paginate(30)
            ->through(fn (Media $item) => [
                'id' => $item->id,
                'url' => $item->thumbnail_url,
                'name' => $item->original_name,
            ]);

        return response()->json($media);
    }
}