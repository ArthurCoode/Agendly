<?php

namespace Tests\Feature;

use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_cria_admin_que_consegue_entrar_no_painel(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@agendly.test')->firstOrFail();

        $this->assertTrue(Hash::check('password', $admin->password));
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->assertSame(3, Service::count());
        $this->assertSame(2, Professional::count());
    }

    public function test_rodar_o_seeder_de_novo_nao_duplica_o_admin(): void
    {
        $this->seed();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@agendly.test')->count());
    }
}
