<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'rental_id' => [
                'required',
                Rule::exists('rentals', 'id')->where('user_id', $userId),
            ],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'payment_date' => ['nullable', 'date'],
            'method' => ['required', Rule::in(['cash', 'transfer', 'card', 'check'])],
            'type' => ['nullable', Rule::in(['income', 'deposit'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rental_id.required' => 'Debes especificar el contrato de renta al que corresponde el pago.',
            'rental_id.exists' => 'El contrato de renta no existe o no te pertenece.',
            'amount_cents.required' => 'El monto del pago es obligatorio.',
            'amount_cents.min' => 'El monto debe ser mayor a 0 centavos.',
            'method.required' => 'Debes seleccionar un método de pago (efectivo, transferencia, tarjeta o cheque).',
        ];
    }
}
