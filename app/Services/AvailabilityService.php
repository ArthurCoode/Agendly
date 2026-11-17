<?php

namespace App\Services;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Horários de início disponíveis para um profissional, serviço e dia.
     *
     * @return Collection<int, Carbon>
     */
    public function slotsFor(
        Professional $professional,
        Service $service,
        Carbon|string $date,
        ?Carbon $now = null,
        int $step = 30,
    ): Collection {
        $now ??= now();
        $dayStart = Carbon::parse($date)->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        // O profissional precisa realizar o serviço.
        if (! $professional->services()->whereKey($service->id)->exists()) {
            return collect();
        }

        $blocks = $professional->workingHours()
            ->where('weekday', $dayStart->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        if ($blocks->isEmpty()) {
            return collect();
        }

        $busy = $this->busyIntervals($professional, $dayStart, $dayEnd);
        $duration = $service->duration_minutes;
        $slots = collect();

        foreach ($blocks as $block) {
            $blockStart = $dayStart->copy()->setTimeFromTimeString($block->start_time);
            $blockEnd = $dayStart->copy()->setTimeFromTimeString($block->end_time);

            $cursor = $blockStart->copy();

            // O serviço inteiro precisa caber dentro do bloco.
            while ($cursor->copy()->addMinutes($duration)->lte($blockEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);

                if ($cursor->gt($now) && ! $this->conflicts($cursor, $slotEnd, $busy)) {
                    $slots->push($cursor->copy());
                }

                $cursor->addMinutes($step);
            }
        }

        return $slots->values();
    }

    /**
     * Confirma se um horário específico ainda está livre.
     * Será usada na Fase 3, logo antes de salvar o agendamento.
     */
    public function isAvailable(
        Professional $professional,
        Service $service,
        Carbon $start,
        ?Carbon $now = null,
        int $step = 30,
    ): bool {
        return $this->slotsFor($professional, $service, $start, $now, $step)
            ->contains(fn (Carbon $slot) => $slot->equalTo($start));
    }

    /**
     * Intervalos já ocupados no dia: agendamentos que bloqueiam e folgas.
     *
     * @return Collection<int, array{0: Carbon, 1: Carbon}>
     */
    private function busyIntervals(Professional $professional, Carbon $dayStart, Carbon $dayEnd): Collection
    {
        $appointments = $professional->appointments()
            ->blocking()
            ->overlapping($dayStart, $dayEnd)
            ->get(['starts_at', 'ends_at']);

        $timeOffs = $professional->timeOffs()
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->get(['starts_at', 'ends_at']);

        return $appointments->toBase()
            ->concat($timeOffs)
            ->map(fn ($item) => [$item->starts_at, $item->ends_at]);
    }

    /** Há sobreposição com algum intervalo ocupado? */
    private function conflicts(Carbon $start, Carbon $end, Collection $busy): bool
    {
        return $busy->contains(
            fn (array $interval) => $start->lt($interval[1]) && $end->gt($interval[0])
        );
    }
}
