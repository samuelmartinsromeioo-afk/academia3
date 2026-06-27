<?php

namespace App\Models\Cadastro;

use App\Models\Agenda;
use App\Models\Avaliacao;
use App\Models\Foto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

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
        'pioneiro_posicao',
    ];

    protected $casts = [
        'precos_avaliacao' => 'array',
        'pioneiro_posicao' => 'integer',
    ];

    /** Quantidade de personais por estado que recebem o selo de pioneiro. */
    public const LIMITE_PIONEIROS_POR_ESTADO = 100;

    /**
     * Nunca exponha a key da subconta Asaas em JSON, dd() ou logs.
     * (Mantida como texto comum na coluna — sem cast 'encrypted' — porque a
     * criptografia/descriptografia é feita manualmente via Crypt, ver abaixo.)
     */
    protected $hidden = [
        'asaas_api_key',
        'senha',
    ];

    /**
     * Descriptografa a apiKey da subconta Asaas apenas no instante do uso.
     * Retorna null se não houver key. Tolera valores legados gravados em texto
     * puro (antes da criptografia): nesse caso devolve o valor como está, sem
     * logar o conteúdo. O chamador deve usar o retorno numa variável local de
     * escopo curto e descartá-la em seguida.
     */
    public function getAsaasApiKeyDecrypted(): ?string
    {
        if (empty($this->asaas_api_key)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->asaas_api_key);
        } catch (DecryptException $e) {
            // Key legada em texto puro (criada antes da criptografia). Recomenda-se
            // regenerá-la pelo fluxo de subconta legada para passar a guardar cripto.
            return $this->asaas_api_key;
        }
    }

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

    /**
     * Personal entre os 100 primeiros a se cadastrar no seu estado (pioneiro).
     */
    public function getEhPioneiroAttribute(): bool
    {
        return ! is_null($this->pioneiro_posicao);
    }

    /**
     * Define a posição de pioneiro deste personal: se ele está entre os
     * LIMITE_PIONEIROS_POR_ESTADO primeiros a se cadastrar no seu estado,
     * grava a posição (1..limite); caso contrário mantém NULL. A posição é a
     * próxima livre do estado (maior já atribuída + 1), o que a mantém única e
     * estável mesmo após exclusões. Deve ser chamado logo após criar o registro.
     */
    public function definirPosicaoPioneiro(): void
    {
        if (empty($this->estado)) {
            return;
        }

        // Próxima posição livre do estado = maior posição já atribuída + 1.
        // Usar a "marca d'água" (e não uma contagem de linhas) mantém as
        // posições únicas e monotônicas mesmo que um personal anterior seja
        // excluído: a vaga liberada vira um buraco permanente em vez de ser
        // reaproveitada e gerar números repetidos (ex.: dois "#100").
        $posicao = (int) static::where('estado', $this->estado)->max('pioneiro_posicao') + 1;

        if ($posicao <= self::LIMITE_PIONEIROS_POR_ESTADO) {
            $this->pioneiro_posicao = $posicao;
            $this->save();
        }
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
