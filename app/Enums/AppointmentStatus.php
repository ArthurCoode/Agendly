<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Confirmed => 'Confirmado',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
            self::NoShow => 'Faltou',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Completed => 'success',
            self::Cancelled => 'gray',
            self::NoShow => 'danger',
        };
    }

    /** Status que ocupam o horário na agenda. */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed];
    }

    /**
     * Para onde cada status pode ir. Concluído, cancelado e falta são finais:
     * reabrir um cancelado poderia colidir com quem reservou o horário liberado.
     *
     * @return array<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Completed, self::NoShow, self::Cancelled],
            default => [],
        };
    }

    /** Status que só fazem sentido depois que o horário começou. */
    public function requiresStarted(): bool
    {
        return in_array($this, [self::Completed, self::NoShow], true);
    }
}
