<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Agenda;
use App\Models\Avaliacao;
use App\Models\Foto;

// Extende Authenticatable + HasApiTokens para permitir login via API (Sanctum).
class Studio extends Authenticatable
{
    use HasFactory;
    use HasApiTokens;

    protected $table = 'studios';

    protected $fillable = [
        'nome',
        'cnpj',
        'email',
        'senha',
        'whatsapp',
        'cep',
        'rua',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'endereco',
        'descricao',
        'modalidades',
        'tipo',
        'valor_aula',
        'capacidade_padrao',
        'latitude',
        'longitude',
        'status',
        'data_aprovacao',
        'motivo_rejeicao',
        'chave_pix',
        'asaas_account_id',
        'asaas_wallet_id',
        'asaas_api_key',
    ];

    protected $casts = [
        'valor_aula'     => 'decimal:2',
        'data_aprovacao' => 'datetime',
    ];

    public function fotos()
    {
        return $this->morphMany(Foto::class, 'fotavel');
    }

    public function planos(): HasMany
    {
        return $this->hasMany(StudioPlano::class, 'studio_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(StudioHorario::class, 'studio_id');
    }

    public function aulas(): HasMany
    {
        return $this->hasMany(StudioAula::class, 'studio_id');
    }

    public function professores(): HasMany
    {
        return $this->hasMany(StudioProfessor::class, 'studio_id');
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'studio_id');
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class, 'studio_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'studio_id');
    }

    public function getMediaAvaliacaoAttribute()
    {
        $media = $this->avaliacoes()->avg('nota');
        return $media ? number_format($media, 1, '.', '') : '0.0';
    }

    /**
     * Slots agendáveis de 1h dentro do horário de funcionamento do dia,
     * com ocupação x capacidade. Slots bloqueados não aparecem.
     */
    public function slotsDisponiveis(string $data): array
    {
        $dia = \Carbon\Carbon::parse($data);

        $horario = $this->horarios()
            ->where('dia_semana', $dia->dayOfWeek)
            ->where('ativo', true)
            ->first();

        // Sem funcionamento definido no dia => studio fechado (nada agendável).
        if (!$horario) {
            return [];
        }

        $capPadrao  = $horario->capacidade ?? $this->capacidade_padrao ?? 10;
        $abertura   = \Carbon\Carbon::parse($data . ' ' . $horario->hora_abertura);
        $fechamento = \Carbon\Carbon::parse($data . ' ' . $horario->hora_fechamento);

        $agendasDoDia = Agenda::where('studio_id', $this->id)
            ->whereDate('data', $data)
            ->where('cancelado', false)
            ->get();

        // Aulas específicas do dia (com horário de início, duração e profissional).
        $aulas = $this->aulas()
            ->with('professor')
            ->where('dia_semana', $dia->dayOfWeek)
            ->where('ativo', true)
            ->orderBy('hora_inicio')
            ->get();

        // Monta as "janelas" de aula. Se houver aulas cadastradas, usa-as
        // (somente as que ficam dentro do funcionamento do dia); caso contrário,
        // mantém o comportamento antigo (slots de 1h pelo funcionamento).
        $janelas = [];

        if ($aulas->isNotEmpty()) {
            foreach ($aulas as $aula) {
                $inicio = \Carbon\Carbon::parse($data . ' ' . $aula->hora_inicio);
                $fim    = $inicio->copy()->addMinutes((int) ($aula->duracao_min ?: 60));

                // Ignora aulas que extrapolam o horário de funcionamento do dia.
                if ($inicio->lt($abertura) || $fim->gt($fechamento)) {
                    continue;
                }

                $janelas[] = [
                    'inicio'           => $inicio,
                    'fim'              => $fim,
                    'capacidade'       => $aula->capacidade ?? $capPadrao,
                    'profissional'     => $aula->professor->nome ?? $aula->profissional,
                    'professor_resumo' => $aula->professor->resumo ?? null,
                    'duracao'          => (int) ($aula->duracao_min ?: 60),
                ];
            }
        } else {
            $cursor = $abertura->copy();
            while ($cursor->copy()->addHour()->lte($fechamento)) {
                $janelas[] = [
                    'inicio'           => $cursor->copy(),
                    'fim'              => $cursor->copy()->addHour(),
                    'capacidade'       => $capPadrao,
                    'profissional'     => null,
                    'professor_resumo' => null,
                    'duracao'          => 60,
                ];
                $cursor->addHour();
            }
        }

        $slots = [];
        foreach ($janelas as $j) {
            $slotInicio = $j['inicio']->format('H:i:s');
            $slotFim    = $j['fim']->format('H:i:s');

            $sobrepostas = $agendasDoDia->filter(function ($agenda) use ($slotInicio, $slotFim) {
                return $agenda->hora_inicio < $slotFim && $agenda->hora_fim > $slotInicio;
            });

            if ($sobrepostas->contains(fn ($a) => $a->tipo_aula === 'bloqueio')) {
                continue;
            }

            $ocupacao   = $sobrepostas->where('tipo_aula', '!=', 'bloqueio')->count();
            $capacidade = (int) $j['capacidade'];

            $slots[] = [
                'inicio'           => substr($slotInicio, 0, 5),
                'fim'              => substr($slotFim, 0, 5),
                'label'            => substr($slotInicio, 0, 5) . ' - ' . substr($slotFim, 0, 5),
                'capacidade'       => $capacidade,
                'ocupacao'         => $ocupacao,
                'vagas'            => max(0, $capacidade - $ocupacao),
                'profissional'     => $j['profissional'],
                'professor_resumo' => $j['professor_resumo'] ?? null,
                'duracao'          => $j['duracao'],
            ];
        }

        return $slots;
    }
}
