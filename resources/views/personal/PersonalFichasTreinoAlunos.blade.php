@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <h1 style="font-size: 2rem; font-weight: 900; color: #d4ff00; margin-bottom: 30px;">
        <i class="fas fa-dumbbell"></i> FICHAS DE TREINO
    </h1>

    @if(session('success'))
    <div style="background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 15px; border-radius: 12px; border: 1px solid #00ff88; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 15px; border-radius: 12px; border: 1px solid #ff4444; margin-bottom: 20px;">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    @if($alunos->isEmpty())
        <div style="text-align: center; padding: 60px 20px; opacity: 0.5;">
            <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 15px; color: #a0a0a0;"></i>
            <p style="color: #a0a0a0; font-size: 1.1rem;">Você não possui alunos com pacote ativo.</p>
            <p style="color: #a0a0a0; font-size: 0.9rem;">Quando seus alunos contratarem pacotes, aparecerão aqui.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            @foreach($alunos as $agendamento)
                <div style="background: #16181d; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 25px; transition: 0.3s;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($agendamento->cliente->nome) }}&background=d4ff00&color=000" 
                            style="width: 60px; height: 60px; border-radius: 50%;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0; font-size: 1.1rem; color: #fff;">{{ $agendamento->cliente->nome }}</h3>
                            <p style="margin: 5px 0 0 0; font-size: 0.8rem; color: #a0a0a0;">
                                <i class="fas fa-dumbbell" style="color: #d4ff00;"></i>
                                Pacote {{ $agendamento->frequencia_pacote }}x/semana
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('fichas-treino.aluno', $agendamento->cliente->id) }}" 
                        style="display: block; background: #d4ff00; color: #000; padding: 12px; border-radius: 10px; text-align: center; text-decoration: none; font-weight: 900; transition: 0.3s; border: none; cursor: pointer;">
                        <i class="fas fa-eye"></i> VER FICHAS
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection