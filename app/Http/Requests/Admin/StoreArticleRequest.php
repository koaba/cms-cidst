<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesArticleMedia;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    use ValidatesArticleMedia;

    public function authorize(): bool
    {
        // L'accès à cette route est déjà filtré en amont par le middleware
        // role:Super Admin|Publication (voir routes/web.php). Aucune
        // vérification supplémentaire nécessaire ici, propre à un article
        // en cours de création (il n'existe pas encore de propriétaire à
        // comparer).
        return true;
    }

    public function rules(): array
    {
        return $this->articleRules(article: null);
    }

    public function withValidator(Validator $validator): void
    {
        $this->withArticleValidation($validator, article: null);
    }
}