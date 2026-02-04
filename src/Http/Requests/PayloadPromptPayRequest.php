<?php

namespace Mortogo321\LaravelThaiPromptPay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayloadPromptPayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => 'required|string|max:20',
            'amount' => 'nullable|numeric|min:0|max:999999999.99',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'A PromptPay identifier is required.',
            'identifier.max' => 'The identifier must not exceed 20 characters.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount cannot be negative.',
            'amount.max' => 'Amount exceeds maximum (999,999,999.99).',
        ];
    }
}
