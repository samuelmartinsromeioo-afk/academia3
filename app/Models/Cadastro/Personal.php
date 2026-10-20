<?php

namespace App\Models\Cadastro;

use App\Models\Agenda;
use App\Models\Avaliacao;
use App\Models\Foto;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\HasApiTokens;

// Estende Authenticatable (que estende Model) para o Sanctum poder emitir
// tokens de API para o personal. O login web por sessão não usa guards do
// Laravel, então nada muda no fluxo Blade existente.
class Personal extends Authenticatable
{
    use \App\Models\Concerns\AceitaTermos;
    use \App\Models\Concerns\TemCupomIndicacao;
    use \App\Models\Concerns\TemSeloPioneiro;
    use \App\Models\Concerns\TemSubcontaAsaas;
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
        'cref',
        // Carimbo da conferência do CREF no conselho, gravado na aprovação do
        // admin (AdminController@aprovar). Não vem de formulário público — é
        // registro de ato administrativo nosso.
        'cref_conferido_em',
        'cref_conferido_por',
        'cref_conferido_resultado',
        'crn',
        'especialidades',
        'modalidade',
        'bio',
        'resultados',
        'avaliacao',
        'valor_secao',
        'valor_consulta',
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
        'especialidades' => 'array',
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

    /** Só personal trainers (registros antigos com tipo nulo contam como PT). */
    public function scopePersonalTrainers($query)
    {
        return $query->where(function ($w) {
            $w->whereNull('professional_type')
                ->orWhere('professional_type', \App\Enums\ProfessionalType::PERSONAL_TRAINER->value);
        });
    }

    /** Só nutricionistas. */
    public function scopeNutricionistas($query)
    {
        return $query->where('professional_type', \App\Enums\ProfessionalType::NUTRITIONIST->value);
    }

    // O limite de pioneiros por estado vem de config('pioneiro.limite_por_estado').

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
     * Onde este personal atua, para a vitrine do aluno (web E app).
     *
     * São DUAS fontes com confiabilidade diferente, e por isso o retorno as
     * mantém separadas em vez de juntar numa lista só:
     *
     * - `vinculadas`: a relação `academiasAprovadas` — a academia confirmou o
     *   vínculo, então é dado verificado e clicável (tem id).
     * - `informadas`: a COLUNA de texto livre `academias`, que o personal digita
     *   à mão (uma por linha). Ninguém conferiu, e não dá para linkar.
     *
     * Existe aqui, e não em cada vitrine, porque são três consumidores (o modal
     * do web, o payload do app e qualquer tela futura) e a regra de quebra de
     * linha + a deduplicação não podem divergir entre eles. O web mostrava só o
     * texto livre e o vínculo aprovado era invisível ao aluno; o app não mostrava
     * nenhum dos dois.
     *
     * Exige `academiasAprovadas` eager-loaded em listagem, senão é N+1.
     */
    public function locaisDeAtuacao(): array
    {
        $vinculadas = $this->academiasAprovadas
            ->map(fn ($a) => [
                'id' => $a->id,
                'nome' => $a->nome,
                'bairro' => $a->bairro,
                'cidade' => $a->cidade,
                'estado' => $a->estado,
            ])
            ->values()
            ->all();

        // O personal costuma digitar à mão a mesma academia que depois aprovou o
        // vínculo. Listar as duas mostraria "Smart Fit" duas vezes, uma
        // verificada e outra não, o que parece defeito.
        $jaVinculadas = array_map(
            fn ($a) => $this->chaveNomeAcademia($a['nome']),
            $vinculadas
        );

        $informadas = collect(preg_split('/\r\n|\r|\n/', (string) $this->academias))
            ->map(fn ($linha) => trim($linha))
            ->filter()
            ->reject(fn ($nome) => in_array($this->chaveNomeAcademia($nome), $jaVinculadas, true))
            ->unique(fn ($nome) => $this->chaveNomeAcademia($nome))
            ->values()
            ->all();

        return [
            'vinculadas' => $vinculadas,
            'informadas' => $informadas,
        ];
    }

    /** Normaliza o nome da academia só para comparar duas grafias da mesma. */
    private function chaveNomeAcademia(string $nome): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($nome)));
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
                'status' => 'pendente',
                'solicitado_em' => now(),
                'respondido_em' => null,
            ]);

            return;
        }

        $this->academiasVinculadas()->attach($academia->id, [
            'status' => 'pendente',
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

    // getEhPioneiroAttribute() e definirPosicaoPioneiro() vêm do trait
    // TemSeloPioneiro, compartilhado com Academia, Studio e Loja.

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
