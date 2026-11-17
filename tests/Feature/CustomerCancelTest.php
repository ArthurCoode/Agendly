<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CustomerCancelTest extends TestCase
{
    use RefreshDatabase;

    private Professional $professional;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-18 08:00')); // quarta-feira

        $this->service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 40]);
        $this->professional = Professional::create(['name' => 'João']);
        $this->professional->services()->attach($this->service);
        $this->professional->workingHours()->create(['weekday' => 3, 'start_time' => '09:00', 'end_time' => '12:00']);
    }

    private function appointmentAt(string $start, AppointmentStatus $status = AppointmentStatus::Confirmed): Appointment
    {
        return Appointment::create([
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'customer_name' => 'Maria',
            'customer_phone' => '77999999999',
            'starts_at' => Carbon::parse($start),
            'ends_at' => Carbon::parse($start)->addMinutes(30),
            'status' => $status,
        ]);
    }

    public function test_cliente_cancela_pelo_link_e_o_horario_volta_a_ficar_livre(): void
    {
        $appointment = $this->appointmentAt('2026-11-18 11:00');

        $this->get(route('booking.show', $appointment->token))->assertSee('Cancelar agendamento');

        $this->post(route('booking.cancel', $appointment->token))
            ->assertRedirect(route('booking.show', $appointment->token))
            ->assertSessionHas('status');

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);

        $slots = app(AvailabilityService::class)
            ->slotsFor($this->professional, $this->service, Carbon::parse('2026-11-18'))
            ->map(fn (Carbon $slot) => $slot->format('H:i'));

        $this->assertContains('11:00', $slots);
    }

    public function test_nao_cancela_em_cima_da_hora(): void
    {
        $appointment = $this->appointmentAt('2026-11-18 09:30'); // 1h30 de antecedência

        $this->get(route('booking.show', $appointment->token))->assertDontSee('Cancelar agendamento');

        $this->post(route('booking.cancel', $appointment->token))->assertSessionHasErrors('cancel');

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
    }

    public function test_nao_cancela_agendamento_ja_finalizado(): void
    {
        $appointment = $this->appointmentAt('2026-11-18 11:00', AppointmentStatus::Cancelled);

        $this->get(route('booking.show', $appointment->token))
            ->assertSee('Agendamento cancelado')
            ->assertDontSee('Cancelar agendamento');

        $this->post(route('booking.cancel', $appointment->token))->assertSessionHasErrors('cancel');
    }

    public function test_token_invalido_retorna_404(): void
    {
        $this->post(route('booking.cancel', 'token-invalido'))->assertNotFound();
    }
}
