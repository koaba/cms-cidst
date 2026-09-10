<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMediaOrderRequest;
use Illuminate\Http\JsonResponse;

class MediaOrderController extends Controller
{
    public function update(UpdateMediaOrderRequest $request): JsonResponse
    {
        $model = $request->resolveMediableModel();

        $model->reorderMedia($request->input('ordered_ids'));

        return response()->json([
            'status' => 'ok',
            'message' => 'Ordre des médias mis à jour avec succès',
        ]);
    }
}