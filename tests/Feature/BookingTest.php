<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Professional $professional;
    private Service $service;
    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 50,
        ]);

        $this->professional = Professional::create(['name' => 'João']);
        $this->professional->services()->attach($this->service);

        $this->professional->workingHours()->createMany([
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00'],
            ['weekday' => 1, 'start_time' => '13:00', 'end_time' => '18:00'],
        ]);

        $this->monday = Carbon::now()->next(Carbon::MONDAY)->startOfDay();
    }

    private function at(string $time): Carbon
    {
        return $this->monday->copy()->setTimeFromTimeString($time);
    }

    private function book(string $time): Appointment
    {
        return app(BookingService::class)->book(
            $this->professional,
            $this->service,
            $this->at($time),
            ['customer_name' => 'Maria', 'customer_phone' => '77999999999'],
        );
    }

    private function payload(string $time): array
    {
        return [
            'service_id' => $this->service->id,
            'professional_id' => $this->professional->id,
            'starts_at' => $this->at($time)->format('Y-m-d H:i'),
            'customer_name' => 'Maria',
            'customer_phone' => '(77) 99999-9999',
            'customer_email' => 'maria@example.com',
        ];
    }

    public function test_cria_agendamento_com_fim_calculado_pela_duracao(): void
    {
        $appointment = $this->book('10:00');

        $this->assertTrue($appointment->ends_at->equalTo($this->at('11:00')));
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertSame(40, strlen($appointment->token));
    }

    public function test_impede_agendar_horario_ja_ocupado(): void
    {
        $this->book('10:00');

        $this->assertThrows(fn () => $this->book('10:00'), SlotUnavailableException::class);
        $this->assertThrows(fn () => $this->book('10:30'), SlotUnavailableException::class);
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_permite_agendamentos_colados(): void
    {
        $this->book('10:00');
        $this->book('11:00');

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_store_cria_agendamento_e_redireciona_para_confirmacao(): void
    {
        $response = $this->post(route('booking.store'), $this->payload('10:00'));

        $appointment = Appointment::firstOrFail();
        $response->assertRedirect(route('booking.show', $appointment->token));

        // Telefone salvo só com dígitos
        $this->assertSame('77999999999', $appointment->customer_phone);
    }

    public function test_store_com_horario_ocupado_volta_com_erro(): void
    {
        $this->book('10:00');

        $this->post(route('booking.store'), $this->payload('10:00'))
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_store_valida_campos_obrigatorios(): void
    {
        $this->post(route('booking.store'), [])
            ->assertSessionHasErrors(['service_id', 'professional_id', 'starts_at', 'customer_name', 'customer_phone']);
    }

    public function test_endpoint_de_horarios_retorna_json(): void
    {
        $this->getJson(route('booking.slots', [
            'service_id' => $this->service->id,
            'professional_id' => $this->professional->id,
            'date' => $this->monday->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertJsonPath('slots.0', '09:00');
    }

    public function test_pagina_de_confirmacao_abre_pelo_token(): void
    {
        $appointment = $this->book('10:00');

        $this->get(route('booking.show', $appointment->token))
            ->assertOk()
            ->assertSee('Maria')
            ->assertSee('Corte');

        $this->get(route('booking.show', 'token-invalido'))->assertNotFound();
    }

    public function test_pagina_publica_carrega(): void
    {
        $this->get(route('booking.create'))->assertOk()->assertSee('Agende seu horário');
    }
}
