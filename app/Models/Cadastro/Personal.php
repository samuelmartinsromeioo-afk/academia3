<?php

namespace App\Models\Cadastro;

use App\Models\Agenda;
use App\Models\Avaliacao;
use App\Models\Foto;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;

// Estende Authenticatable (que estende Model) para o Sanctum poder emitir
// tokens de API para o personal. O login web por sessão não usa guards do
// Laravel, então nada muda no fluxo Blade existente.
class Personal extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'personals';

    /** A coluna de senha desta tabela chama-se `senha`, não `password`. */
    public function getAuthPassword()
    {
        return $this->senha;
    }

    public $timestamps = false;

    protected $fillable = [
        'professional_type',
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
        'crn',
        'especialidades',
        'modalidade',
        'bio',
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
        'especialidades'   => 'array',
        'pioneiro_posicao' => 'integer',
    ];

    /** Tipo de profissional (enum), com fallback para personal trainer. */
    public function tipoProfissional(): \App\Enums\ProfessionalType
    {
        return \App\Enums\ProfessionalType::tryFromDefault($this->professional_type);
    }

    public function isNutricionista(): bool
    {
        return $this->tipoProfissional() === \App\Enums\ProfessionalType::NUTRITIONIST;
    }

    public function isPersonalTrainer(): bool
    {
        return $this->tipoProfissional() === \App\Enums\ProfessionalType::PERSONAL_TRAINER;
    }

    /** Número de registro do conselho conforme o tipo (CREF ou CRN). */
    public function registroConselho(): ?string
    {
        return $this->isNutricionista() ? $this->crn : $this->cref;
    }

    // ── Relações do módulo de nutrição ───────────────────────────────
    public function pacientes()
    {
        return $this->hasMany(\App\Models\Nutri\Paciente::class, 'personal_id');
    }

    public function planosAlimentares()
    {
        return $this->hasMany(\App\Models\Nutri\PlanoAlimentar::class, 'personal_id');
    }

    public function modelosAnamnese()
    {
        return $this->hasMany(\App\Models\Nutri\AnamneseModelo::class, 'personal_id');
    }

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

    /**
     * Academias às quais o personal se vinculou (vínculo real via aprovação).
     *
     * ATENÇÃO: NÃO nomear esta relação como `academias()`, pois `academias` já é
     * uma COLUNA de texto livre desta tabela (as academias que o personal digita
     * à mão). Um método `academias()` sobrescreveria o acessor `$personal->academias`
     * e quebraria o campo existente. Por isso a relação chama-se `academiasVinculadas`.
     */
    public function academiasVinculadas()
    {
        return $this->belongsToMany(Academia::class, 'academia_personal', 'personal_id', 'academia_id')
            ->withPivot('status', 'solicitado_em', 'respondido_em')
            ->withTimestamps();
    }

    /** Apenas as academias que já aprovaram o vínculo deste personal. */
    public function academiasAprovadas()
    {
        return $this->academiasVinculadas()->wherePivot('status', 'aprovado');
    }

    /**
     * Cria uma solicitação de vínculo (pendente) para a academia informada.
     * Bloqueia duplicidade: se já existe vínculo pendente ou aprovado com essa
     * academia, lança RuntimeException com mensagem amigável (o controller trata).
     */
    public function solicitarVinculo(Academia $academia): void
    {
        $existente = $this->academiasVinculadas()
            ->where('academias.id', $academia->id)
            ->first();

        if ($existente) {
            $status = $existente->pivot->status;
            if ($status === 'aprovado') {
                throw new \RuntimeException('Você já está vinculado a esta academia.');
            }
            if ($status === 'pendente') {
                throw new \RuntimeException('Você já tem uma solicitação pendente para esta academia.');
            }
            // Vínculo anteriormente rejeitado: permite solicitar de novo, reabrindo como pendente.
            $this->academiasVinculadas()->updateExistingPivot($academia->id, [
                'status'        => 'pendente',
                'solicitado_em' => now(),
                'respondido_em' => null,
            ]);

            return;
        }

        $this->academiasVinculadas()->attach($academia->id, [
            'status'        => 'pendente',
            'solicitado_em' => now(),
        ]);
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
