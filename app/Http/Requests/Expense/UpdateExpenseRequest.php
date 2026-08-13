<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
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
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'amount_cents' => ['sometimes', 'required', 'integer', 'min:1'],
            'expense_date' => ['nullable', 'date'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'type' => ['sometimes', 'required', Rule::in(['maintenance', 'repair', 'purchase', 'other'])],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }
}
