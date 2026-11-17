<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\TimeOffs\Pages\CreateTimeOff;
use App\Filament\Resources\TimeOffs\Pages\ListTimeOffs;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\TimeOff;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class TimeOffAdminTest extends TestCase
{
    use RefreshDatabase;

    private Professional $professional;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-18 08:00')); // quarta-feira
        $this->actingAs(User::factory()->create());

        $this->service = Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 40]);
        $this->professional = Professional::create(['name' => 'João']);
        $this->professional->services()->attach($this->service);
        $this->professional->workingHours()->create(['weekday' => 3, 'start_time' => '09:00', 'end_time' => '12:00']);
    }

    private function appointmentAt(string $start, AppointmentStatus $status): Appointment
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

    public function test_telas_de_folga_abrem(): void
    {
        $timeOff = TimeOff::create([
            'professional_id' => $this->professional->id,
            'starts_at' => '2026-11-20 09:00',
            'ends_at' => '2026-11-20 18:00',
        ]);

        foreach (['/admin/time-offs', '/admin/time-offs/create', "/admin/time-offs/{$timeOff->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_folga_criada_pelo_painel_remove_horarios_da_pagina_publica(): void
    {
        Livewire::test(CreateTimeOff::class)
            ->fillForm([
                'professional_id' => $this->professional->id,
                'starts_at' => '2026-11-18 10:00',
                'ends_at' => '2026-11-18 11:00',
                'reason' => 'Consulta médica',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $slots = app(AvailabilityService::class)
            ->slotsFor($this->professional, $this->service, Carbon::parse('2026-11-18'))
            ->map(fn (Carbon $slot) => $slot->format('H:i'))
            ->all();

        $this->assertSame(['09:00', '09:30', '11:00', '11:30'], $slots);
    }

    public function test_nao_cria_folga_em_cima_de_agendamento_ativo(): void
    {
        $this->appointmentAt('2026-11-18 10:00', AppointmentStatus::Confirmed);

        Livewire::test(CreateTimeOff::class)
            ->fillForm([
                'professional_id' => $this->professional->id,
                'starts_at' => '2026-11-18 09:00',
                'ends_at' => '2026-11-18 12:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at']);

        $this->assertSame(0, TimeOff::count());
    }

    public function test_agendamento_cancelado_nao_impede_a_folga(): void
    {
        $this->appointmentAt('2026-11-18 10:00', AppointmentStatus::Cancelled);

        Livewire::test(CreateTimeOff::class)
            ->fillForm([
                'professional_id' => $this->professional->id,
                'starts_at' => '2026-11-18 09:00',
                'ends_at' => '2026-11-18 12:00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, TimeOff::count());
    }

    public function test_lista_esconde_folgas_encerradas_por_padrao(): void
    {
        $old = TimeOff::create(['professional_id' => $this->professional->id, 'starts_at' => '2026-11-01 09:00', 'ends_at' => '2026-11-01 18:00']);
        $next = TimeOff::create(['professional_id' => $this->professional->id, 'starts_at' => '2026-11-25 09:00', 'ends_at' => '2026-11-25 18:00']);

        Livewire::test(ListTimeOffs::class)
            ->assertCanSeeTableRecords([$next])
            ->assertCanNotSeeTableRecords([$old]);
    }
}
