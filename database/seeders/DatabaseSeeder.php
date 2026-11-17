<?php

namespace Database\Seeders;

use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();

        $services = collect([
            ['name' => 'Corte de cabelo', 'duration_minutes' => 30, 'price' => 40],
            ['name' => 'Barba', 'duration_minutes' => 30, 'price' => 30],
            ['name' => 'Corte + Barba', 'duration_minutes' => 60, 'price' => 65],
        ])->map(fn ($data) => Service::create($data));

        $joao = Professional::create(['name' => 'João']);
        $pedro = Professional::create(['name' => 'Pedro']);

        $joao->services()->attach($services->pluck('id'));
        $pedro->services()->attach($services->take(2)->pluck('id'));

        // Segunda a sábado, com pausa para almoço (dois blocos no mesmo dia)
        foreach ([$joao, $pedro] as $professional) {
            foreach (range(1, 6) as $weekday) {
                $professional->workingHours()->createMany([
                    ['weekday' => $weekday, 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['weekday' => $weekday, 'start_time' => '13:00', 'end_time' => '18:00'],
                ]);
            }
        }
    }

    /**
     * Admin para entrar em /admin. Em produção a senha precisa vir do .env:
     * todo usuário tem acesso ao painel, então uma senha padrão seria uma porta aberta.
     */
    private function seedAdmin(): void
    {
        $password = env('ADMIN_PASSWORD');

        if (! $password && app()->isProduction()) {
            throw new RuntimeException('Defina ADMIN_PASSWORD no .env antes de rodar o seeder em produção.');
        }

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@agendly.test')],
            ['name' => 'Administrador', 'password' => $password ?: 'password'],
        );
    }
}
