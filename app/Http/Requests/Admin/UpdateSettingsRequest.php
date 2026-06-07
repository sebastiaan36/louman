<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mail_order_notification' => ['nullable', 'email', 'max:255'],
            'mail_registration_notification' => ['nullable', 'email', 'max:255'],
            'mail_cancellation_notification' => ['nullable', 'email', 'max:255'],
            'mail_cc' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Get custom error messages for the validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_order_notification.email' => 'Voer een geldig e-mailadres in voor de bestelnotificatie.',
            'mail_registration_notification.email' => 'Voer een geldig e-mailadres in voor de registratienotificatie.',
            'mail_cancellation_notification.email' => 'Voer een geldig e-mailadres in voor de annuleringsnotificatie.',
            'mail_cc.email' => 'Voer een geldig CC e-mailadres in.',
        ];
    }
}
