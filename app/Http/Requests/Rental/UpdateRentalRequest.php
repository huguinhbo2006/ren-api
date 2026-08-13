<?php

namespace App\Http\Requests\Rental;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRentalRequest extends FormRequest
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
                'sometimes',
                'required',
                Rule::exists('customers', 'id')->where('user_id', $userId),
            ],
            'asset_id' => [
                'sometimes',
                'required',
                Rule::exists('assets', 'id')->where('user_id', $userId),
            ],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'deposit_cents' => ['nullable', 'integer', 'min:0'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'pending', 'active', 'completed', 'cancelled'])],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'partial', 'paid', 'refunded'])],
            'extras' => ['nullable', 'array'],
            'extras.*.extra_service_id' => [
                'required_with:extras',
                Rule::exists('extra_services', 'id')->where('user_id', $userId),
            ],
            'extras.*.quantity' => ['required_with:extras', 'integer', 'min:1'],
            'extras.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
