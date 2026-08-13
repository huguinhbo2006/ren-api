<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'asset_id' => [
                'nullable',
                Rule::exists('assets', 'id')->where('user_id', $userId),
            ],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'expense_date' => ['nullable', 'date'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'type' => ['required', Rule::in(['maintenance', 'repair', 'purchase', 'other'])],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'La categoría del gasto es obligatoria.',
            'description.required' => 'La descripción del egreso es obligatoria.',
            'amount_cents.required' => 'El monto del gasto es obligatorio.',
            'amount_cents.min' => 'El monto debe ser mayor a 0 centavos.',
            'type.required' => 'Debes indicar el tipo de egreso (mantenimiento, reparación, adquisición u otro).',
            'type.in' => 'El tipo de egreso no es válido.',
            'receipt.max' => 'El archivo de comprobante no debe superar los 5MB.',
        ];
    }
}
