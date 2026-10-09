<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// Estende Authenticatable (que estende Model) para o Sanctum poder emitir
// tokens de API para o cliente. O login web por sessão não usa guards do
// Laravel, então nada muda no fluxo Blade existente.
class Cliente extends Authenticatable
{
    use HasApiTokens;
    use \App\Models\Concerns\TemCupomIndicacao;
    use \App\Models\Concerns\AceitaTermos;

    protected $primaryKey = 'id';
    protected $table = 'clientes';

    public $incrementing = true;
    protected $keyType = 'int';

    /** Nunca expor a senha em JSON (API/resources). */
    protected $hidden = [
        'senha',
    ];

    /** A coluna de senha desta tabela chama-se `senha`, não `password`. */
    public function getAuthPassword()
    {
        return $this->senha;
    }


    protected $fillable = [
        'nome',
        'id',
        'email',
        'academia_id',
        'filial_id',
        'senha',
        'aceita_termos',
        'data_aceitacao_termos',
        'ip_aceitacao_termos',
        'cep',
        'rua',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'altura',
        'peso',
        'idade',
        'sexo',
        'frequencia_semanal',
        'modalidade_preferida',
        'resumo_objetivo',
        'condicao_clinica',
        'whatsapp',
        'foto',
        'plano',
        'plano_ativo',
        'studio_id',
        'studio_plano_id',
        'studio_plano_ativo',
    ];
    protected $casts = [
        'aceita_termos' => 'boolean',
        'data_aceitacao_termos' => 'datetime',
    ];

        /**
         * Modalidades de profissional que atendem a preferência deste aluno.
         *
         * Quem é `Híbrido` atende presencial E online, então entra nas duas
         * preferências — mesma regra do filtro da vitrine. Sem preferência
         * declarada devolve lista vazia, que significa "não filtra nada".
         *
         * @return array<int, string>
         */
        public function modalidadesCompativeis(): array
        {
            return self::compativeisCom($this->modalidade_preferida);
        }

        /**
         * A mesma regra, para uma preferência que NÃO está neste modelo.
         *
         * Existe porque a vitrine do app filtra no SQL a partir do
         * `?modalidade=` recebido, que tem precedência sobre a preferência
         * salva (igual ao web) e portanto não sai de um Cliente. Sem este
         * ponto de entrada estático a alternativa seria repetir
         * `[$pref, 'Híbrido']` na query — uma terceira cópia da regra, que é
         * exatamente o que o comentário do método acima pede para não fazer.
         *
         * @return array<int, string>
         */
        public static function compativeisCom(?string $preferida): array
        {
            if (blank($preferida)) {
                return [];
            }

            return [$preferida, 'Híbrido'];
        }

        /** Este profissional atende do jeito que o aluno quer? */
        public function atendidoPor(?string $modalidadeDoProfissional): bool
        {
            $compativeis = $this->modalidadesCompativeis();

            // Sem preferência, qualquer um serve.
            if ($compativeis === []) {
                return true;
            }

            // Profissional que não declarou modalidade não é descartado: a
            // ausência do dado dele não é escolha do aluno, e esconder o
            // profissional por isso puniria quem só não preencheu o cadastro.
            if (blank($modalidadeDoProfissional)) {
                return true;
            }

            return in_array($modalidadeDoProfissional, $compativeis, true);
        }

        public function personal() {
            return $this->belongsTo(\App\Models\Cadastro\Personal::class, 'personal_id');
        }

        public function studio() {
            return $this->belongsTo(\App\Models\Cadastro\Studio::class, 'studio_id');
        }

        public function filial() {
            return $this->belongsTo(\App\Models\Cadastro\Filial::class, 'filial_id');
        }

        public function studioPlano() {
            return $this->belongsTo(\App\Models\Cadastro\StudioPlano::class, 'studio_plano_id');
        }

        public function anamnese() {
            return $this->hasOne(\App\Models\Anamnese::class, 'cliente_id');
        }
    use HasFactory;
}
