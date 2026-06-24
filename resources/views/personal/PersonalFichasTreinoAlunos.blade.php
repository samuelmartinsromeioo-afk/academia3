@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
        <h1 style="font-size: 2rem; font-weight: 900; color: #d4ff00; margin: 0;">
            <i class="fas fa-dumbbell"></i> FICHAS DE TREINO
        </h1>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('notificacoes.index') }}"
               style="position:relative; display:inline-flex; align-items:center; gap:8px; background:transparent; color:#F4BE16; border:1px solid #F4BE16; padding:12px 18px; border-radius:10px; font-weight:900; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fas fa-bell"></i> Avisos
                <span data-notif-badge style="display:none; position:absolute; top:-6px; right:-6px; background:#ff3b30; color:#fff; font-size:0.6rem; font-weight:900; min-width:18px; height:18px; border-radius:9px; align-items:center; justify-content:center; padding:0 4px;">0</span>
            </a>
            <a href="{{ route('chat.index') }}"
               style="display:inline-flex; align-items:center; gap:8px; background:transparent; color:#F4BE16; border:1px solid #F4BE16; padding:12px 18px; border-radius:10px; font-weight:900; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fas fa-comments"></i> Chat
            </a>
            <a href="{{ route('painel.feed') }}"
               style="display:inline-flex; align-items:center; gap:8px; background:transparent; color:#F4BE16; border:1px solid #F4BE16; padding:12px 18px; border-radius:10px; font-weight:900; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fas fa-rss"></i> Feed
            </a>
            <a href="{{ route('templates.index') }}"
               style="display:inline-flex; align-items:center; gap:8px; background:transparent; color:#F4BE16; border:1px solid #F4BE16; padding:12px 18px; border-radius:10px; font-weight:900; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fas fa-clone"></i> Templates
            </a>
            <a href="{{ route('periodizacao.index') }}"
               style="display:inline-flex; align-items:center; gap:8px; background:transparent; color:#F4BE16; border:1px solid #F4BE16; padding:12px 18px; border-radius:10px; font-weight:900; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fas fa-layer-group"></i> Periodização
            </a>
            <a href="{{ route('aderencia.dashboard') }}"
               style="display:inline-flex; align-items:center; gap:8px; background:#F4BE16; color:#000; padding:12px 18px; border-radius:10px; font-weight:900; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px; box-shadow:0 0 16px rgba(244,190,22,0.25);">
                <i class="fas fa-bolt"></i> Frequência &amp; Aderência
            </a>
        </div>
    </div>

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
@include('partials.push-notif')
@endsection