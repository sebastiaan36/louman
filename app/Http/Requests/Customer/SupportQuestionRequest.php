<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SupportQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->customer !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'question' => ['required', 'string', 'min:5', 'max:2000'],
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
            'email.required' => 'Vul het e-mailadres in waarop u antwoord wilt.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'question.required' => 'Vul uw vraag in.',
            'question.min' => 'Uw vraag is wel erg kort. Schrijf iets meer, dan kunnen we u beter helpen.',
            'question.max' => 'Uw vraag mag maximaal 2000 tekens bevatten.',
        ];
    }
}
