<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Aceita "(77) 99999-9999" e guarda só os dígitos.
        $this->merge([
            'customer_phone' => preg_replace('/\D+/', '', (string) $this->input('customer_phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', Rule::exists('services', 'id')->where('active', true)],
            'professional_id' => ['required', Rule::exists('professionals', 'id')->where('active', true)],
            'starts_at' => ['required', 'date_format:Y-m-d H:i', 'after:now', 'before_or_equal:+60 days'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'digits_between:10,13'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Escolha um serviço.',
            'service_id.exists' => 'Serviço indisponível.',
            'professional_id.required' => 'Escolha um profissional.',
            'professional_id.exists' => 'Profissional indisponível.',
            'starts_at.required' => 'Escolha um horário.',
            'starts_at.date_format' => 'Horário inválido.',
            'starts_at.after' => 'Escolha um horário futuro.',
            'starts_at.before_or_equal' => 'Só aceitamos agendamentos nos próximos 60 dias.',
            'customer_name.required' => 'Informe seu nome.',
            'customer_phone.required' => 'Informe seu telefone.',
            'customer_phone.digits_between' => 'Informe um telefone válido com DDD.',
            'customer_email.email' => 'Informe um e-mail válido.',
        ];
    }
}
