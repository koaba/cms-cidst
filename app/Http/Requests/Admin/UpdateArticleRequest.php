<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesArticleMedia;
use App\Models\Article;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    use ValidatesArticleMedia;

    public function authorize(): bool
    {
        // Même remarque que StoreArticleRequest : le filtrage par rôle est
        // déjà assuré par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        return $this->articleRules($this->article());
    }

    public function withValidator(Validator $validator): void
    {
        $this->withArticleValidation($validator, $this->article());
    }

    /**
     * Résout l'article ciblé par la route (route model binding), pour les
     * règles et validations qui doivent tenir compte de son état actuel
     * (ex. quotas déjà atteints, médias déjà possédés).
     */
    private function article(): Article
    {
        return $this->route('article');
    }
}