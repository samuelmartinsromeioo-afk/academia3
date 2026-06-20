<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichas de Treino - {{ $cliente->nome }}</title>
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
            --success: #00ff88;
            --border: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-main);
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font-family: inherit;
        }

        input, textarea, select {
            font-family: inherit;
        }

        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* HEADER */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header-left {
            flex: 1;
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: inline-block;
            transition: 0.3s;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        h1 {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
            margin: 0;
        }

        .btn-nova-ficha {
            background: var(--primary);
            color: #000;
            padding: 12px 25px;
            border-radius: 10px;
            border: none;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.9rem;
        }

        .btn-nova-ficha:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        /* ALERT */
        .alert {
            background: rgba(0, 255, 136, 0.1);
            color: var(--success);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid var(--success);
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            opacity: 0.6;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .empty-state p {
            color: var(--text-main);
            font-size: 1.1rem;
            margin: 10px 0;
        }

        /* GRID DE FICHAS */
        .fichas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .ficha-card {
            background: var(--card-bg);
            border: 1px solid rgba(212, 255, 0, 0.2);
            border-radius: 20px;
            padding: 20px;
            transition: 0.3s;
        }

        .ficha-card:hover {
            border-color: rgba(212, 255, 0, 0.5);
            box-shadow: 0 0 20px rgba(212, 255, 0, 0.1);
        }

        .ficha-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .ficha-header h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .ficha-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: 1px solid;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }

        .btn-edit {
            background: rgba(212, 255, 0, 0.1);
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-edit:hover {
            background: var(--primary);
            color: #000;
        }

        .btn-delete {
            background: rgba(255, 68, 68, 0.1);
            color: var(--error);
            border-color: var(--error);
        }

        .btn-delete:hover {
            background: var(--error);
            color: #fff;
        }

        .ficha-nome {
            margin: 0 0 15px 0;
            color: var(--text-main);
            font-weight: 700;
            font-size: 1.05rem;
        }

        .ficha-obs {
            margin: 0 0 15px 0;
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.5;
            border-left: 3px solid var(--primary);
            padding-left: 10px;
        }

        .exercicios-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .exercicios-title {
            margin: 0 0 12px 0;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 900;
        }

        .exercicios-table {
            width: 100%;
            font-size: 0.85rem;
            color: var(--text-main);
            border-collapse: collapse;
        }

        .exercicios-table thead tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .exercicios-table th {
            text-align: left;
            padding: 8px 0;
            color: var(--primary);
            font-weight: 900;
        }

        .exercicios-table td {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .exercicios-table tbody tr:last-child td {
            border-bottom: none;
        }

        .btn-delete-ex {
            background: none;
            border: none;
            color: var(--error);
            cursor: pointer;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .btn-delete-ex:hover {
            transform: scale(1.2);
        }

        .empty-ex {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            padding: 10px 0;
        }

        .btn-add-ex {
            width: 100%;
            background: rgba(212, 255, 0, 0.1);
            color: var(--primary);
            border: 1px solid rgba(212, 255, 0, 0.3);
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: 0.3s;
        }

        .btn-add-ex:hover {
            background: rgba(212, 255, 0, 0.2);
            border-color: var(--primary);
        }

        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.92);
            backdrop-filter: blur(8px);
            z-index: 2000;
            overflow-y: auto;
            padding: 40px 0;
        }

        .modal-overlay.active {
            display: block;
        }

        .modal-content {
            background: var(--card-bg);
            max-width: 500px;
            margin: auto;
            padding: 35px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .modal-content h2 {
            color: var(--primary);
            margin-top: 0;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 900;
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: 0.2s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 10px rgba(212, 255, 0, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 900;
            transition: 0.2s;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-submit {
            flex: 1;
            background: var(--primary);
            color: #000;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 900;
            transition: 0.2s;
        }

        .btn-submit:hover {
            filter: brightness(1.1);
        }

        /* BADGE NIVEL/DIVISÃO NA FICHA */
        .ficha-badges {
            display: flex;
            gap: 6px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .badge-nivel {
            font-size: 0.68rem;
            font-weight: 900;
            padding: 2px 9px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .badge-nivel.iniciante {
            background: rgba(0, 200, 120, 0.15);
            color: #00c878;
            border: 1px solid rgba(0, 200, 120, 0.3);
        }
        .badge-nivel.avancado {
            background: rgba(212, 255, 0, 0.12);
            color: var(--primary);
            border: 1px solid rgba(212, 255, 0, 0.3);
        }
        .badge-divisao {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 20px;
            background: rgba(255,255,255,0.06);
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* SELETOR DE NÍVEL */
        .nivel-toggle {
            display: flex;
            gap: 8px;
        }
        .nivel-btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .nivel-btn.ativo {
            background: rgba(212, 255, 0, 0.12);
            color: var(--primary);
            border-color: rgba(212, 255, 0, 0.4);
        }

        /* SELETOR DE EXERCÍCIO */
        .exercicio-search-wrapper {
            position: relative;
        }
        .exercicio-search-wrapper .search-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
        }
        .exercicio-search-wrapper input {
            padding-left: 34px !important;
        }
        .lista-exercicios {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.1);
            border-top: none;
            border-radius: 0 0 8px 8px;
            background: #12141a;
            margin-top: 0;
        }
        .lista-exercicios::-webkit-scrollbar {
            width: 4px;
        }
        .lista-exercicios::-webkit-scrollbar-track {
            background: transparent;
        }
        .lista-exercicios::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 2px;
        }
        .exercicio-item {
            padding: 9px 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.15s;
        }
        .exercicio-item:hover {
            background: rgba(212, 255, 0, 0.07);
        }
        .exercicio-item:last-child {
            border-bottom: none;
        }
        .exercicio-item-nome {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-main);
        }
        .exercicio-item-grupo {
            font-size: 0.7rem;
            color: var(--text-muted);
            background: rgba(255,255,255,0.07);
            padding: 2px 7px;
            border-radius: 10px;
            white-space: nowrap;
        }
        .exercicio-vazio {
            padding: 14px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .exercicio-personalizado {
            padding: 9px 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--primary);
            border-top: 1px solid rgba(255,255,255,0.08);
            transition: background 0.15s;
        }
        .exercicio-personalizado:hover {
            background: rgba(212, 255, 0, 0.07);
        }
        .exercicio-escolhido {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 13px;
            background: rgba(212, 255, 0, 0.08);
            border: 1px solid rgba(212, 255, 0, 0.25);
            border-radius: 8px;
        }
        .exercicio-escolhido-nome {
            font-weight: 700;
            font-size: 0.9rem;
            flex: 1;
        }
        .exercicio-escolhido-grupo {
            font-size: 0.72rem;
            color: var(--primary);
            background: rgba(212,255,0,0.1);
            padding: 2px 8px;
            border-radius: 10px;
        }
        .btn-trocar-exercicio {
            background: none;
            border: 1px solid rgba(255,255,255,0.15);
            color: var(--text-muted);
            padding: 3px 9px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: 0.2s;
            white-space: nowrap;
        }
        .btn-trocar-exercicio:hover {
            background: rgba(255,255,255,0.08);
            color: var(--text-main);
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .fichas-grid {
                grid-template-columns: 1fr;
            }

            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="page-header">
            <div class="header-left">
                <a href="{{ route('personal.dashboard') }}" class="back-link">
                    <i class="fas fa-arrow-left"></i> VOLTAR
                </a>
                <h1>
                    <i class="fas fa-dumbbell"></i> FICHAS DE {{ strtoupper($cliente->nome) }}
                </h1>
            </div>
            <button id="btnNovaFicha" class="btn-nova-ficha">
                <i class="fas fa-plus"></i> NOVA FICHA
            </button>
        </div>

        @if(session('success'))
        <div class="alert">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
        @endif

        @if($fichas->isEmpty())
            <div class="empty-state">
                <i class="fas fa-calendar"></i>
                <p>Nenhuma ficha criada ainda.</p>
                <small style="color: var(--text-muted);">Clique em "NOVA FICHA" para começar!</small>
            </div>
        @else
            <div class="fichas-grid">
                @foreach($fichas as $ficha)
                    @php
                        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                    @endphp
                    <div class="ficha-card">
                        <div class="ficha-header">
                            <h3><i class="fas fa-calendar-day"></i> {{ $dias[$ficha->dia_semana] }}</h3>
                            <div class="ficha-buttons">
                                <button class="btn-icon btn-edit" onclick="editarFicha({{ $ficha->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon btn-delete" onclick="deletarFicha({{ $ficha->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="ficha-badges">
                            @php $nivelFicha = $ficha->nivel ?? 'iniciante'; @endphp
                            <span class="badge-nivel {{ $nivelFicha }}">
                                {{ $nivelFicha === 'avancado' ? 'Avançado' : 'Iniciante' }}
                            </span>
                            @if($ficha->divisao)
                                @php
                                    $divisoesNomes = [
                                        'costas_biceps' => 'Costas e Bíceps',
                                        'peito_ombro_triceps' => 'Peito, Ombro e Tríceps',
                                        'pernas_gluteos' => 'Pernas e Glúteos',
                                        'abdomen_core' => 'Abdômen e Core',
                                        'full_body' => 'Corpo Inteiro',
                                        'cardio' => 'Cardio e Aeróbico',
                                    ];
                                @endphp
                                <span class="badge-divisao">{{ $divisoesNomes[$ficha->divisao] ?? $ficha->divisao }}</span>
                            @endif
                        </div>

                        <p class="ficha-nome">{{ $ficha->nome_treino }}</p>

                        @if($ficha->observacoes)
                            <p class="ficha-obs">{{ $ficha->observacoes }}</p>
                        @endif

                        <div class="exercicios-box">
                            <p class="exercicios-title"><i class="fas fa-list-ul"></i> Exercícios</p>
                            
                            @if($ficha->exercicios->isEmpty())
                                <p class="empty-ex">Nenhum exercício adicionado</p>
                            @else
                                <table class="exercicios-table resp-cards">
                                    <thead>
                                        <tr>
                                            <th>Exercício</th>
                                            <th style="text-align: center;">Séries</th>
                                            <th style="text-align: center;">Reps</th>
                                            <th style="text-align: center;">Peso</th>
                                            <th style="text-align: center;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ficha->exercicios as $exercicio)
                                        <tr>
                                            <td data-label="Exercício">
                                                {{ $exercicio->nome_exercicio }}
                                                @if($exercicio->observacoes)<div style="color:var(--text-muted); font-size:0.72rem; margin-top:2px;">{{ $exercicio->observacoes }}</div>@endif
                                                @if($exercicio->video)
                                                    <button type="button" onclick="abrirVideo('{{ asset('storage/' . $exercicio->video) }}')" style="margin-top:4px; background:none; border:none; color:var(--primary); font-size:0.72rem; font-weight:700; cursor:pointer; padding:0; display:inline-flex; align-items:center; gap:5px;"><i class="fas fa-play-circle"></i> Ver vídeo</button>
                                                @endif
                                            </td>
                                            <td data-label="Séries" style="text-align: center;">{{ $exercicio->series }}</td>
                                            <td data-label="Reps" style="text-align: center;">{{ $exercicio->repeticoes }}</td>
                                            <td data-label="Peso" style="text-align: center;">{{ $exercicio->peso ? $exercicio->peso . ' kg' : '-' }}</td>
                                            <td style="text-align: center; white-space:nowrap;">
                                                <button class="btn-delete-ex" style="color:var(--primary);" title="Editar"
                                                    onclick="editarExercicioModal({{ $exercicio->id }}, {!! htmlspecialchars(json_encode($exercicio->nome_exercicio), ENT_QUOTES) !!}, {{ $exercicio->series }}, {{ $exercicio->repeticoes }}, {!! htmlspecialchars(json_encode($exercicio->peso), ENT_QUOTES) !!}, {!! htmlspecialchars(json_encode($exercicio->observacoes), ENT_QUOTES) !!}, {!! htmlspecialchars(json_encode($exercicio->video ? asset('storage/' . $exercicio->video) : ''), ENT_QUOTES) !!})">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="btn-delete-ex" onclick="deletarExercicio({{ $exercicio->id }})" title="Remover">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <button class="btn-add-ex" onclick="adicionarExercicio({{ $ficha->id }}, '{{ $ficha->nivel ?? 'iniciante' }}', '{{ $ficha->divisao ?? '' }}')">
                            <i class="fas fa-plus"></i> ADICIONAR EXERCÍCIO
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- MODAL NOVA FICHA -->
    <div id="modalNovaFicha" class="modal-overlay">
        <div class="modal-content">
            <h2><i class="fas fa-plus-circle"></i> NOVA FICHA</h2>

            <form action="{{ route('fichas-treino.criar') }}" method="POST">
                @csrf
                <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                <input type="hidden" name="nivel" id="inputNivel" value="iniciante">

                <div class="form-group">
                    <label>Nível da Ficha</label>
                    <div class="nivel-toggle">
                        <button type="button" class="nivel-btn ativo" data-nivel="iniciante" onclick="selecionarNivel('iniciante')">
                            <i class="fas fa-seedling"></i> INICIANTE
                        </button>
                        <button type="button" class="nivel-btn" data-nivel="avancado" onclick="selecionarNivel('avancado')">
                            <i class="fas fa-fire"></i> AVANÇADO
                        </button>
                    </div>
                </div>

                <div class="form-group" id="grupoDivisao" style="display:none">
                    <label>Divisão de Treino</label>
                    <select name="divisao" id="selectDivisao" onchange="sugerirNomeTreino()">
                        <option value="">Selecione a divisão</option>
                        <option value="costas_biceps">Costas e Bíceps</option>
                        <option value="peito_ombro_triceps">Peito, Ombro e Tríceps</option>
                        <option value="pernas_gluteos">Pernas e Glúteos</option>
                        <option value="abdomen_core">Abdômen e Core</option>
                        <option value="full_body">Corpo Inteiro</option>
                        <option value="cardio">Cardio e Aeróbico</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Dia da Semana</label>
                    <select name="dia_semana" required>
                        <option value="">Selecione um dia</option>
                        <option value="0">Domingo</option>
                        <option value="1">Segunda</option>
                        <option value="2">Terça</option>
                        <option value="3">Quarta</option>
                        <option value="4">Quinta</option>
                        <option value="5">Sexta</option>
                        <option value="6">Sábado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nome do Treino</label>
                    <input type="text" id="inputNomeTreinoNovo" name="nome_treino" placeholder="Ex: Peito e Costas" required>
                </div>

                <div class="form-group">
                    <label>Observações</label>
                    <textarea name="observacoes" placeholder="Aquecimento, alongamento, etc"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="fecharModalNovaFicha()">CANCELAR</button>
                    <button type="submit" class="btn-submit">CRIAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR FICHA -->
    <div id="modalEditarFicha" class="modal-overlay">
        <div class="modal-content">
            <h2><i class="fas fa-edit"></i> EDITAR FICHA</h2>
            
            <form id="formEditarFicha" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Nome do Treino</label>
                    <input type="text" id="inputNomeTreino" name="nome_treino" required>
                </div>

                <div class="form-group">
                    <label>Observações</label>
                    <textarea id="inputObservacoes" name="observacoes"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="fecharModalEditarFicha()">CANCELAR</button>
                    <button type="submit" class="btn-submit">SALVAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ADICIONAR EXERCÍCIO -->
    <div id="modalAdicionarExercicio" class="modal-overlay">
        <div class="modal-content">
            <h2><i class="fas fa-dumbbell"></i> ADICIONAR EXERCÍCIO</h2>

            <form id="formAdicionarExercicio" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="nome_exercicio" id="hiddenNomeExercicio">

                <div class="form-group">
                    <label>Nome do Exercício</label>

                    <!-- Estado: nenhum exercício escolhido -->
                    <div id="exercicioPicker">
                        <div class="exercicio-search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text"
                                   id="filtroExercicio"
                                   placeholder="Buscar exercício..."
                                   autocomplete="off"
                                   oninput="filtrarExercicios(this.value)">
                        </div>
                        <div id="listaExercicios" class="lista-exercicios"></div>
                    </div>

                    <!-- Estado: exercício escolhido -->
                    <div id="exercicioEscolhido" class="exercicio-escolhido" style="display:none">
                        <div style="flex:1">
                            <div class="exercicio-escolhido-nome" id="nomeExercicioEscolhido"></div>
                            <div class="exercicio-escolhido-grupo" id="grupoExercicioEscolhido"></div>
                        </div>
                        <button type="button" class="btn-trocar-exercicio" onclick="trocarExercicio()">
                            <i class="fas fa-exchange-alt"></i> Trocar
                        </button>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Séries</label>
                        <input type="number" name="series" min="1" placeholder="3" required>
                    </div>
                    <div class="form-group">
                        <label>Repetições</label>
                        <input type="number" name="repeticoes" min="1" placeholder="10" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" name="peso" min="0" step="0.5" placeholder="80">
                </div>

                <div class="form-group">
                    <label>Observações / Técnica</label>
                    <textarea id="obsExercicio" name="observacoes" placeholder="Técnica, cuidados, etc"></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-video"></i> Vídeo demonstrativo (máx. 15s)</label>
                    <input type="file" name="video" accept="video/*" onchange="validarVideo15s(this)">
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="fecharModalAdicionarExercicio()">CANCELAR</button>
                    <button type="submit" id="btnAdicionarExercicio" class="btn-submit" disabled style="opacity:.5;cursor:not-allowed">ADICIONAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR EXERCÍCIO -->
    <div id="modalEditarExercicio" class="modal-overlay">
        <div class="modal-content">
            <h2><i class="fas fa-pen"></i> EDITAR EXERCÍCIO</h2>

            <form id="formEditarExercicio" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Nome do Exercício</label>
                    <input type="text" name="nome_exercicio" id="editNomeExercicio" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Séries</label>
                        <input type="number" name="series" id="editSeries" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Repetições</label>
                        <input type="number" name="repeticoes" id="editReps" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" name="peso" id="editPeso" min="0" step="0.5">
                </div>

                <div class="form-group">
                    <label>Observações / Técnica</label>
                    <textarea name="observacoes" id="editObs"></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-video"></i> Vídeo demonstrativo (máx. 15s)</label>
                    <div id="editVideoAtual" style="display:none; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
                        <button type="button" class="btn-cancel" style="padding:8px 12px;" onclick="abrirVideo(editVideoUrlAtual)"><i class="fas fa-play"></i> Ver vídeo atual</button>
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="remover_video" value="1"> Remover vídeo
                        </label>
                    </div>
                    <input type="file" name="video" accept="video/*" onchange="validarVideo15s(this)">
                    <small style="color:var(--text-muted);">Envie um arquivo para substituir o atual.</small>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="fecharModalEditarExercicio()">CANCELAR</button>
                    <button type="submit" class="btn-submit">SALVAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL VÍDEO -->
    <div id="videoModal" onclick="if(event.target===this)fecharVideo()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:99999; justify-content:center; align-items:center; padding:20px;">
        <div style="position:relative; max-width:520px; width:100%;">
            <button onclick="fecharVideo()" style="position:absolute; top:-38px; right:0; background:none; border:none; color:#fff; font-size:1.4rem; cursor:pointer;">✕</button>
            <video id="videoPlayer" controls playsinline style="width:100%; border-radius:14px; background:#000;"></video>
        </div>
    </div>

    <script>
        // ─── DADOS DOS EXERCÍCIOS ───────────────────────────────────────────
        const TODOS_EXERCICIOS = {!! json_encode($exerciciosData, JSON_UNESCAPED_UNICODE) !!};

        const DIVISOES_NOMES = {
            'costas_biceps':       'Costas e Bíceps',
            'peito_ombro_triceps': 'Peito, Ombro e Tríceps',
            'pernas_gluteos':      'Pernas e Glúteos',
            'abdomen_core':        'Abdômen e Core',
            'full_body':           'Corpo Inteiro',
            'cardio':              'Cardio e Aeróbico',
        };

        // Estado do picker de exercício
        let exerciciosFiltradosAtual = [];
        let nivelFichaAtual = 'iniciante';
        let divisaoFichaAtual = '';
        let obsEditadaManualmente = false;

        // ─── MODAIS ─────────────────────────────────────────────────────────
        const modalNovaFicha          = document.getElementById('modalNovaFicha');
        const modalEditarFicha        = document.getElementById('modalEditarFicha');
        const modalAdicionarExercicio = document.getElementById('modalAdicionarExercicio');

        function abrirModalNovaFicha() {
            selecionarNivel('iniciante');
            modalNovaFicha.classList.add('active');
        }

        function fecharModalNovaFicha() {
            modalNovaFicha.classList.remove('active');
        }

        function editarFicha(fichaId) {
            const form = document.getElementById('formEditarFicha');
            form.action = `/personal/fichas-treino/${fichaId}/editar`;
            modalEditarFicha.classList.add('active');
        }

        function fecharModalEditarFicha() {
            modalEditarFicha.classList.remove('active');
        }

        function deletarFicha(fichaId) {
            if (!confirm('Tem certeza que deseja deletar esta ficha? Todos os exercícios serão removidos!')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/personal/fichas-treino/${fichaId}`;
            form.innerHTML = '@csrf @method("DELETE")';
            document.body.appendChild(form);
            form.submit();
        }

        function adicionarExercicio(fichaId, nivel = 'iniciante', divisao = '') {
            nivelFichaAtual    = nivel;
            divisaoFichaAtual  = divisao;
            obsEditadaManualmente = false;

            const form = document.getElementById('formAdicionarExercicio');
            form.action = `/personal/fichas-treino/${fichaId}/exercicio`;

            // Reset do picker
            trocarExercicio();
            document.getElementById('filtroExercicio').value = '';
            renderizarExercicios('');

            modalAdicionarExercicio.classList.add('active');
            setTimeout(() => document.getElementById('filtroExercicio').focus(), 100);
        }

        function fecharModalAdicionarExercicio() {
            modalAdicionarExercicio.classList.remove('active');
            document.getElementById('formAdicionarExercicio').reset();
            trocarExercicio();
        }

        function deletarExercicio(exercicioId) {
            if (!confirm('Deletar este exercício?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/personal/fichas-treino/exercicio/${exercicioId}`;
            form.innerHTML = '@csrf @method("DELETE")';
            document.body.appendChild(form);
            form.submit();
        }

        // ─── EDITAR EXERCÍCIO ───────────────────────────────────────────────
        let editVideoUrlAtual = '';
        function editarExercicioModal(id, nome, series, reps, peso, obs, videoUrl) {
            const form = document.getElementById('formEditarExercicio');
            form.action = `/personal/fichas-treino/exercicio/${id}`;
            document.getElementById('editNomeExercicio').value = nome || '';
            document.getElementById('editSeries').value = series || '';
            document.getElementById('editReps').value = reps || '';
            document.getElementById('editPeso').value = (peso === null || peso === undefined) ? '' : peso;
            document.getElementById('editObs').value = obs || '';

            const chk = form.querySelector('input[name="remover_video"]');
            if (chk) chk.checked = false;
            const fileInput = form.querySelector('input[name="video"]');
            if (fileInput) fileInput.value = '';

            editVideoUrlAtual = videoUrl || '';
            document.getElementById('editVideoAtual').style.display = editVideoUrlAtual ? 'flex' : 'none';

            document.getElementById('modalEditarExercicio').classList.add('active');
        }
        function fecharModalEditarExercicio() {
            document.getElementById('modalEditarExercicio').classList.remove('active');
        }

        // ─── VÍDEO DEMONSTRATIVO ────────────────────────────────────────────
        function validarVideo15s(input) {
            const file = input.files[0];
            if (!file) return;
            const v = document.createElement('video');
            v.preload = 'metadata';
            v.onloadedmetadata = function () {
                window.URL.revokeObjectURL(v.src);
                if (v.duration > 15.5) {
                    alert('O vídeo deve ter no máximo 15 segundos. O selecionado tem ' + Math.round(v.duration) + 's.');
                    input.value = '';
                }
            };
            v.src = window.URL.createObjectURL(file);
        }
        function abrirVideo(url) {
            if (!url) return;
            document.getElementById('videoPlayer').src = url;
            document.getElementById('videoModal').style.display = 'flex';
        }
        function fecharVideo() {
            const p = document.getElementById('videoPlayer');
            p.pause(); p.src = '';
            document.getElementById('videoModal').style.display = 'none';
        }

        // ─── NOVA FICHA: NÍVEL / DIVISÃO ────────────────────────────────────
        function selecionarNivel(nivel) {
            document.querySelectorAll('.nivel-btn').forEach(b => b.classList.remove('ativo'));
            document.querySelector(`.nivel-btn[data-nivel="${nivel}"]`).classList.add('ativo');
            document.getElementById('inputNivel').value = nivel;

            const grupoDivisao  = document.getElementById('grupoDivisao');
            const selectDivisao = document.getElementById('selectDivisao');

            if (nivel === 'avancado') {
                grupoDivisao.style.display = 'block';
                selectDivisao.setAttribute('required', 'required');
            } else {
                grupoDivisao.style.display = 'none';
                selectDivisao.removeAttribute('required');
                selectDivisao.value = '';
                document.getElementById('inputNomeTreinoNovo').value = '';
            }
        }

        function sugerirNomeTreino() {
            const divisao = document.getElementById('selectDivisao').value;
            const nomeInput = document.getElementById('inputNomeTreinoNovo');
            if (divisao && DIVISOES_NOMES[divisao]) {
                nomeInput.value = DIVISOES_NOMES[divisao];
            }
        }

        // ─── PICKER DE EXERCÍCIO ─────────────────────────────────────────────
        function getExerciciosFiltradosPorFicha() {
            if (nivelFichaAtual === 'iniciante' || !divisaoFichaAtual) {
                return TODOS_EXERCICIOS;
            }
            return TODOS_EXERCICIOS.filter(e => e.divisoes.includes(divisaoFichaAtual));
        }

        function filtrarExercicios(termo) {
            renderizarExercicios(termo);
        }

        function renderizarExercicios(termo) {
            const lista = document.getElementById('listaExercicios');
            const base  = getExerciciosFiltradosPorFicha();
            const t     = termo.trim().toLowerCase();
            const filtrados = t
                ? base.filter(e =>
                    e.nome.toLowerCase().includes(t) ||
                    e.grupo.toLowerCase().includes(t))
                : base;

            exerciciosFiltradosAtual = filtrados;

            if (filtrados.length === 0) {
                let html = `<div class="exercicio-vazio">Nenhum exercício encontrado</div>`;
                if (termo.trim()) {
                    html += `<div class="exercicio-personalizado" onclick="selecionarPersonalizado('${escapeHtml(termo.trim())}')">
                        <i class="fas fa-plus-circle"></i> Usar "<strong>${escapeHtml(termo.trim())}</strong>" como exercício personalizado
                    </div>`;
                }
                lista.innerHTML = html;
                return;
            }

            lista.innerHTML = filtrados.map((e, i) =>
                `<div class="exercicio-item" onclick="selecionarExercicio(${i})">
                    <span class="exercicio-item-nome">${escapeHtml(e.nome)}</span>
                    <span class="exercicio-item-grupo">${escapeHtml(e.grupo)}</span>
                </div>`
            ).join('');
        }

        function selecionarExercicio(indexNaLista) {
            const exercicio = exerciciosFiltradosAtual[indexNaLista];
            if (!exercicio) return;

            document.getElementById('hiddenNomeExercicio').value   = exercicio.nome;
            document.getElementById('nomeExercicioEscolhido').textContent  = exercicio.nome;
            document.getElementById('grupoExercicioEscolhido').textContent = exercicio.grupo;

            if (!obsEditadaManualmente) {
                document.getElementById('obsExercicio').value = exercicio.observacao;
            }

            document.getElementById('exercicioPicker').style.display    = 'none';
            document.getElementById('exercicioEscolhido').style.display  = 'flex';

            const btn = document.getElementById('btnAdicionarExercicio');
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor  = 'pointer';
        }

        function selecionarPersonalizado(nome) {
            document.getElementById('hiddenNomeExercicio').value           = nome;
            document.getElementById('nomeExercicioEscolhido').textContent  = nome;
            document.getElementById('grupoExercicioEscolhido').textContent = 'Personalizado';

            document.getElementById('exercicioPicker').style.display   = 'none';
            document.getElementById('exercicioEscolhido').style.display = 'flex';

            const btn = document.getElementById('btnAdicionarExercicio');
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor  = 'pointer';
        }

        function trocarExercicio() {
            document.getElementById('hiddenNomeExercicio').value           = '';
            document.getElementById('nomeExercicioEscolhido').textContent  = '';
            document.getElementById('grupoExercicioEscolhido').textContent = '';

            document.getElementById('exercicioPicker').style.display   = 'block';
            document.getElementById('exercicioEscolhido').style.display = 'none';

            const btn = document.getElementById('btnAdicionarExercicio');
            btn.disabled = true;
            btn.style.opacity = '.5';
            btn.style.cursor  = 'not-allowed';

            renderizarExercicios(document.getElementById('filtroExercicio').value);
        }

        function escapeHtml(str) {
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // Marcar observação como editada manualmente se o usuário digitar
        document.getElementById('obsExercicio').addEventListener('input', () => {
            obsEditadaManualmente = true;
        });

        // ─── EVENTOS ────────────────────────────────────────────────────────
        document.getElementById('btnNovaFicha').addEventListener('click', abrirModalNovaFicha);

        window.addEventListener('click', (e) => {
            if (e.target === modalNovaFicha)          fecharModalNovaFicha();
            if (e.target === modalEditarFicha)        fecharModalEditarFicha();
            if (e.target === modalAdicionarExercicio) fecharModalAdicionarExercicio();
        });
    </script>

</body>

</html>
