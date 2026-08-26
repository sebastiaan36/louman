<?php

namespace App\Http\Requests\Api\V1;

use App\Support\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Status write-back from the external package once an order has been picked,
 * shipped or cancelled there.
 */
class UpdateOrderStatusRequest extends ApiFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(OrderStatus::STATUSES)],
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
            'status.required' => 'Geef een status op.',
            'status.in' => 'Ongeldige status. Toegestaan: '.implode(', ', OrderStatus::STATUSES).'.',
        ];
    }
}
