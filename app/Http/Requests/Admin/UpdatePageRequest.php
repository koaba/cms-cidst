<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesPageFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    use ValidatesPageFields;

    public function authorize(): bool
    {
        // Meme remarque que StorePageRequest : role deja filtre par la route.
        return true;
    }

    public function rules(): array
    {
        return $this->pageRules($this->route('page'));
    }
}
