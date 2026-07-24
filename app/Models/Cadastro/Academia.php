<?php

namespace App\Models\Cadastro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Foto;
use App\Models\Cadastro\Plano;
use App\Models\Cadastro\Filial;
// Extende Authenticatable + HasApiTokens para permitir login via API (Sanctum),
// igual a Cliente/Personal. O login web por sessão continua funcionando.
class Academia extends Authenticatable
{
    use HasApiTokens;

    // O nome da tabela deve bater com o banco
    protected $table = 'academias';


    protected $fillable = [
        'nome',
        'cep',
        'rua',
        'bairro',
        'cidade',
        'estado',
        'complemento',
        'endereco',
        'valor_mensalidade',
        'quantidade_alunos',
        'descricao',
        'email',
        'senha',
        'cnpj',
        'status',
        'data_aprovacao',
        'motivo_rejeicao',
        'tipos_aulas',
        'infraestrutura',
        'latitude',
        'longitude',
        'chave_pix',
        'asaas_account_id',
        'asaas_wallet_id',
        'asaas_api_key',
                            ];

    //garante com que os dados saiam de forma correta do banco de dados 
    protected $casts = [
        'valor' => 'decimal:2',
        'created_at' => 'datetime',
    ];
    public function cliente(): HasMany
    {
        return $this->hasMany(Academia::class);
    }
   
    public function fotos()
    {
        return $this->morphMany(Foto::class, 'fotavel');
    }

    public function planos(): HasMany
    {
        return $this->hasMany(Plano::class);
    }

    public function filiais(): HasMany
    {
        return $this->hasMany(Filial::class);
    }

    public function professores(): HasMany
    {
        return $this->hasMany(AcademiaProfessor::class, 'academia_id');
    }

    /**
     * Personais vinculados a esta academia (vínculo real via solicitação/aprovação).
     * Diferente de `professores()`, que são profissionais cadastrados manualmente
     * pela própria academia; aqui são personais com conta na plataforma.
     */
    public function personais()
    {
        return $this->belongsToMany(Personal::class, 'academia_personal', 'academia_id', 'personal_id')
            ->withPivot('status', 'solicitado_em', 'respondido_em')
            ->withTimestamps();
    }

    /** Solicitações de vínculo ainda aguardando resposta da academia. */
    public function solicitacoesPendentes()
    {
        return $this->personais()->wherePivot('status', 'pendente');
    }

    /** Personais cujo vínculo já foi aprovado (aparecem na página pública). */
    public function personaisAprovados()
    {
        return $this->personais()->wherePivot('status', 'aprovado');
    }

    public function aulas(): HasMany
    {
        return $this->hasMany(AcademiaAula::class, 'academia_id');
    }

    use HasFactory;
}
