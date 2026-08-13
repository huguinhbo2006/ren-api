<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'category_id' => [
                'nullable',
                Rule::exists('asset_categories', 'id')->where('user_id', $userId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'daily_rate_cents' => ['sometimes', 'required', 'integer', 'min:0'],
            'weekly_rate_cents' => ['nullable', 'integer', 'min:0'],
            'monthly_rate_cents' => ['nullable', 'integer', 'min:0'],
            'deposit_cents' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['available', 'rented', 'maintenance', 'inactive'])],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
        ];
    }
}
