<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole('Super Admin|Publication');
    }

    public function rules(): array
    {
        return [
            'mediable_type' => ['required', 'string', Rule::in(['article', 'diaporama'])],
            'mediable_id' => [
                'required',
                'integer',
                'exists:' . $this->input('mediable_type') . 's,id'
            ],
            'ordered_ids' => ['required', 'array', 'min:2'],
            'ordered_ids.*' => ['required', 'integer', 'exists:media,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'mediable_type.in' => 'Le type de média n\'est pas valide.',
            'ordered_ids.min' => 'Il faut au moins 2 médias à réorganiser.',
            'ordered_ids.*.exists' => 'Un ou plusieurs médias n\'existent pas.',
        ];
    }

    public function resolveMediableModel(): \Illuminate\Database\Eloquent\Model
    {
        $type = $this->input('mediable_type');
        $id = $this->input('mediable_id');

        $class = match ($type) {
            'article' => \App\Models\Article::class,
            'diaporama' => \App\Models\Diaporama::class,
            default => throw new \InvalidArgumentException("Type non supporté : {$type}"),
        };

        return $class::findOrFail($id);
    }
}