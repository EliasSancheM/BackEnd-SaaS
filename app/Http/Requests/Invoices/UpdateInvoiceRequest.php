<?php

namespace App\Http\Requests\Invoices;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
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
        $tenantId = $this->user()->tenant_id;
        $invoiceId = $this->route('invoice')?->id;

        return [
            'client_id' => [
                'sometimes', 'integer',
                Rule::exists('clients', 'id')->where('tenant_id', $tenantId),
            ],
            'number' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('invoices', 'number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($invoiceId),
            ],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax_total' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
