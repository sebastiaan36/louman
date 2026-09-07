<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mail_order_notification' => ['nullable', 'email', 'max:255'],
            'mail_registration_notification' => ['nullable', 'email', 'max:255'],
            'mail_registration_cc' => ['nullable', 'email', 'max:255'],
            'mail_cancellation_notification' => ['nullable', 'email', 'max:255'],
            'mail_support_notification' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'mail_cc' => ['nullable', 'email', 'max:255'],
            'mail_reply_to' => ['nullable', 'email', 'max:255'],
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
            'mail_registration_cc.email' => 'Voer een geldig CC e-mailadres in voor de registratienotificatie.',
            'mail_cancellation_notification.email' => 'Voer een geldig e-mailadres in voor de annuleringsnotificatie.',
            'mail_support_notification.email' => 'Voer een geldig e-mailadres in voor vragen van klanten.',
            'support_email.email' => 'Voer een geldig e-mailadres in voor de hulpknop.',
            'mail_cc.email' => 'Voer een geldig CC e-mailadres in.',
            'mail_reply_to.email' => 'Voer een geldig antwoord-aan e-mailadres in.',
        ];
    }
}
