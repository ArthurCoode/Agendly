<?php

namespace Database\Seeders;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
}
