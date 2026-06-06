<?php

namespace App\Http\Requests\Payments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => [
                'sometimes', 'integer',
                Rule::exists('invoices', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'provider' => ['sometimes', 'string', 'in:manual,mercadopago,paypal'],
            'provider_payment_id' => ['nullable', 'string', 'max:255'],
            'paypal_order_id' => ['nullable', 'string', 'max:255'],
            'paypal_payer_id' => ['nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'raw_payload' => ['nullable', 'array'],
        ];
    }
}
