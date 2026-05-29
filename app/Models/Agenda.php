<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\cadastro\Personal; 
use App\Models\cadastro\Cliente; 

class Agenda extends Model
{
    use HasFactory;

    protected $table = 'agendas';

    protected $fillable = [
        'personal_id',
        'academia_id',
        'cliente_id',
        'data',
        'hora_inicio',
        'hora_fim',
        'descricao',
        'cancelado',
        'justificativa_cancelamento',
        'cancelado_em',
        'status',
        'frequencia_pacote',
        'data_inicio_pacote',
        'data_fim_pacote',
        'tipo_aula',
        'valor_aula',
        'academia_nome',
    ];

    protected $casts = [
        'data' => 'date',
        'cancelado_em' => 'datetime',
        'valor_aula' => 'decimal:2',
    ];

    /**
     * Relacionamento: Um horário pertence a um Personal.
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function academia()
    {
        return $this->belongsTo(\App\Models\cadastro\Academia::class, 'academia_id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\cadastro\Cliente::class, 'cliente_id');
    }

    
    public function calcularValorAula()
    {
        if ($this->tipo_aula === 'pacote' && $this->frequencia_pacote) {
            // Valor do pacote mensal ÷ quantidade de aulas do mês
            // Exemplo: R$ 400 ÷ 4 aulas = R$ 100 por aula
            $pacote = \App\Models\cadastro\Pacote::where('personal_id', $this->personal_id)
                ->where('frequencia', $this->frequencia_pacote)
                ->first();
            
            if ($pacote) {
                // Calcula número de aulas no mês (frequencia × semanas do mês)
                $semanasDoMes = ceil($this->data->daysInMonth / 7);
                $totalAulasNoMes = $this->frequencia_pacote * $semanasDoMes;
                
                return $pacote->valor_mensal / $totalAulasNoMes;
            }
        }

        // Se for avulsa, retorna o valor guardado ou o valor_secao do personal
        return $this->valor_aula ?? $this->personal->valor_secao ?? 0;
    }

    /**
     * ✅ NOVO: Accessor para exibir valor formatado
     */
    public function getValorFormatadoAttribute()
    {
        return 'R$ ' . number_format($this->calcularValorAula(), 2, ',', '.');
    }
}