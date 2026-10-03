<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cadastro\Personal; 
use App\Models\Cadastro\Cliente; 

class Agenda extends Model
{
    use HasFactory;

    protected $table = 'agendas';

    protected $fillable = [
        'personal_id',
        'payment_id',
        'academia_id',
        'studio_id',
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
        'modalidade',
        'valor_aula',
        'academia_nome',
    ];

    protected $casts = [
        'data' => 'date',
        'cancelado_em' => 'datetime',
        'valor_aula' => 'decimal:2',
    ];

    /** Uma aula acontece de um jeito só: `Híbrido` não é opção aqui. */
    public const MODALIDADES = ['Presencial', 'Online'];

    /**
     * Modalidades que o aluno pode escolher ao reservar com este profissional.
     *
     * Profissional `Híbrido` oferece as duas e por isso a escolha é do aluno;
     * quem atende de um jeito só não gera escolha — devolve aquele único valor, e
     * a tela nem pergunta. Quem não declarou nada devolve as duas: sem o dado
     * dele não há como restringir, e travar a reserva seria pior.
     *
     * @return array<int, string>
     */
    public static function modalidadesDisponiveis(?string $modalidadeDoProfissional): array
    {
        if ($modalidadeDoProfissional === 'Presencial' || $modalidadeDoProfissional === 'Online') {
            return [$modalidadeDoProfissional];
        }

        return self::MODALIDADES;
    }

    /**
     * A modalidade que deve ser gravada na aula.
     *
     * Devolve a escolha do aluno quando houve uma; senão, só preenche se a
     * resposta for DEDUTÍVEL — profissional que atende de um jeito único. Para
     * `Híbrido` (ou sem declaração) devolve null em vez de chutar: gravar
     * "Presencial" por omissão afirmaria algo que ninguém escolheu, e o personal
     * se programaria com base num palpite.
     */
    public static function modalidadeResolvida(?string $escolhida, ?string $modalidadeDoProfissional): ?string
    {
        if (filled($escolhida)) {
            return $escolhida;
        }

        $opcoes = self::modalidadesDisponiveis($modalidadeDoProfissional);

        return count($opcoes) === 1 ? $opcoes[0] : null;
    }

    /** O profissional aceita dar a aula nesta modalidade? */
    public static function modalidadeValida(?string $escolhida, ?string $modalidadeDoProfissional): bool
    {
        if (blank($escolhida)) {
            return true; // não informado continua aceito (histórico e app antigo)
        }

        return in_array($escolhida, self::modalidadesDisponiveis($modalidadeDoProfissional), true);
    }

    /** Ícone da modalidade, igual ao usado na vitrine. */
    public function iconeModalidade(): string
    {
        return $this->modalidade === 'Online' ? 'ph-monitor-play' : 'ph-barbell';
    }

    /**
     * Relacionamento: Um horário pertence a um Personal.
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function academia()
    {
        return $this->belongsTo(\App\Models\Cadastro\Academia::class, 'academia_id');
    }

    public function studio()
    {
        return $this->belongsTo(\App\Models\Cadastro\Studio::class, 'studio_id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cadastro\Cliente::class, 'cliente_id');
    }

    
    public function calcularValorAula()
    {
        if ($this->tipo_aula === 'pacote' && $this->frequencia_pacote) {
            // Valor do pacote mensal ÷ quantidade de aulas do mês
            // Exemplo: R$ 400 ÷ 4 aulas = R$ 100 por aula
            $pacote = \App\Models\Cadastro\Pacote::where('personal_id', $this->personal_id)
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