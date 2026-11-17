<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-18 12:00'));
        $this->actingAs(User::factory()->create());
    }

    private function makeAppointment(string $start, AppointmentStatus $status = AppointmentStatus::Confirmed): Appointment
    {
        $service = Service::firstOrCreate(['name' => 'Corte'], ['duration_minutes' => 30, 'price' => 40]);
        $professional = Professional::firstOrCreate(['name' => 'João']);

        return Appointment::create([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => 'Maria '.$start,
            'customer_phone' => '77999999999',
            'starts_at' => Carbon::parse($start),
            'ends_at' => Carbon::parse($start)->addMinutes(30),
            'status' => $status,
        ]);
    }

    public function test_agenda_abre_e_aba_hoje_mostra_so_os_de_hoje(): void
    {
        $today = $this->makeAppointment('2026-11-18 15:00');
        $tomorrow = $this->makeAppointment('2026-11-19 10:00');

        $this->get('/admin/appointments')->assertOk();

        Livewire::test(ListAppointments::class)
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$tomorrow]);
    }

    public function test_painel_nao_permite_criar_editar_nem_excluir(): void
    {
        $appointment = $this->makeAppointment('2026-11-18 15:00');
        $user = auth()->user();

        $this->assertFalse($user->can('create', Appointment::class));
        $this->assertFalse($user->can('update', $appointment));
        $this->assertFalse($user->can('delete', $appointment));
    }

    public function test_confirma_agendamento_pendente(): void
    {
        $appointment = $this->makeAppointment('2026-11-18 15:00', AppointmentStatus::Pending);

        Livewire::test(ListAppointments::class)
            ->callTableAction('confirm', $appointment);

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
    }

    public function test_cancelar_libera_o_horario(): void
    {
        $appointment = $this->makeAppointment('2026-11-18 15:00');

        Livewire::test(ListAppointments::class)
            ->callTableAction('cancel', $appointment);

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertFalse(Appointment::blocking()->whereKey($appointment->id)->exists());
    }

    public function test_falta_e_conclusao_so_depois_do_horario_comecar(): void
    {
        $future = $this->makeAppointment('2026-11-18 15:00');
        $past = $this->makeAppointment('2026-11-18 09:00');

        Livewire::test(ListAppointments::class)
            ->assertTableActionHidden('no_show', $future)
            ->assertTableActionHidden('complete', $future)
            ->assertTableActionVisible('no_show', $past)
            ->callTableAction('complete', $past);

        $this->assertSame(AppointmentStatus::Completed, $past->fresh()->status);
    }

    public function test_status_finais_nao_mudam(): void
    {
        $cancelled = $this->makeAppointment('2026-11-18 15:00', AppointmentStatus::Cancelled);

        foreach (AppointmentStatus::cases() as $status) {
            $this->assertFalse($cancelled->canTransitionTo($status));
        }

        $this->expectException(\DomainException::class);
        $cancelled->transitionTo(AppointmentStatus::Confirmed);
    }
}
