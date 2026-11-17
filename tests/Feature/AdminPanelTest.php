<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): Service
    {
        return Service::create(['name' => 'Corte', 'duration_minutes' => 30, 'price' => 40]);
    }

    private function makeAppointment(Professional $professional, Service $service): Appointment
    {
        return Appointment::create([
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'customer_name' => 'Maria',
            'customer_phone' => '77999999999',
            'starts_at' => Carbon::now()->addDay()->setTime(10, 0),
            'ends_at' => Carbon::now()->addDay()->setTime(10, 30),
        ]);
    }

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_usuario_logado_abre_as_telas_do_painel(): void
    {
        $this->actingAs(User::factory()->create());

        $service = $this->makeService();
        $professional = Professional::create(['name' => 'João']);
        $professional->workingHours()->create(['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00']);

        $urls = [
            '/admin',
            '/admin/services',
            '/admin/services/create',
            "/admin/services/{$service->id}/edit",
            '/admin/professionals',
            '/admin/professionals/create',
            "/admin/professionals/{$professional->id}/edit",
        ];

        foreach ($urls as $url) {
            $status = $this->get($url)->status();
            $this->assertSame(200, $status, "A tela {$url} respondeu {$status}.");
        }
    }

    public function test_servico_com_agendamentos_nao_pode_ser_excluido(): void
    {
        $user = User::factory()->create();
        $service = $this->makeService();
        $professional = Professional::create(['name' => 'João']);

        $this->assertTrue($user->can('delete', $service));

        $this->makeAppointment($professional, $service);

        $this->assertFalse($user->can('delete', $service));
    }

    public function test_profissional_com_agendamentos_nao_pode_ser_excluido(): void
    {
        $user = User::factory()->create();
        $service = $this->makeService();
        $professional = Professional::create(['name' => 'João']);

        $this->assertTrue($user->can('delete', $professional));

        $this->makeAppointment($professional, $service);

        $this->assertFalse($user->can('delete', $professional));
    }
}
