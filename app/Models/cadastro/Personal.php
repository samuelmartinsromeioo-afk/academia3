<?php

namespace App\Models\cadastro;

use App\Models\Agenda;
use App\Models\Avaliacao;
use Illuminate\Database\Eloquent\Model;
use App\Models\Foto;

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
        'resultados',
        'avaliacao',
        'valor_secao',
        'idade',
        'academia_id',
        'latitude',
        'longitude',
    ];



public function agendas() {
    return $this->hasMany(Agenda::class);
}
public function pacotes() {
    return $this->hasMany(Pacote::class, 'personal_id');
}

public function fotos()
{
    return $this->morphMany(Foto::class, 'fotavel');
}

public function avaliacoes()
{
     return $this->hasMany(Avaliacao::class, 'personal_id');
}

public function getMediaAvaliacaoAttribute()
{
    $media = $this->avaliacoes()->avg('nota');
    return $media ? number_format($media, 1, '.', '') : '0.0';
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

}
