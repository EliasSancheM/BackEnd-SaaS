<?php

namespace App\Http\Requests\Payments;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => [
                'required', 'integer',
                Rule::exists('invoices', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'provider' => ['sometimes', 'string', 'in:manual,mercadopago,paypal'],
            'provider_payment_id' => ['nullable', 'string', 'max:255'],
            'paypal_order_id' => ['nullable', 'string', 'max:255'],
            'paypal_payer_id' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'raw_payload' => ['nullable', 'array'],
        ];
    }
}
