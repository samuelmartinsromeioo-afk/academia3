<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Agenda;
use App\Models\Avaliacao;
use App\Models\Foto;

class Studio extends Model
{
    use HasFactory;

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

        if (!$horario) {
            return [];
        }

        $capacidade = $horario->capacidade ?? $this->capacidade_padrao ?? 10;

        $agendasDoDia = Agenda::where('studio_id', $this->id)
            ->whereDate('data', $data)
            ->where('cancelado', false)
            ->get();

        $slots = [];
        $cursor = \Carbon\Carbon::parse($data . ' ' . $horario->hora_abertura);
        $fechamento = \Carbon\Carbon::parse($data . ' ' . $horario->hora_fechamento);

        while ($cursor->copy()->addHour()->lte($fechamento)) {
            $slotInicio = $cursor->format('H:i:s');
            $slotFim = $cursor->copy()->addHour()->format('H:i:s');

            $sobrepostas = $agendasDoDia->filter(function ($agenda) use ($slotInicio, $slotFim) {
                return $agenda->hora_inicio < $slotFim && $agenda->hora_fim > $slotInicio;
            });

            $bloqueado = $sobrepostas->contains(fn ($a) => $a->tipo_aula === 'bloqueio');

            if (!$bloqueado) {
                $ocupacao = $sobrepostas->where('tipo_aula', '!=', 'bloqueio')->count();

                $slots[] = [
                    'inicio'     => substr($slotInicio, 0, 5),
                    'fim'        => substr($slotFim, 0, 5),
                    'label'      => substr($slotInicio, 0, 5) . ' - ' . substr($slotFim, 0, 5),
                    'capacidade' => (int) $capacidade,
                    'ocupacao'   => $ocupacao,
                    'vagas'      => max(0, (int) $capacidade - $ocupacao),
                ];
            }

            $cursor->addHour();
        }

        return $slots;
    }
}
