<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSizeKb = config('rentame.storage.max_image_size_kb', 5120);

        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                "max:{$maxSizeKb}",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Debes adjuntar un archivo de imagen.',
            'photo.image' => 'El archivo debe ser una imagen válida (jpeg, png, webp).',
            'photo.max' => 'La imagen no debe superar los 5MB.',
        ];
    }
}
