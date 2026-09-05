<?php

namespace App\Models\Nutri;

use App\Models\Cadastro\Personal;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    protected $table = 'nutri_consultas';

    protected $fillable = [
        'personal_id', 'paciente_id', 'data_hora', 'duracao_min', 'tipo',
        'modalidade', 'status', 'observacoes', 'lembrete_enviado',
    ];

    protected $casts = [
        'data_hora' => 'datetime',
        'duracao_min' => 'integer',
        'lembrete_enviado' => 'boolean',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function fim(): \Carbon\Carbon
    {
        return $this->data_hora->copy()->addMinutes($this->duracao_min);
    }

    /** URL "Adicionar ao Google Agenda" (sem OAuth, template público). */
    public function googleCalendarUrl(): string
    {
        $fmt = fn ($d) => $d->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => 'Consulta nutricional — '.($this->paciente->nome ?? ''),
            'dates' => $fmt($this->data_hora).'/'.$fmt($this->fim()),
            'details' => $this->observacoes ?? 'Consulta agendada pelo SnrFit.',
        ]);
    }
}
