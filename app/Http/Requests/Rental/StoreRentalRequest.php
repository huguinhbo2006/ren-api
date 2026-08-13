<?php

namespace App\Http\Requests\Rental;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('user_id', $userId),
            ],
            'asset_id' => [
                'required',
                Rule::exists('assets', 'id')->where('user_id', $userId),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'deposit_cents' => ['nullable', 'integer', 'min:0'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'extras' => ['nullable', 'array'],
            'extras.*.extra_service_id' => [
                'required_with:extras',
                Rule::exists('extra_services', 'id')->where('user_id', $userId),
            ],
            'extras.*.quantity' => ['required_with:extras', 'integer', 'min:1'],
            'extras.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Debes seleccionar un cliente.',
            'customer_id.exists' => 'El cliente seleccionado no existe o no te pertenece.',
            'asset_id.required' => 'Debes seleccionar un activo para la renta.',
            'asset_id.exists' => 'El activo seleccionado no existe o no te pertenece.',
            'start_date.required' => 'La fecha de inicio de la renta es obligatoria.',
            'end_date.required' => 'La fecha de fin de la renta es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
