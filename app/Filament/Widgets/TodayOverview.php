<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TodayOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Hoje';

    /** Atualiza sozinho para pegar agendamentos feitos pela página pública. */
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = Appointment::query()->whereDate('starts_at', today());

        $active = (clone $today)->blocking()->count();

        $next = Appointment::query()
            ->blocking()
            ->where('starts_at', '>=', now())
            ->whereDate('starts_at', today())
            ->with(['service', 'professional'])
            ->orderBy('starts_at')
            ->first();

        // Previsto = o que ainda vai acontecer + o que já foi concluído hoje.
        $expected = (clone $today)
            ->whereIn('status', [...AppointmentStatus::blocking(), AppointmentStatus::Completed])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->sum('services.price');

        $noShows = (clone $today)->where('status', AppointmentStatus::NoShow)->count();

        return [
            Stat::make('Atendimentos ativos', $active)
                ->description('Pendentes e confirmados')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(AppointmentResource::getUrl('index')),

            Stat::make('Próximo cliente', $next ? $next->starts_at->format('H:i').' · '.$next->customer_name : '—')
                ->description($next ? "{$next->service->name} com {$next->professional->name}" : 'Ninguém mais hoje')
                ->icon(Heroicon::OutlinedClock),

            Stat::make('Faturamento previsto', Number::currency((float) $expected, 'BRL', 'pt_BR'))
                ->description('Ativos + concluídos')
                ->icon(Heroicon::OutlinedBanknotes),

            Stat::make('Faltas', $noShows)
                ->icon(Heroicon::OutlinedUserMinus)
                ->color($noShows > 0 ? 'danger' : 'gray'),
        ];
    }
}
