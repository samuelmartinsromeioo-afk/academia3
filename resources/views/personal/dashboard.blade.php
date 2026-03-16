<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ $personal->nome }}</title>
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

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .menu-container {
            position: relative;
        }

        .dots-btn {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--primary);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }

        .dots-btn:hover {
            background: var(--primary);
            color: #000;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 50px;
            left: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 260px;
            z-index: 1000;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        }

        .menu-rating-header {
            padding: 20px;
            background: rgba(212, 255, 0, 0.05);
            border-bottom: 1px solid var(--border);
            text-align: center;
        }

        .stars-row {
            color: var(--primary);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .rating-number {
            font-size: 1.5rem;
            font-weight: 900;
            display: block;
            color: #fff;
        }

        .rating-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .dropdown-menu button:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--primary);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar-img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            object-fit: cover;
            background: #222;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .nav-link {
            color: var(--text-main);
            text-decoration: none;
            background: var(--card-bg);
            padding: 8px 15px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 0.8rem;
            transition: 0.3s;
        }

        .nav-link:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .agenda-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .calendar-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            border: 1px solid var(--border);
        }

        .week-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            text-align: center;
        }

        .day-col {
            background: rgba(255, 255, 255, 0.02);
            padding: 15px 5px;
            border-radius: 12px;
            border: 1px solid var(--border);
            min-height: 100px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .day-col:hover {
            background: rgba(212, 255, 0, 0.05);
            border-color: rgba(212, 255, 0, 0.3);
        }

        .day-col.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #000;
        }

        .day-col.active .day-name,
        .day-col.active .day-number {
            color: #000;
        }

        .has-event::after {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--primary);
            border-radius: 50%;
            position: absolute;
            bottom: 12px;
            box-shadow: 0 0 10px var(--primary);
        }

        .agenda-notes {
            background: linear-gradient(135deg, #16181d 0%, #0a0b0d 100%);
            padding: 25px;
            border-radius: 24px;
            border: 1px solid var(--border);
            min-height: 400px;
        }

        .summary-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 15px;
            margin-top: 12px;
            border-left: 4px solid var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-delete-agenda {
            background: rgba(255, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(255, 68, 68, 0.2);
            width: 35px;
            height: 35px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-delete-agenda:hover:not(:disabled) {
            background: var(--error);
            color: #fff;
        }

        .btn-delete-agenda:disabled {
            opacity: 0.2;
            cursor: not-allowed;
            filter: grayscale(1);
        }

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

        .modal-content {
            background: var(--card-bg);
            max-width: 750px;
            margin: auto;
            padding: 35px;
            border-radius: 28px;
            border: 1px solid var(--border);
        }

        .finance-card {
            background: rgba(212, 255, 0, 0.05);
            border: 1px solid var(--primary);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .finance-value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 12px;
            margin-top: 5px;
        }

        .input-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px;
            color: #fff;
            outline: none;
            font-size: 0.9rem;
        }

        .input-wrapper i {
            color: var(--primary);
            width: 20px;
            text-align: center;
        }

        label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 800;
            margin-top: 12px;
            display: block;
            letter-spacing: 0.5px;
        }

        .btn-save {
            background: var(--primary);
            color: #000;
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            font-weight: 900;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.3s;
            margin-top: 20px;
        }

        .btn-save:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        /* Scrollbar customizada para a lista de alunos */
        .alunos-list::-webkit-scrollbar {
            width: 6px;
        }
        .alunos-list::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.02);
        }
        .alunos-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <div class="menu-container">
            <button class="dots-btn" id="btnMenu" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="menu-rating-header">
                    @php
                    $media = (float)($personal->media_avaliacao ?? 0);
                    $totalAvals = $personal->avaliacoes ? $personal->avaliacoes->count() : 0;
                    @endphp
                    <span class="rating-number">{{ number_format($media, 1) }}</span>
                    <div class="stars-row">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <=$media) <i class="fas fa-star"></i>
                            @elseif ($i - 0.5 <= $media) <i class="fas fa-star-half-alt"></i>
                                @else <i class="far fa-star"></i>
                                @endif
                                @endfor
                    </div>
                    <span class="rating-label">{{ $totalAvals }} Avaliações</span>
                </div>
                <button type="button" id="btnOpenUpdate"><i class="fas fa-user-edit"></i> Meu Perfil</button>
                <button type="button" id="btnOpenAlunos"><i class="fas fa-users"></i> Meus Alunos</button>
                <button type="button" id="btnOpenFinance"><i class="fas fa-wallet" style="color: var(--success)"></i> Minhas Finanças</button>
                <form action="{{ route('login.logout') }}" method="POST"> @csrf <button type="submit" style="color: var(--error)"><i class="fas fa-power-off"></i> Sair</button></form>
            </div>
        </div>
        <div class="profile-header">
            <img src="{{ $personal->foto ? asset('storage/' . $personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" class="avatar-img">
            <span style="font-weight: 700; font-size: 0.9rem;">{{ $personal->nome }}</span>
        </div>
    </div>

    <div class="container">
        @if(session('success'))
        <div style="background: rgba(0, 255, 136, 0.1); color: var(--success); padding: 15px; border-radius: 12px; border: 1px solid var(--success); margin-bottom: 20px; font-size: 0.9rem;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div style="background: rgba(255, 68, 68, 0.1); color: var(--error); padding: 15px; border-radius: 12px; border: 1px solid var(--error); margin-bottom: 20px; font-size: 0.9rem;">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
        @endif

        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <h1 style="margin:0; font-size: 2rem; font-weight: 900;">Minha Agenda</h1>
            <button id="btnOpenBloqueio" class="btn-save" style="width: auto; padding: 12px 25px; margin: 0; font-size: 0.75rem;">
                <i class="fas fa-calendar-plus"></i> BLOQUEAR HORÁRIO
            </button>
        </header>

        <div class="calendar-nav">
            <a href="?data={{ $inicioSemana->copy()->subWeek()->format('Y-m-d') }}" class="nav-link"><i class="fas fa-chevron-left"></i> Semana Anterior</a>
            <span style="font-weight: 800; font-size: 0.9rem; color: var(--primary);">{{ $inicioSemana->format('d/m') }} até {{ $inicioSemana->copy()->endOfWeek()->format('d/m') }}</span>
            <a href="?data={{ $inicioSemana->copy()->addWeek()->format('Y-m-d') }}" class="nav-link">Próxima Semana <i class="fas fa-chevron-right"></i></a>
        </div>

        <div class="agenda-section">
            <div class="calendar-card">
                <div class="week-grid">
                    @php
                    $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                    $diasComEvento = \App\Models\Agenda::where('personal_id', session('personal_id'))
                    ->where('cancelado', false)
                    ->whereBetween('data', [$inicioSemana->format('Y-m-d'), $inicioSemana->copy()->endOfWeek()->format('Y-m-d')])
                    ->pluck('data')->toArray();
                    @endphp
                    @foreach($diasSemana as $index => $nomeDia)
                    @php
                    $dataLoop = $inicioSemana->copy()->addDays($index);
                    $dataStr = $dataLoop->format('Y-m-d');
                    @endphp
                    <div class="day-col {{ now()->isSameDay($dataLoop) ? 'today active' : '' }} {{ in_array($dataStr, $diasComEvento) ? 'has-event' : '' }}"
                        onclick="selectDay('{{ $dataStr }}', this)">
                        <span class="day-name">{{ $nomeDia }}</span>
                        <span class="day-number">{{ $dataLoop->day }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="agenda-notes">
                <h3 id="summary-title" style="color: var(--primary); font-size: 0.8rem; margin: 0 0 15px 0; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-list-ul"></i> Compromissos do Dia
                </h3>
                <div id="summary-content"></div>
            </div>
        </div>
    </div>

    <input type="hidden" name="_token" value="{{ csrf_token() }}">

    <div id="modalAlunos" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-users"></i> MEUS ALUNOS
            </h2>
            
            <div class="alunos-list" style="display: flex; flex-direction: column; gap: 15px; max-height: 400px; overflow-y: auto; padding-right: 10px;">
                @php
                    // Busca todos os agendamentos do personal, traz os clientes junto e filtra para não repetir alunos
                    $meusAlunos = \App\Models\Agenda::with('cliente')
                        ->where('personal_id', session('personal_id'))
                        ->where('cancelado', false)
                        ->get()
                        ->filter(function($agenda) {
                            return $agenda->cliente != null; // Garante que tem cliente vinculado
                        })
                        ->unique('cliente_id');
                @endphp

                @forelse($meusAlunos as $agendamento)
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); padding: 15px; border-radius: 16px; display: flex; align-items: center; gap: 15px; transition: 0.3s;">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($agendamento->cliente->nome ?? $agendamento->cliente->name ?? 'Aluno') }}&background=d4ff00&color=000" 
                             style="width: 50px; height: 50px; border-radius: 50%;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 5px 0; font-size: 1.1rem; color: #fff;">{{ $agendamento->cliente->nome ?? $agendamento->cliente->name ?? 'Aluno Sem Nome' }}</h3>
                            <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted);">
                                <i class="fas fa-bullseye" style="color: var(--primary);"></i> 
                                {{ $agendamento->cliente->resumo_objetivo ?? 'Objetivo não informado' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 40px 20px; opacity: 0.5;">
                        <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 15px; color: var(--text-muted);"></i>
                        <p style="color: var(--text-muted);">Você ainda não possui alunos vinculados.</p>
                    </div>
                @endforelse
            </div>
            
            <button type="button" onclick="closeModalAlunos()" class="btn-save">Fechar Relatório</button>
        </div>
    </div>

    <div id="modalFinance" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900; text-align: center;">FINANCEIRO</h2>
            <div class="finance-card">
                <span style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase;">Previsão de Ganhos (Mês Atual)</span>
                @php
                $agendasMes = \App\Models\Agenda::where('personal_id', session('personal_id'))
                ->where('cancelado', false)
                ->whereMonth('data', now()->month)
                ->whereYear('data', now()->year)
                ->get();
                $faturamento = 0;
                foreach($agendasMes as $ag) {
                $start = \Carbon\Carbon::parse($ag->hora_inicio);
                $end = \Carbon\Carbon::parse($ag->hora_fim);
                $horas = $start->diffInMinutes($end) / 60;
                $faturamento += ($horas * ($personal->valor_secao ?? 0));
                }
                @endphp
                <span class="finance-value">R$ {{ number_format($faturamento, 2, ',', '.') }}</span>
            </div>
            <button type="button" onclick="closeModalFinance()" class="btn-save">Fechar Relatório</button>
        </div>
    </div>

    <div id="modalUpdate" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">ATUALIZAR MEUS DADOS</h2>
            <form action="{{ route('personal.update', $personal->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <div class="full-width">
                        <label>Foto de Perfil</label>
                        <div class="input-wrapper"><i class="fas fa-camera"></i><input type="file" name="foto" accept="image/*"></div>
                    </div>
                    <div>
                        <label>Nome Completo</label>
                        <div class="input-wrapper"><i class="fas fa-user"></i><input type="text" name="nome" value="{{ $personal->nome }}" required></div>
                    </div>
                    <div>
                        <label>Valor por Seção (R$)</label>
                        <div class="input-wrapper"><i class="fas fa-dollar-sign"></i><input type="number" step="0.01" name="valor_secao" value="{{ $personal->valor_secao }}"></div>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="closeModalUpdate()" class="btn-save btn-cancel">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalHorario" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color: var(--primary); font-size: 1.4rem; margin-top: 0; font-weight: 900;">BLOQUEAR HORÁRIO</h2>
            <form action="{{ route('personal.storeHorario') }}" method="POST">
                @csrf
                <label>Data</label>
                <div class="input-wrapper"><i class="fas fa-calendar"></i><input type="date" name="data" id="modal_data" required></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div><label>Início</label>
                        <div class="input-wrapper"><i class="fas fa-clock"></i><input type="time" name="hora_inicio" required></div>
                    </div>
                    <div><label>Fim</label>
                        <div class="input-wrapper"><i class="fas fa-clock"></i><input type="time" name="hora_fim" required></div>
                    </div>
                </div>
                <label>Motivo</label>
                <div class="input-wrapper"><i class="fas fa-pen"></i><input type="text" name="descricao" placeholder="Ex: Almoço / Aula Particular"></div>
                <button type="submit" class="btn-save">Confirmar Bloqueio</button>
                <button type="button" onclick="closeModalHorario()" class="btn-save btn-cancel">Voltar</button>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const m = document.getElementById('dropdownMenu');
            m.style.display = m.style.display === 'block' ? 'none' : 'block';
        }

        const modalUpdate = document.getElementById('modalUpdate');
        const modalHorario = document.getElementById('modalHorario');
        const modalFinance = document.getElementById('modalFinance');
        const modalAlunos = document.getElementById('modalAlunos');

        document.getElementById('btnOpenUpdate').onclick = () => {
            modalUpdate.style.display = 'block';
            toggleMenu();
        };
        document.getElementById('btnOpenFinance').onclick = () => {
            modalFinance.style.display = 'block';
            toggleMenu();
        };

        // ALTERAÇÃO AQUI: Agora ele abre o modal em vez de ir para outra página
        document.getElementById('btnOpenAlunos').onclick = () => {
            modalAlunos.style.display = 'block';
            toggleMenu();
        };

        document.getElementById('btnOpenBloqueio').onclick = () => {
            document.getElementById('modal_data').value = new Date().toISOString().split('T')[0];
            modalHorario.style.display = 'block';
        };

        function closeModalUpdate() {
            modalUpdate.style.display = 'none';
        }

        function closeModalHorario() {
            modalHorario.style.display = 'none';
        }

        function closeModalFinance() {
            modalFinance.style.display = 'none';
        }

        // Função para fechar o Modal de Alunos
        function closeModalAlunos() {
            modalAlunos.style.display = 'none';
        }

        function cancelarComJustificativa(id) {
            const justificativa = prompt("Por favor, digite a justificativa para o cancelamento (mínimo 10 caracteres):");

            if (justificativa && justificativa.length >= 10) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/agenda/cancelar/${id}`;
                const token = document.querySelector('input[name="_token"]').value;

                form.innerHTML = `
                <input type="hidden" name="_token" value="${token}">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="justificativa" value="${justificativa}">
            `;
                document.body.appendChild(form);
                form.submit();
            } else if (justificativa) {
                alert("A justificativa deve ter pelo menos 10 caracteres.");
            }
        }

        function selectDay(dateStr, element) {
            document.querySelectorAll('.day-col').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            const content = document.getElementById('summary-content');
            const parts = dateStr.split('-');
            document.getElementById('summary-title').innerHTML = `<i class="fas fa-calendar-day"></i> AGENDA (${parts[2]}/${parts[1]})`;
            content.innerHTML = `<p style="text-align:center; color:var(--text-muted); padding:30px; font-size:0.8rem;">Buscando...</p>`;

            fetch(`/personal/agenda/${dateStr}`)
                .then(r => r.json())
                .then(dados => {
                    console.log("Dados recebidos do servidor:", dados); 
                    content.innerHTML = "";

                    if (!dados || dados.length === 0) {
                        content.innerHTML = `<div style="text-align:center; padding-top:40px; opacity:0.3;"><i class="fas fa-coffee" style="font-size:2rem;"></i><p>Nada para hoje! Horário livre.</p></div>`;
                        return;
                    }

                    dados.forEach(item => {
                        const agora = new Date();
                        const dataAula = new Date(item.data + 'T' + item.hora_inicio);
                        const diffHoras = (dataAula - agora) / (1000 * 60 * 60);
                        const podeCancelar = diffHoras >= 24;

                        let nomeExibicao = "";
                        let icone = '<i class="fas fa-lock" style="opacity:0.5"></i>';

                        if (item.cliente && (item.cliente.nome || item.cliente.name)) {
                            nomeExibicao = `<strong>${item.cliente.nome || item.cliente.name}</strong>`;
                            icone = '<i class="fas fa-user-circle" style="color:var(--primary)"></i>';
                        } else {
                            nomeExibicao = item.descricao || "Horário Bloqueado";
                        }

                        content.innerHTML += `
                        <div class="summary-item">
                            <div>
                                <strong style="color:var(--primary);">${item.hora_inicio.substring(0,5)} - ${item.hora_fim.substring(0,5)}</strong>
                                <p style="margin:5px 0 0 0; font-size:0.85rem;">${icone} ${nomeExibicao}</p>
                                <small style="color:var(--text-muted); font-size:0.7rem; display:block; margin-top:5px;">
                                    <i class="fas fa-dumbbell"></i>
                                    ${item.academia ? (item.academia.nome || item.academia.name) : 'Local não informado'}
                                </small>
                            </div>
                            <button type="button" class="btn-delete-agenda" 
                                onclick="cancelarComJustificativa(${item.id})" 
                                ${!podeCancelar ? 'disabled title="Mínimo 24h de antecedência"' : ''}>
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>`;
                    });
                })
                .catch(err => {
                    console.error("Erro na requisição:", err);
                    content.innerHTML = `<p style="color:var(--error); text-align:center;">Erro ao carregar dados.</p>`;
                });
        }

        window.onclick = (e) => {
            if (e.target == modalUpdate) closeModalUpdate();
            if (e.target == modalHorario) closeModalHorario();
            if (e.target == modalFinance) closeModalFinance();
            if (e.target == modalAlunos) closeModalAlunos(); // Fecha se clicar fora do modal de alunos
        }

        window.onload = () => {
            const todayStr = new Date().toISOString().split('T')[0];
            const todayElement = document.querySelector(`.day-col[onclick*="${todayStr}"]`);

            if (todayElement) {
                todayElement.click();
            } else {
                const firstDay = document.querySelector('.day-col');
                if (firstDay) firstDay.click();
            }
        }
    </script>
</body>

</html>