<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Filament\Widgets\TodayOverview;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function appointment(string $start, AppointmentStatus $status, string $customer, float $price): void
    {
        $service = Service::create(['name' => "Serviço {$price}", 'duration_minutes' => 30, 'price' => $price]);
        $professional = Professional::firstOrCreate(['name' => 'João']);

        Appointment::create([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => $customer,
            'customer_phone' => '77999999999',
            'starts_at' => Carbon::parse($start),
            'ends_at' => Carbon::parse($start)->addMinutes(30),
            'status' => $status,
        ]);
    }

    public function test_resumo_do_dia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 12:00'));
        $this->actingAs(User::factory()->create());

        $this->appointment('2026-11-18 09:00', AppointmentStatus::Completed, 'Ana', 40);
        $this->appointment('2026-11-18 10:00', AppointmentStatus::NoShow, 'Bia', 30);
        $this->appointment('2026-11-18 14:00', AppointmentStatus::Confirmed, 'Caio', 65);
        $this->appointment('2026-11-18 16:00', AppointmentStatus::Pending, 'Duda', 30);
        $this->appointment('2026-11-18 17:00', AppointmentStatus::Cancelled, 'Edu', 100);
        $this->appointment('2026-11-19 09:00', AppointmentStatus::Confirmed, 'Fabi', 100);

        $this->get('/admin')->assertOk();

        Livewire::test(TodayOverview::class)
            ->assertSee('14:00 · Caio')
            ->assertSee('135,00') // 40 + 65 + 30: falta e cancelado não entram
            ->assertDontSee('Fabi');
    }
}
