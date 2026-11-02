<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesPageFields;
use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    use ValidatesPageFields;

    public function authorize(): bool
    {
        // Le filtrage par role est deja assure par le middleware de route.
        return true;
    }

    public function rules(): array
    {
        return $this->pageRules(page: null);
    }
}
