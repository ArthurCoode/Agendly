<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOff extends Model
{
    protected $table = 'time_offs';

    protected $fillable = ['professional_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Agendamentos ativos que cairiam dentro de uma folga nesse intervalo.
     * Folga não cancela ninguém sozinha: o admin resolve esses antes.
     */
    public static function conflictingAppointments(int $professionalId, $start, $end): Builder
    {
        return Appointment::query()
            ->where('professional_id', $professionalId)
            ->blocking()
            ->overlapping($start, $end);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }
}
