<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão | {{ $academia->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --error: #ff4444;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); }

        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .logo { font-weight: 900; letter-spacing: 2px; }
        .logo span { color: var(--primary); }
        .btn-top { background: transparent; border: 1px solid var(--border); color: var(--text-main); padding: 9px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.78rem; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        header.page-head { margin-bottom: 24px; }
        header.page-head h1 { font-size: 1.7rem; font-weight: 900; }
        header.page-head p { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }

        .alert { padding: 13px 18px; border-radius: 12px; margin-bottom: 18px; font-size: 0.85rem; }
        .alert-ok { background: rgba(212,255,0,0.1); border: 1px solid rgba(212,255,0,0.35); color: var(--primary); }
        .alert-err { background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.35); color: #ff6b6b; }

        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 22px; margin-bottom: 20px; }
        .card h2 { font-size: 1.05rem; font-weight: 800; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: var(--primary); }
        .card .sub { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 16px; }

        label { display: block; font-size: 0.65rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted); margin-bottom: 6px; letter-spacing: 0.5px; }
        input, select, textarea { width: 100%; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px; padding: 11px 13px; color: #fff; font-family: inherit; font-size: 0.88rem; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); }
        textarea { resize: vertical; min-height: 70px; }
        select option { background: var(--card-bg); }

        .grid { display: grid; gap: 14px; }
        .g2 { grid-template-columns: 1fr 1fr; }
        .g4 { grid-template-columns: repeat(4, 1fr); }

        .btn { background: var(--primary); color: #000; border: none; border-radius: 10px; padding: 12px 18px; font-weight: 900; font-size: 0.8rem; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; }
        .btn:hover { background: #e8ff40; }
        .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text-muted); }
        .btn-ghost:hover { border-color: var(--error); color: #ff6b6b; }
        .btn-sm { padding: 8px 12px; font-size: 0.72rem; }

        .item { border: 1px solid var(--border); border-radius: 14px; padding: 16px; margin-bottom: 12px; background: var(--input-bg); }
        .item-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
        .item-head h3 { font-size: 1rem; font-weight: 800; }
        .item-head .meta { color: var(--text-muted); font-size: 0.78rem; margin-top: 4px; line-height: 1.5; }
        .item-actions { display: flex; gap: 8px; }
        .empty { color: var(--text-muted); font-size: 0.82rem; padding: 6px 0 14px; }

        details.edit { margin-top: 12px; border-top: 1px dashed var(--border); padding-top: 12px; }
        details.edit summary { cursor: pointer; color: var(--primary); font-size: 0.74rem; font-weight: 700; list-style: none; }
        details.edit summary::-webkit-details-marker { display: none; }

        .divider { height: 1px; background: var(--border); margin: 20px 0; }

        @media (max-width: 680px) {
            .top-bar { padding: 14px 20px; }
            .g2, .g4 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span> <span style="font-family:'Inter'; font-size:0.65rem; color:var(--text-muted); letter-spacing:1px; text-transform:uppercase;">| Gestão</span></div>
    <a href="{{ route('academia.dashboard') }}" class="btn-top"><i class="fas fa-arrow-left"></i> Voltar ao painel</a>
</div>

<div class="container">
    @if(session('success'))<div class="alert alert-ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

    <header class="page-head">
        <h1>Gestão da Academia</h1>
        <p>Cadastre os profissionais, as aulas e a infraestrutura que os alunos verão em "Ver detalhes".</p>
    </header>

    @php
        $dias = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
    @endphp

    {{-- ============ PROFISSIONAIS ============ --}}
    <div class="card">
        <h2><i class="fas fa-user-tie"></i> Profissionais</h2>
        <p class="sub">Quem trabalha na academia. O aluno clica no nome para ver o resumo.</p>

        @forelse($professores as $prof)
            <div class="item">
                <div class="item-head">
                    <div>
                        <h3>{{ $prof->nome }}</h3>
                        @if($prof->resumo)<div class="meta">{{ $prof->resumo }}</div>@endif
                    </div>
                    <div class="item-actions">
                        <form action="{{ route('academia.professores.destroy', $prof->id) }}" method="POST" onsubmit="return confirm('Remover este profissional?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <details class="edit">
                    <summary><i class="fas fa-pen"></i> Editar</summary>
                    <form action="{{ route('academia.professores.update', $prof->id) }}" method="POST" style="margin-top:12px;">
                        @csrf @method('PUT')
                        <div style="margin-bottom:12px;">
                            <label>Nome</label>
                            <input type="text" name="nome" value="{{ $prof->nome }}" required>
                        </div>
                        <div style="margin-bottom:12px;">
                            <label>Resumo</label>
                            <textarea name="resumo" placeholder="Formação, especialidades, experiência...">{{ $prof->resumo }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-sm"><i class="fas fa-save"></i> Salvar</button>
                    </form>
                </details>
            </div>
        @empty
            <p class="empty">Nenhum profissional cadastrado ainda.</p>
        @endforelse

        <div class="divider"></div>

        <form action="{{ route('academia.professores.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:12px;">
                <label>Nome do profissional</label>
                <input type="text" name="nome" placeholder="Ex: João Silva" required>
            </div>
            <div style="margin-bottom:12px;">
                <label>Resumo</label>
                <textarea name="resumo" placeholder="Formação, especialidades, experiência..."></textarea>
            </div>
            <button type="submit" class="btn"><i class="fas fa-plus"></i> Adicionar profissional</button>
        </form>
    </div>

    {{-- ============ AULAS ============ --}}
    <div class="card">
        <h2><i class="fas fa-people-group"></i> Aulas</h2>
        <p class="sub">As aulas/modalidades oferecidas, com resumo e (opcional) horário e profissional responsável.</p>

        @forelse($aulas as $aula)
            <div class="item">
                <div class="item-head">
                    <div>
                        <h3>{{ $aula->nome }}</h3>
                        <div class="meta">
                            @if(!is_null($aula->dia_semana))<i class="fas fa-calendar-day"></i> {{ $dias[$aula->dia_semana] ?? '' }}@endif
                            @if($aula->hora_inicio) · {{ \Illuminate\Support\Str::substr($aula->hora_inicio, 0, 5) }}@endif
                            @if($aula->duracao_min) · {{ $aula->duracao_min . 'min' }}@endif
                            @if($aula->professor) · <i class="fas fa-user-tie"></i> {{ $aula->professor->nome }}@endif
                            @if($aula->resumo)<br>{{ $aula->resumo }}@endif
                        </div>
                    </div>
                    <div class="item-actions">
                        <form action="{{ route('academia.aulas.destroy', $aula->id) }}" method="POST" onsubmit="return confirm('Remover esta aula?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <details class="edit">
                    <summary><i class="fas fa-pen"></i> Editar</summary>
                    <form action="{{ route('academia.aulas.update', $aula->id) }}" method="POST" style="margin-top:12px;">
                        @csrf @method('PUT')
                        <div style="margin-bottom:12px;">
                            <label>Nome da aula</label>
                            <input type="text" name="nome" value="{{ $aula->nome }}" required>
                        </div>
                        <div style="margin-bottom:12px;">
                            <label>Resumo</label>
                            <textarea name="resumo">{{ $aula->resumo }}</textarea>
                        </div>
                        <div class="grid g4" style="margin-bottom:12px;">
                            <div>
                                <label>Profissional</label>
                                <select name="professor_id">
                                    <option value="">— Nenhum —</option>
                                    @foreach($professores as $prof)
                                        <option value="{{ $prof->id }}" {{ $aula->professor_id == $prof->id ? 'selected' : '' }}>{{ $prof->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>Dia</label>
                                <select name="dia_semana">
                                    <option value="">—</option>
                                    @foreach($dias as $i => $dia)
                                        <option value="{{ $i }}" {{ (string)$aula->dia_semana === (string)$i ? 'selected' : '' }}>{{ $dia }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>Hora</label>
                                <input type="time" name="hora_inicio" value="{{ $aula->hora_inicio ? \Illuminate\Support\Str::substr($aula->hora_inicio, 0, 5) : '' }}">
                            </div>
                            <div>
                                <label>Duração (min)</label>
                                <input type="number" name="duracao_min" min="5" max="600" value="{{ $aula->duracao_min }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm"><i class="fas fa-save"></i> Salvar</button>
                    </form>
                </details>
            </div>
        @empty
            <p class="empty">Nenhuma aula cadastrada ainda.</p>
        @endforelse

        <div class="divider"></div>

        <form action="{{ route('academia.aulas.store') }}" method="POST">
            @csrf
            <div class="grid g2" style="margin-bottom:12px;">
                <div>
                    <label>Nome da aula</label>
                    <input type="text" name="nome" placeholder="Ex: Spinning, Funcional, Pilates" required>
                </div>
                <div>
                    <label>Profissional (opcional)</label>
                    <select name="professor_id">
                        <option value="">— Nenhum —</option>
                        @foreach($professores as $prof)
                            <option value="{{ $prof->id }}">{{ $prof->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="margin-bottom:12px;">
                <label>Resumo da aula</label>
                <textarea name="resumo" placeholder="Sobre o que é a aula, para quem é indicada..."></textarea>
            </div>
            <div class="grid g4" style="margin-bottom:12px;">
                <div>
                    <label>Dia (opcional)</label>
                    <select name="dia_semana">
                        <option value="">—</option>
                        @foreach($dias as $i => $dia)
                            <option value="{{ $i }}">{{ $dia }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Hora (opcional)</label>
                    <input type="time" name="hora_inicio">
                </div>
                <div>
                    <label>Duração min (opcional)</label>
                    <input type="number" name="duracao_min" min="5" max="600" placeholder="60">
                </div>
            </div>
            <button type="submit" class="btn"><i class="fas fa-plus"></i> Adicionar aula</button>
        </form>
    </div>

    {{-- ============ INFRAESTRUTURA ============ --}}
    <div class="card">
        <h2><i class="fas fa-building"></i> Infraestrutura</h2>
        <p class="sub">Descreva os equipamentos, espaços e diferenciais da academia.</p>
        <form action="{{ route('academia.infraestrutura') }}" method="POST">
            @csrf
            <textarea name="infraestrutura" style="min-height:120px;" placeholder="Ex: 2 andares, sala de musculação completa, área de cardio, sala de aulas coletivas, estacionamento, vestiários com armários...">{{ $academia->infraestrutura }}</textarea>
            <button type="submit" class="btn" style="margin-top:14px;"><i class="fas fa-save"></i> Salvar infraestrutura</button>
        </form>
    </div>
</div>
</body>
</html>
