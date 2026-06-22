<?php

namespace App\Models\Cadastro;

use App\Models\Agenda;
use App\Models\Avaliacao;
use App\Models\Foto;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'personals';

    public $timestamps = false;

    protected $fillable = [
        'nome',
        'cpf',
        'cep',
        'rua',
        'foto',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'senha',
        'email',
        'certificado',
        'cref',
        'resultados',
        'avaliacao',
        'valor_secao',
        'idade',
        'data_aceicao_termos_atualizacao',
        'ip_aceicao_termos_atualizacao',
        'academia_id',
        'latitude',
        'longitude',
        'status',
        'data_aprovacao',
        'motivo_rejeicao',
        'academias',
        'chave_pix',
        'stripe_account_id',
        'stripe_onboarding_complete',
        'whatsapp',
        'asaas_account_id',
        'asaas_wallet_id',
        'asaas_api_key',
        'valor_ficha',
        'valor_avaliacao',
        'precos_avaliacao',
    ];

    protected $casts = [
        'precos_avaliacao' => 'array',
    ];

    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }

    public function solicitacoesFicha()
    {
        return $this->hasMany(\App\Models\SolicitacaoFicha::class, 'personal_id');
    }

    public function pacotes()
    {
        return $this->hasMany(Pacote::class, 'personal_id');
    }

    public function pacotesAvaliacao()
    {
        return $this->hasMany(\App\Models\PacoteAvaliacao::class, 'personal_id');
    }

    public function fotos()
    {
        return $this->morphMany(Foto::class, 'fotavel');
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class, 'personal_id');
    }

    /** Mínimo de avaliações para exibir a nota publicamente. */
    public const MIN_AVALIACOES_PUBLICAS = 15;

    public function getMediaAvaliacaoAttribute()
    {
        $media = $this->avaliacoes()->avg('nota');

        return $media ? number_format($media, 1, '.', '') : '0.0';
    }

    /**
     * Profissional ainda sem avaliações suficientes para exibir a nota pública.
     * Reutiliza a relação já carregada para não gerar consultas extras.
     */
    public function getEhNovoProfissionalAttribute(): bool
    {
        return $this->avaliacoes->count() < self::MIN_AVALIACOES_PUBLICAS;
    }

    public function getFaturamentoMensalAttribute()
    {
        // Pega todos os compromissos do mês atual que NÃO sejam bloqueios
        $agendas = $this->agendas()
            ->whereMonth('data', now()->month)
            ->whereYear('data', now()->year)
            ->where('cancelado', false)
            ->get();

        $totalGeral = 0;

        foreach ($agendas as $item) {

            $inicio = \Carbon\Carbon::parse($item->hora_inicio);
            $fim = \Carbon\Carbon::parse($item->hora_fim);

            // Calcula a diferença em horas (ex: 1h30m vira 1.5)
            $horasTrabalhadas = $inicio->diffInMinutes($fim) / 60;

            $totalGeral += ($horasTrabalhadas * $this->valor_secao);
        }

        return $totalGeral;
    }

    /**
     * Calcula o financeiro do mês (pacotes + aulas avulsas)
     *
     * @param  int|null  $mes
     * @param  int|null  $ano
     * @return array
     */
    public function calcularFinanceiroMes($personalId = null, $mes = null, $ano = null)
    {
        $personalId = $personalId ?? $this->id;
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        // ✅ AULAS DE PACOTE
        $faturamentoPacotes = 0;
        $pacotesProcessados = [];

        $aulasPacote = \App\Models\Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('tipo_aula', 'pacote')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        foreach ($aulasPacote as $agenda) {
            if ($agenda->frequencia_pacote && ! isset($pacotesProcessados[$agenda->frequencia_pacote])) {
                $pacote = \App\Models\Cadastro\Pacote::where('personal_id', $personalId)
                    ->where('frequencia', $agenda->frequencia_pacote)
                    ->first();

                if ($pacote) {
                    $aulasDoMes = \App\Models\Agenda::where('personal_id', $personalId)
                        ->where('cancelado', false)
                        ->where('tipo_aula', 'pacote')
                        ->where('frequencia_pacote', $agenda->frequencia_pacote)
                        ->whereMonth('data', $mes)
                        ->whereYear('data', $ano)
                        ->count();

                    $pacotesProcessados[$agenda->frequencia_pacote] = true;
                    $faturamentoPacotes += $pacote->valor_mensal;
                }
            }
        }

        // ✅ AULAS AVULSAS
        $faturamentoAvulsas = 0;
        $aulasAvulsas = \App\Models\Agenda::where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('tipo_aula', 'avulsa')
            ->whereMonth('data', $mes)
            ->whereYear('data', $ano)
            ->get();

        $personal = \App\Models\Cadastro\Personal::find($personalId);

        foreach ($aulasAvulsas as $agenda) {
            $duracao = \Carbon\Carbon::parse($agenda->hora_inicio)
                ->diffInMinutes(\Carbon\Carbon::parse($agenda->hora_fim)) / 60;
            $faturamentoAvulsas += ($duracao * ($personal->valor_secao ?? 0));
        }

        // ✅ RETORNA SEPARADO
        return [
            'pacotes' => $faturamentoPacotes,
            'avulsas' => $faturamentoAvulsas,
            'total' => $faturamentoPacotes + $faturamentoAvulsas,
            'detalhes' => [
                'quantidade_aulas_pacote' => $aulasPacote->count(),
                'quantidade_aulas_avulsa' => $aulasAvulsas->count(),
                'valor_secao' => $personal->valor_secao ?? 0,
            ],
        ];
    }
}
