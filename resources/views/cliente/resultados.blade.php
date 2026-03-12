@extends('layouts.app') {{-- Ou o nome do seu layout principal --}}

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Resultados próximos ao CEP: <strong>{{ $cep }}</strong></h2>
            <p class="text-muted">Mostrando locais em um raio de 15km</p>
            <a href="{{ route('cliente.index') }}" class="btn btn-outline-secondary btn-sm">Nova Busca</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <h4 class="mb-3"><i class="fas fa-dumbbell"></i> Academias</h4>
            
            @if($academias->isEmpty())
                <div class="alert alert-warning">Nenhuma academia encontrada nesta região.</div>
            @else
                @foreach($academias as $academia)
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">{{ $academia->nome }}</h5>
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-map-marker-alt"></i> Distância: 
                                <strong>{{ number_format($academia->distancia, 2, ',', '.') }} km</strong>
                            </p>
                            {{-- Se você tiver o endereço salvo, pode colocar aqui --}}
                            <p class="small text-secondary">{{ $academia->logradouro ?? 'Endereço não disponível' }}</p>
                            <a href="#" class="btn btn-primary btn-sm">Ver Perfil</a>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="col-md-6">
            <h4 class="mb-3"><i class="fas fa-user-tie"></i> Personais Trainers</h4>
            
            @if($personals->isEmpty())
                <div class="alert alert-warning">Nenhum personal disponível nesta região.</div>
            @else
                @foreach($personals as $personal)
                    <div class="card mb-3 border-info shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">{{ $personal->nome }}</h5>
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-walking"></i> Distância: 
                                <strong>{{ number_format($personal->distancia, 2, ',', '.') }} km</strong>
                            </p>
                            <p class="card-text small">{{ Str::limit($personal->bio ?? 'Personal qualificado para te atender.', 80) }}</p>
                            <a href="#" class="btn btn-info btn-sm text-white">Contatar</a>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection