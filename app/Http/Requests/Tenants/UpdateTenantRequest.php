<?php

namespace App\Http\Requests\Tenants;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Las claves de `settings` coinciden (camelCase) con la interfaz
     * CompanySettings del frontend; se almacenan como blob JSON.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'settings.companyName' => ['nullable', 'string', 'max:255'],
            'settings.taxId' => ['nullable', 'string', 'max:32'],
            'settings.giro' => ['nullable', 'string', 'max:255'],
            'settings.address' => ['nullable', 'string', 'max:255'],
            'settings.city' => ['nullable', 'string', 'max:255'],
            'settings.phone' => ['nullable', 'string', 'max:50'],
            'settings.website' => ['nullable', 'string', 'max:255'],
            'settings.logoUrl' => ['nullable', 'string', 'max:2048'],
            'settings.siiResolution' => ['nullable', 'string', 'max:255'],
            'settings.siiResolutionDate' => ['nullable', 'string', 'max:32'],
            'settings.siiActivity' => ['nullable', 'string', 'max:255'],
            'settings.currency' => ['nullable', 'string', 'max:3'],
            'settings.vatRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settings.invoicePrefix' => ['nullable', 'string', 'max:16'],
            'settings.nextFolioNumber' => ['nullable', 'integer', 'min:1'],
            'settings.defaultPaymentTermsDays' => ['nullable', 'integer', 'min:0'],
            'settings.defaultNotes' => ['nullable', 'string', 'max:2000'],
            'settings.theme' => ['nullable', 'string', 'in:light,dark,system'],
            'settings.accentColor' => ['nullable', 'string', 'in:emerald,blue,violet,amber,rose'],
        ];
    }
}
