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

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAY = '2030-01-07';
    private const SUNDAY = '2030-01-06';

    private Professional $professional;
    private Service $service;
    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::parse('2030-01-01 08:00');

        $this->service = Service::create([
            'name' => 'Corte',
            'duration_minutes' => 60,
            'price' => 50,
        ]);

        $this->professional = Professional::create(['name' => 'João']);
        $this->professional->services()->attach($this->service);

        // Segunda-feira: 9h-12h e 13h-18h (almoço das 12h às 13h)
        $this->professional->workingHours()->createMany([
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00'],
            ['weekday' => 1, 'start_time' => '13:00', 'end_time' => '18:00'],
        ]);
    }

    private function slots(string $date = self::MONDAY, ?Carbon $now = null): array
    {
        return app(AvailabilityService::class)
            ->slotsFor($this->professional, $this->service, $date, $now ?? $this->now)
            ->map(fn (Carbon $slot) => $slot->format('H:i'))
            ->all();
    }

    private function book(string $start, string $end, AppointmentStatus $status = AppointmentStatus::Confirmed): void
    {
        Appointment::create([
            'professional_id' => $this->professional->id,
            'service_id' => $this->service->id,
            'customer_name' => 'Cliente',
            'customer_phone' => '77999999999',
            'starts_at' => Carbon::parse(self::MONDAY.' '.$start),
            'ends_at' => Carbon::parse(self::MONDAY.' '.$end),
            'status' => $status,
        ]);
    }

    public function test_respeita_blocos_de_trabalho_e_o_almoco(): void
    {
        $slots = $this->slots();

        $this->assertCount(14, $slots);
        $this->assertSame('09:00', $slots[0]);
        $this->assertSame('17:00', end($slots));

        // Nada que invada o almoço ou termine depois do bloco
        $this->assertNotContains('11:30', $slots);
        $this->assertNotContains('12:00', $slots);
        $this->assertNotContains('12:30', $slots);
        $this->assertNotContains('17:30', $slots);
    }

    public function test_remove_horarios_que_conflitam_com_agendamento(): void
    {
        $this->book('10:00', '11:00');

        $slots = $this->slots();

        $this->assertContains('09:00', $slots);  // termina às 10:00, encosta mas não sobrepõe
        $this->assertNotContains('09:30', $slots);
        $this->assertNotContains('10:00', $slots);
        $this->assertNotContains('10:30', $slots);
        $this->assertContains('11:00', $slots);  // começa quando o outro termina
    }

    public function test_agendamento_cancelado_nao_bloqueia_o_horario(): void
    {
        $this->book('10:00', '11:00', AppointmentStatus::Cancelled);

        $this->assertContains('10:00', $this->slots());
    }

    public function test_folga_bloqueia_horarios(): void
    {
        $this->professional->timeOffs()->create([
            'starts_at' => Carbon::parse(self::MONDAY.' 14:00'),
            'ends_at' => Carbon::parse(self::MONDAY.' 16:00'),
            'reason' => 'Médico',
        ]);

        $slots = $this->slots();

        $this->assertNotContains('14:00', $slots);
        $this->assertNotContains('15:30', $slots);
        $this->assertContains('13:00', $slots);
        $this->assertContains('16:00', $slots);
    }

    public function test_dia_sem_expediente_retorna_vazio(): void
    {
        $this->assertSame([], $this->slots(self::SUNDAY));
    }

    public function test_nao_oferece_horarios_que_ja_passaram(): void
    {
        $slots = $this->slots(now: Carbon::parse(self::MONDAY.' 10:10'));

        $this->assertSame('10:30', $slots[0]);
    }

    public function test_profissional_que_nao_faz_o_servico_retorna_vazio(): void
    {
        $outro = Service::create([
            'name' => 'Barba',
            'duration_minutes' => 30,
            'price' => 30,
        ]);

        $slots = app(AvailabilityService::class)
            ->slotsFor($this->professional, $outro, self::MONDAY, $this->now);

        $this->assertTrue($slots->isEmpty());
    }

    public function test_is_available_confirma_horario_livre_e_ocupado(): void
    {
        $this->book('10:00', '11:00');
        $service = app(AvailabilityService::class);

        $this->assertTrue($service->isAvailable(
            $this->professional, $this->service, Carbon::parse(self::MONDAY.' 09:00'), $this->now
        ));
        $this->assertFalse($service->isAvailable(
            $this->professional, $this->service, Carbon::parse(self::MONDAY.' 10:00'), $this->now
        ));
    }
}
