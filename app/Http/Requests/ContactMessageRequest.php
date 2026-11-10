<?php

namespace App\Http\Requests;

use App\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = SiteSetting::current();
        $side = (int) config('contact.max_side');
        $maxKb = max(1, (int) $settings->contact_images_max_mb) * 1024;

        $images = $settings->contact_images_enabled
            ? [
                'images' => ['nullable', 'array', 'max:'.(int) config('contact.max_files')],
                'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb, 'dimensions:max_width='.$side.',max_height='.$side],
            ]
            : ['images' => ['prohibited']];

        return $images + [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'attachment_link' => ['nullable', 'url:https', 'max:500'],
            'website' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        $mb = max(1, (int) SiteSetting::current()->contact_images_max_mb);

        return [
            'images.prohibited' => "L'envoi d'images est d\u{e9}sactiv\u{e9}.",
            'images.max' => 'Vous pouvez joindre '.(int) config('contact.max_files').' images au maximum.',
            'images.*.mimes' => "Chaque fichier doit \u{ea}tre une image JPG, PNG ou WebP.",
            'images.*.max' => "Chaque image doit peser {$mb} Mo au maximum.",
            'images.*.dimensions' => 'Chaque image doit mesurer '.(int) config('contact.max_side').' pixels de c\u{f4}t\u{e9} au maximum.',
            'images.*.file' => "Le fichier n'a pas pu \u{ea}tre envoy\u{e9}.",
        ];
    }
}
