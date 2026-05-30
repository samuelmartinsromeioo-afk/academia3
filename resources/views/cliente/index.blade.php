<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - {{ $cliente->nome }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #d4ff00; 
            --bg-dark: #0a0b0d; 
            --card-bg: #16181d; 
            --text-main: #ffffff; 
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
            --success: #28a745;
            --error: #ff4444;
        }

        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; padding: 0; overflow-x: hidden; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .menu-container { position: relative; }
        .dots-btn { background: var(--card-bg); border: 1px solid var(--border); color: var(--primary); width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .dots-btn:hover { background: var(--primary); color: #000; }
        .dropdown-menu { display: none; position: absolute; top: 50px; left: 0; background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; width: 220px; z-index: 1000; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.6); }
        .dropdown-menu button, .dropdown-menu a { display: flex; align-items: center; gap: 12px; padding: 15px 20px; color: #fff; text-decoration: none; font-size: 14px; width: 100%; text-align: left; background: none; border: none; cursor: pointer; transition: 0.2s; }
        .dropdown-menu button:hover, .dropdown-menu a:hover { background: rgba(255,255,255,0.05); color: var(--primary); }
        .profile-header { display: flex; align-items: center; gap: 20px; }
        .avatar-img { width: 45px; height: 45px; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 20px; border: 1px solid var(--border); text-align: center; transition: 0.3s; }
        .stat-card:hover { border-color: rgba(212, 255, 0, 0.3); }
        .stat-card i { color: var(--primary); font-size: 1.5rem; margin-bottom: 10px; display: block; }
        .stat-card span { display: block; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 800; }
        .stat-card h2 { margin: 5px 0 0; font-size: 1.5rem; }
        .list-item { background: var(--card-bg); padding: 20px; border-radius: 15px; border-left: 4px solid var(--primary); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-right: 1px solid var(--border); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .badge-status { background: rgba(212, 255, 0, 0.1); color: var(--primary); padding: 5px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .personal-card { text-align: left; position: relative; overflow: hidden; }
        .personal-card img { width: 50px; height: 50px; border-radius: 12px; border: 1px solid var(--primary); object-fit: cover; }
        #editFormContainer { display: none; animation: fadeIn 0.4s ease; margin-bottom: 50px; }
        .profile-card { background: var(--card-bg); border-radius: 24px; padding: 35px; border: 1px solid var(--border); position: relative; }
        .close-form { position: absolute; top: 20px; right: 25px; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; transition: 0.2s; }
        .close-form:hover { color: var(--primary); transform: rotate(90deg); }
        .section-title { color: var(--primary); font-size: 0.8rem; margin: 30px 0 15px 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .section-title::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .form-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; }
        .col-6 { grid-column: span 6; }
        .col-3 { grid-column: span 3; }
        .col-2 { grid-column: span 2; }
        .col-4 { grid-column: span 4; }
        label { font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; margin-bottom: 5px; display: block; }
        .input-wrapper { display: flex; align-items: center; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px; padding: 0 12px; }
        .input-wrapper i { color: var(--primary); width: 18px; font-size: 0.9rem; }
        .input-wrapper input, .input-wrapper select, .input-wrapper textarea { flex: 1; background: transparent; border: none; padding: 12px; color: #fff; outline: none; font-size: 0.9rem; font-family: inherit; }
        .input-wrapper textarea { resize: none; height: 80px; }
        .btn-action { background: var(--primary); color: #000; width: 100%; padding: 18px; border-radius: 12px; font-weight: 900; border: none; cursor: pointer; text-transform: uppercase; transition: 0.3s; font-size: 0.8rem; margin-top: 20px; }
        .btn-action:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }
        .btn-action:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(212, 255, 0, 0.15); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1001; display: none; justify-content: center; align-items: center; backdrop-filter: blur(8px); overflow-y: auto; padding: 40px 0; }
        .horario-item { background: var(--input-bg); padding: 15px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .col-3, .col-2, .col-4 { grid-column: span 6; }
        }

        /* Estilos do Calendário */
        #calendarGrid::-webkit-scrollbar { width: 6px; }
        #calendarGrid::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 10px; }
        #calendarGrid::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        .dia-calendario {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.75rem;
            font-weight: 700;
            user-select: none;
        }

        .dia-calendario.outro-mes {
            color: var(--text-muted);
            background: rgba(255,255,255,0.02);
            cursor: not-allowed;
        }

        .dia-calendario.disponivel {
            background: rgba(212, 255, 0, 0.08);
            border-color: rgba(212, 255, 0, 0.2);
            color: var(--primary);
        }

        .dia-calendario.disponivel:hover {
            background: rgba(212, 255, 0, 0.15);
            border-color: var(--primary);
            transform: scale(1.05);
        }

        .dia-calendario.ocupado {
            background: rgba(255, 68, 68, 0.08);
            border-color: rgba(255, 68, 68, 0.2);
            color: #ff6666;
            cursor: not-allowed;
        }

        .dia-calendario.selecionado {
            background: var(--primary);
            border-color: var(--primary);
            color: #000;
            font-weight: 900;
        }

        .pacote-item {
            background: var(--input-bg);
            padding: 12px;
            border-radius: 10px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pacote-item:hover {
            border-color: rgba(212, 255, 0, 0.3);
            background: rgba(212, 255, 0, 0.05);
        }

        .pacote-item.selecionado {
            border-color: var(--primary);
            background: rgba(212, 255, 0, 0.1);
        }

        .pacote-freq { color: #fff; font-weight: 800; font-size: 0.8rem; }
        .pacote-valor { color: var(--primary); font-weight: 900; font-size: 0.9rem; }

        .horario-selecionavel {
            background: var(--input-bg);
            padding: 12px;
            border-radius: 10px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .horario-selecionavel:hover {
            border-color: var(--primary);
            background: rgba(212, 255, 0, 0.05);
            transform: translateX(5px);
        }

        /* Estilos da Paginação */
        .pagination-container { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; margin-bottom: 20px; }
        .pagination-btn { background: var(--input-bg); border: 1px solid var(--border); color: var(--primary); padding: 8px 12px; border-radius: 8px; cursor: pointer; font-weight: 700; transition: 0.2s; font-size: 0.75rem; }
        .pagination-btn:hover:not(:disabled) { background: var(--primary); color: #000; }
        .pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .pagination-btn.active { background: var(--primary); color: #000; }
        .pagination-info { color: var(--text-muted); font-size: 0.75rem; margin: 0 10px; }

        /* ✅ NOVO: ESTILOS DO MODAL DE DETALHES */
        .detalhes-personal-modal {
            width: 95%;
            max-width: 700px;
            border: 1px solid var(--primary);
            max-height: 90vh;
            overflow-y: auto;
        }

        .detalhes-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .detalhes-foto {
            width: 100px;
            height: 100px;
            border-radius: 16px;
            border: 2px solid var(--primary);
            object-fit: cover;
            flex-shrink: 0;
        }

        .detalhes-foto-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 16px;
            border: 2px solid var(--primary);
            background: rgba(212, 255, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 2.5rem;
            flex-shrink: 0;
        }

        .detalhes-info-header h2 {
            color: #fff;
            font-size: 1.5rem;
            margin: 0 0 5px 0;
        }

        .detalhes-info-header p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin: 0 0 10px 0;
        }

        .detalhes-avaliacao {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 5px 12px;
            border-radius: 20px;
            color: gold;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .detalhes-section {
            margin-bottom: 25px;
        }

        .detalhes-section-title {
            color: var(--primary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detalhes-galeria {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .detalhes-galeria img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: 0.3s;
        }

        .detalhes-galeria img:hover {
            border-color: var(--primary);
            transform: scale(1.05);
        }

        .detalhes-academias {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .detalhes-academia-item {
            background: rgba(212, 255, 0, 0.05);
            border: 1px solid rgba(212, 255, 0, 0.2);
            padding: 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detalhes-academia-item i {
            color: var(--primary);
            font-size: 1rem;
        }

        .detalhes-academia-item span {
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .detalhes-resumo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .detalhes-resumo-card {
            background: var(--input-bg);
            border: 1px solid var(--border);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
        }

        .detalhes-resumo-card i {
            color: var(--primary);
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .detalhes-resumo-card .label {
            font-size: 0.6rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .detalhes-resumo-card .valor {
            font-size: 1rem;
            color: #fff;
            font-weight: 800;
        }

        .detalhes-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            margin-top: 25px;
        }

        .detalhes-actions button {
            margin-top: 0 !important;
        }

        @media (max-width: 600px) {
            .detalhes-header { flex-direction: column; text-align: center; }
            .detalhes-galeria { grid-template-columns: repeat(3, 1fr); }
            .detalhes-academias { grid-template-columns: 1fr; }
            .detalhes-resumo-grid { grid-template-columns: 1fr; }
            .detalhes-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

<div class="top-bar">
    <div class="menu-container">
        <button class="dots-btn" onclick="toggleMenu()"><i class="fas fa-bars"></i></button>
        <div class="dropdown-menu" id="dropdownMenu">
            <button type="button" onclick="window.location.href='{{ route('cliente.index') }}'"><i class="fas fa-chart-line"></i> Menu Principal</button>
            <button type="button" onclick="toggleEditForm()"><i class="fas fa-user-edit"></i> Editar Perfil</button>
            <button type="button" onclick="window.location.href='{{ route('fichas-treino.minhas') }}'"><i class="fas fa-dumbbell"></i> Minha Ficha</button>
            <button type="button" onclick="abrirHistoricoModal()"><i class="fas fa-history"></i> Ver Histórico</button>
            <button type="button" onclick="window.location.href='{{ route('mapa.index') }}'"><i class="fas fa-map-marked-alt"></i> Ver Mapa</button>
            <form action="{{ route('login.logout') }}" method="POST">
                @csrf
                <button type="submit" style="color: #ff4444;"><i class="fas fa-power-off"></i> Sair</button>
            </form>
        </div>
    </div>
    <div class="profile-header">
        <span style="font-weight: 700; font-size: 0.85rem;">{{ $cliente->nome }}</span>
        <img src="https://ui-avatars.com/api/?name={{ urlencode($cliente->nome) }}&background=d4ff00&color=000" class="avatar-img">
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div id="avisoSucesso" style="background: rgba(40,167,69,0.2); border: 1px solid var(--success); color: #fff; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
        <script>
            setTimeout(() => {
                const aviso = document.getElementById('avisoSucesso');
                if (aviso) aviso.style.display = 'none';
            }, 7000);
        </script>
    @endif

    @if(session('error'))
        <div id="avisoErro" style="background: rgba(255,68,68,0.2); border: 1px solid var(--error); color: #ff6666; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
            {{ session('error') }}
        </div>
        <script>
            setTimeout(() => {
                const aviso = document.getElementById('avisoErro');
                if (aviso) aviso.style.display = 'none';
            }, 7000);
        </script>
    @endif

    @if(request('payment') === 'cancelled')
        <div id="avisoCancelado" style="background: rgba(255,68,68,0.2); border: 1px solid var(--error); color: #ff6666; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
            <i class="fas fa-times-circle"></i> Pagamento cancelado. Você pode tentar novamente quando quiser.
        </div>
        <script>
            setTimeout(() => {
                const aviso = document.getElementById('avisoCancelado');
                if (aviso) aviso.style.display = 'none';
            }, 7000);
        </script>
    @endif

    <header id="mainHeader" style="margin-bottom: 30px;">
        <h1 style="margin:0; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px;">CENTRAL DO ALUNO</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Bem-vindo de volta, foque nos seus objetivos.</p>
    </header>

    {{-- FORMULÁRIO DE EDIÇÃO --}}
    <div id="editFormContainer" style="display: none;">
        <form action="{{ route('cliente.update', $cliente->id) }}" method="POST" class="profile-card">
            @csrf @method('PUT')
            <i class="fas fa-times close-form" onclick="toggleEditForm()"></i>
            <div class="section-title">Dados de Acesso</div>
            <div class="form-grid">
                <div class="col-3">
                    <label>Nome Completo</label>
                    <div class="input-wrapper"><i class="fas fa-user"></i><input type="text" name="nome" value="{{ $cliente->nome }}" required></div>
                </div>
                <div class="col-3">
                    <label>E-mail</label>
                    <div class="input-wrapper"><i class="fas fa-envelope"></i><input type="email" name="email" value="{{ $cliente->email }}" required></div>
                </div>
                <div class="col-3">
                    <label>Nova Senha (deixe em branco)</label>
                    <div class="input-wrapper"><i class="fas fa-lock"></i><input type="password" name="senha" placeholder="********"></div>
                </div>
                <div class="col-3">
                    <label>Sexo</label>
                    <div class="input-wrapper">
                        <i class="fas fa-venus-mars"></i>
                        <select name="sexo">
                            <option value="masculino" {{ $cliente->sexo == 'masculino' ? 'selected' : '' }}>Masculino</option>
                            <option value="feminino" {{ $cliente->sexo == 'feminino' ? 'selected' : '' }}>Feminino</option>
                            <option value="outro" {{ $cliente->sexo == 'outro' ? 'selected' : '' }}>Outro</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="section-title">Medidas Físicas</div>
            <div class="form-grid">
                <div class="col-3">
                    <label>Peso (kg)</label>
                    <div class="input-wrapper"><i class="fas fa-weight"></i><input type="number" step="0.01" name="peso" value="{{ $cliente->peso }}"></div>
                </div>
                <div class="col-3">
                    <label>Altura (m)</label>
                    <div class="input-wrapper"><i class="fas fa-ruler-vertical"></i><input type="number" step="0.01" name="altura" value="{{ $cliente->altura }}"></div>
                </div>
            </div>
            <div class="section-title">Localização</div>
            <div class="form-grid">
                <div class="col-2">
                    <label>CEP</label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="cep" id="cep" placeholder="00000-000" 
                            oninput="this.value = mascaras.cep(this.value)" maxlength="9" required>
                    </div>
                </div>  
                <div class="col-4">
                    <label>Rua</label>
                    <div class="input-wrapper"><i class="fas fa-road"></i><input type="text" name="rua" value="{{ $cliente->rua }}"></div>
                </div>
                <div class="col-2">
                    <label>Bairro</label>
                    <div class="input-wrapper"><i class="fas fa-home"></i><input type="text" name="bairro" value="{{ $cliente->bairro }}"></div>
                </div>
                <div class="col-2">
                    <label>Cidade</label>
                    <div class="input-wrapper"><i class="fas fa-city"></i><input type="text" name="cidade" value="{{ $cliente->cidade }}"></div>
                </div>
                <div class="col-2">
                    <label>Estado</label>
                    <div class="input-wrapper"><i class="fas fa-map"></i><input type="text" name="estado" value="{{ $cliente->estado }}"></div>
                </div>
                <div class="col-6">
                    <label>Complemento</label>
                    <div class="input-wrapper"><i class="fas fa-info-circle"></i><input type="text" name="complemento" value="{{ $cliente->complemento }}"></div>
                </div>
            </div>
            <button type="submit" class="btn-action">Atualizar Perfil Completo</button>
        </form>
    </div>

    <div id="dashboardSummary">
        {{-- AGENDA COM PAGINAÇÃO --}}
        <div class="section-title">Minha Agenda de Treinos</div>
        <div id="agendaContainer">
            @php
                $inicioSemana = now()->startOfWeek()->format('Y-m-d');
                $fimSemana = now()->endOfWeek()->format('Y-m-d');
                $agendadasSemana = $meusAgendamentos->whereBetween('data', [$inicioSemana, $fimSemana])->values();
            @endphp
            
            @if($agendadasSemana->count() > 0)
                <script>
                    window.agendamentosData = {!! json_encode($agendadasSemana->map(function($a) {
                        return [
                            'personal' => $a->personal->nome ?? 'N/A',
                            'data' => \Carbon\Carbon::parse($a->data)->format('d/m/Y'),
                            'hora' => \Carbon\Carbon::parse($a->hora_inicio)->format('H:i')
                        ];
                    })) !!};
                </script>
                
                <div id="agendaItems" style="min-height: 250px;"></div>

                <div class="pagination-container">
                    <button class="pagination-btn" onclick="irParaPaginaAgenda(1)" id="btnPrimeira">
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button class="pagination-btn" onclick="paginaAnteriorAgenda()" id="btnAnterior">
                        <i class="fas fa-angle-left"></i>
                    </button>
                    <div id="paginasBotoes"></div>
                    <button class="pagination-btn" onclick="proximaPaginaAgenda()" id="btnProxima">
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button class="pagination-btn" onclick="irParaPaginaAgenda(-1)" id="btnUltima">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                    <span class="pagination-info"><span id="paginaAtualInfo">1</span> / <span id="totalPaginasInfo">1</span></span>
                </div>
            @else
                <div class="stat-card" style="padding: 20px; border-style: dashed;">
                    <p style="color: var(--text-muted); margin: 0; font-size: 0.85rem;">Você não possui treinos agendados para esta semana.</p>
                </div>
            @endif
        </div>

        {{-- PERSONALS --}}
        <div class="section-title">Personals Disponíveis</div>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin: -10px 0 15px 0;">
            <i class="fas fa-info-circle" style="color: var(--primary);"></i>
            Você pode contratar um personal com ou sem vínculo com academia.
        </p>

        <div class="dashboard-grid" style="grid-template-columns: repeat(2, 1fr);">
            @foreach($personals as $p)
            <div class="stat-card personal-card" style="position: relative; padding-top: 25px;">
                
                <div onclick="abrirAvaliacao({{ $p->id }}, '{{ addslashes($p->nome) }}')" 
                     style="position: absolute; top: 10px; right: 10px; cursor: pointer; color: gold; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; padding: 4px 10px; background: rgba(255, 215, 0, 0.1); border-radius: 20px; border: 1px solid rgba(255, 215, 0, 0.2); transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                     onmouseover="this.style.background='rgba(255, 215, 0, 0.3)'"
                     onmouseout="this.style.background='rgba(255, 215, 0, 0.1)'"
                     title="Clique para avaliar {{ $p->nome }}">
                    <i class="fas fa-star"></i> 
                    <strong style="color: white; font-size: 0.9rem;">{{ $p->media_avaliacao }}</strong>
                </div>

                <div style="display: flex; align-items: center; gap: 15px;">
                    @if($p->foto)
                        <img src="{{ asset('storage/' . $p->foto) . '?t=' . time() }}" alt="Foto de {{ $p->nome }}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($p->nome) }}&background=000&color=d4ff00" alt="Iniciais de {{ $p->nome }}" style="width: 50px; height: 50px; border-radius: 50%;">
                    @endif

                    <div>
                        <h3 style="margin:0; font-size: 0.9rem; padding-right: 40px;">{{ $p->nome }}</h3>
                        <p style="margin:0; font-size: 0.6rem; color: var(--primary);">Ativo na plataforma</p>
                    </div>
                </div>

                @if($p->fotos && $p->fotos->count() > 0)
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; margin-top: 12px;">
                    @foreach($p->fotos->take(6) as $foto)
                    <img src="{{ asset('storage/' . $foto->path) }}"
                         onclick="abrirDetalhesPersonal({{ $p->id }})"
                         style="width:100%; aspect-ratio:1; object-fit:cover; border-radius:8px; border:1px solid var(--border); cursor:pointer; transition:0.2s;"
                         onmouseover="this.style.borderColor='var(--primary)'"
                         onmouseout="this.style.borderColor='var(--border)'"
                         title="{{ $foto->legenda }}">
                    @endforeach
                </div>
                @if($p->fotos->count() > 6)
                <p style="font-size:0.7rem; color:var(--text-muted); margin:5px 0 0; text-align:center; cursor:pointer;"
                   onclick="abrirDetalhesPersonal({{ $p->id }})">
                    +{{ $p->fotos->count() - 6 }} fotos — ver todas
                </p>
                @endif
                @endif

                {{-- ✅ BOTÃO: VER DETALHES --}}
                <div style="text-align: center; margin-top: 12px;">
                    <button onclick="abrirDetalhesPersonal({{ $p->id }})" class="btn-action" style="padding: 8px 16px; font-size: 0.65rem; margin-top: 0; width: auto; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="fas fa-eye"></i> Ver Detalhes
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ACADEMIAS --}}
        <div class="section-title">Academias Parceiras (Contratar)</div>
        <div id="listaAcademias">
            @forelse($academias as $academia)
            <div class="list-item" style="flex-direction: column; align-items: flex-start; gap: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 15px;">
                    @if($academia->fotos && $academia->fotos->count() > 0)
                        <img src="{{ asset('storage/' . $academia->fotos->first()->path) }}" alt="Foto de {{ $academia->nome }}" style="width: 60px; height: 60px; border-radius: 12px; border: 1px solid var(--primary); object-fit: cover; flex-shrink: 0;">
                    @else
                        <div style="width: 60px; height: 60px; border-radius: 12px; border: 1px solid var(--primary); background: rgba(212, 255, 0, 0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--primary); font-size: 1.5rem;">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                    @endif

                    <div style="flex: 1;">
                        <strong style="display: block; font-size: 1.1rem; color: var(--primary);">{{ $academia->nome }}</strong>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">
                            <i class="fas fa-map-marker-alt"></i> {{ $academia->cidade }} - {{ $academia->estado }}
                        </span>
                        <div style="margin-top: 5px; font-weight: 700; color: #fff;">
                            Mensalidade: R$ {{ number_format($academia->valor_mensalidade, 2, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        @if($cliente->academia_id == $academia->id)
                            <div class="badge-status" style="background: var(--primary); color: #000;">Meu Plano</div>
                        @else
                            <button class="btn-action" style="margin:0; padding: 10px 20px; width: auto; font-size: 0.7rem;"
                                onclick="abrirPlanosAcademia({{ $academia->id }}, '{{ addslashes($academia->nome) }}', {!! json_encode($academia->planos->map(fn($p) => ['id'=>$p->id,'nome'=>$p->nome,'valor'=>$p->valor,'duracao'=>$p->duracao_meses,'descricao'=>$p->descricao])) !!})">
                                <i class="fas fa-tags"></i> Ver Planos
                            </button>
                        @endif
                    </div>
                </div>

                @if($academia->fotos && $academia->fotos->count() > 0)
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; width: 100%; margin-top: 8px;">
                    @foreach($academia->fotos as $foto)
                    <img src="{{ asset('storage/' . $foto->path) }}"
                         onclick="abrirGaleria('academia', {{ $academia->id }})"
                         style="width:100%; aspect-ratio:1; object-fit:cover; border-radius:8px; border:1px solid var(--border); cursor:pointer; transition:0.2s;"
                         onmouseover="this.style.borderColor='var(--primary)'"
                         onmouseout="this.style.borderColor='var(--border)'"
                         title="{{ $foto->legenda }}">
                    @endforeach
                </div>
                @endif
            </div>
            @empty
                <p style="color: var(--text-muted); text-align: center;">Nenhuma academia disponível no momento.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ✅ NOVO: MODAL DE DETALHES DO PERSONAL --}}
<div id="detalhesPersonalModal" class="modal-overlay">
    <div class="profile-card detalhes-personal-modal">
        <i class="fas fa-times close-form" onclick="fecharDetalhesPersonal()"></i>
        
        {{-- HEADER COM FOTO E NOME --}}
        <div class="detalhes-header" id="detalhesHeader">
            {{-- Preenchido via JavaScript --}}
        </div>

        {{-- GALERIA DE FOTOS --}}
        <div class="detalhes-section" id="detalhesGaleriaSection" style="display: none;">
            <div class="detalhes-section-title">
                <i class="fas fa-images"></i> Galeria de Fotos
            </div>
            <div class="detalhes-galeria" id="detalhesGaleria">
                {{-- Preenchido via JavaScript --}}
            </div>
        </div>

        {{-- ACADEMIAS ONDE ATUA --}}
        <div class="detalhes-section" id="detalhesAcademiasSection" style="display: none;">
            <div class="detalhes-section-title">
                <i class="fas fa-building"></i> Academias onde atua
            </div>
            <div class="detalhes-academias" id="detalhesAcademias">
                {{-- Preenchido via JavaScript --}}
            </div>
        </div>

        {{-- RESUMO --}}
        <div class="detalhes-section">
            <div class="detalhes-section-title">
                <i class="fas fa-chart-bar"></i> Resumo
            </div>
            <div class="detalhes-resumo-grid" id="detalhesResumo">
                {{-- Preenchido via JavaScript --}}
            </div>
        </div>

        {{-- AÇÕES: AULA AVULSA E PACOTE --}}
        <div class="detalhes-actions">
            <button onclick="abrirAgendaDoDetalhes()" class="btn-action btn-outline">
                <i class="fas fa-calendar-day"></i> Aula Avulsa
            </button>
            <button onclick="abrirPacoteDoDetalhes()" class="btn-action">
                <i class="fas fa-calendar-week"></i> Contratar Pacote
            </button>
        </div>
    </div>
</div>

{{-- MODAL DE HISTÓRICO COMPLETO --}}
<div id="historicoModal" class="modal-overlay">
    <div class="profile-card" style="width: 90%; max-width: 600px; border: 1px solid var(--primary); max-height: 90vh; overflow-y: auto;">
        <i class="fas fa-times close-form" onclick="fecharHistoricoModal()"></i>
        <h2 style="color: var(--primary); margin-bottom: 5px;">Histórico Completo de Treinos</h2>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Todos os treinos realizados</p>
        
        <div id="listaHistoricoCompleto"></div>
    </div>
</div>

{{-- MODAL DE AVALIAÇÃO --}}
<div id="avaliacaoModal" class="modal-overlay">
    <div class="profile-card" style="width: 90%; max-width: 450px; border: 1px solid var(--primary);">
        <i class="fas fa-times close-form" onclick="fecharAvaliacao()"></i>
        <h2 id="nomePersonalAvaliacao" style="color: var(--primary); margin-bottom: 5px;">Avaliar Personal</h2>
        
        <div id="avisoAvaliacao" style="background: rgba(255, 68, 68, 0.1); border: 1px solid rgba(255, 68, 68, 0.3); color: #ff6666; padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 0.8rem; text-align: center; display: none;">
            <i class="fas fa-exclamation-circle"></i> 
            Você só pode avaliar após realizar uma aula com este personal.
        </div>

        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Como foi o seu treino com este profissional?</p>
        
        <form action="{{ route('avaliar.store') }}" method="POST">
            @csrf
            <input type="hidden" name="personal_id" id="personal_id_avaliacao" value="">
            
            <div style="text-align: center; margin-bottom: 15px;">
                <div class="star-rating" style="display: flex; justify-content: center; gap: 10px; font-size: 2.5rem; color: #444; cursor: pointer; flex-direction: row-reverse;">
                    <input type="radio" name="nota" value="5" id="star5" style="display:none;" required>
                    <label for="star5"><i class="fas fa-star"></i></label>
                    <input type="radio" name="nota" value="4" id="star4" style="display:none;">
                    <label for="star4"><i class="fas fa-star"></i></label>
                    <input type="radio" name="nota" value="3" id="star3" style="display:none;">
                    <label for="star3"><i class="fas fa-star"></i></label>
                    <input type="radio" name="nota" value="2" id="star2" style="display:none;">
                    <label for="star2"><i class="fas fa-star"></i></label>
                    <input type="radio" name="nota" value="1" id="star1" style="display:none;">
                    <label for="star1"><i class="fas fa-star"></i></label>
                </div>
            </div>

            <label>Comentário (Opcional)</label>
            <div class="input-wrapper" style="height: auto; margin-top: 5px;">
                <i class="fas fa-comment-dots" style="align-self: flex-start; margin-top: 15px;"></i>
                <textarea name="comentario" placeholder="Conte o que achou..." style="width: 100%; background: transparent; border: none; color: white; padding: 15px 10px; min-height: 80px; resize: none; outline: none;"></textarea>
            </div>

            <button type="submit" class="btn-action" style="margin-top: 20px;">Enviar Avaliação</button>
        </form>
    </div>
</div>

<style>
    .star-rating label { transition: color 0.2s; }
    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
        color: gold; 
    }
</style>

{{-- MODAL AGENDA --}}
<div id="agendaModal" class="modal-overlay">
    <div class="profile-card" style="width: 90%; max-width: 450px; border: 1px solid var(--primary);">
        <i class="fas fa-times close-form" onclick="fecharAgenda()"></i>
        <h2 id="nomePersonalAgenda" style="color: var(--primary); margin-bottom: 5px;">Personal</h2>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 15px;">Selecione um horário disponível:</p>

        {{-- SELECT DE ACADEMIA (preenchido via JS) --}}
        <div id="academiaAvulsaContainer" style="margin-bottom: 15px;"></div>

        <div id="listaHorarios" style="max-height: 370px; overflow-y: auto;">
            @foreach($horariosDisponiveis as $h)
            <div class="horario-item personal-horario-{{ $h->personal_id }}" style="display: none;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--primary);">
                        {{ \Carbon\Carbon::parse($h->data)->translatedFormat('d \d\e F') }}
                    </span>
                    <span style="font-size: 0.75rem; color: #fff;">{{ $h->horario_inicio }} às {{ $h->horario_fim }}</span>
                </div>
                <form action="{{ route('agendar.horario') }}" method="POST">
                    @csrf
                    <input type="hidden" name="personal_id" value="{{ $h->personal_id }}">
                    <input type="hidden" name="data" value="{{ $h->data }}">
                    <input type="hidden" name="horario_inicio" value="{{ $h->horario_inicio }}">
                    <input type="hidden" name="horario_fim" value="{{ $h->horario_fim }}">
                    <input type="hidden" name="academia_id" value="{{ $cliente->academia_id ?? '' }}">
                    <input type="hidden" name="academia_nome" class="academia-nome-avulsa" value="">
                    <button type="submit" class="btn-action" style="width:auto; margin:0; padding: 8px 15px; font-size: 0.7rem;">
                        <i class="fas fa-calendar-check"></i> Agendar
                    </button>
                </form>
            </div>
            @endforeach
            <p id="msgSemHorario" style="display: none; text-align: center; color: var(--text-muted); padding: 20px;">Sem horários livres.</p>
        </div>
    </div>
</div>

{{-- MODAL CONTRATAÇÃO DE PACOTE --}}
<div id="pacoteModal" class="modal-overlay">
    <div class="profile-card" style="width: 95%; max-width: 800px; border: 1px solid var(--primary); max-height: 90vh; overflow-y: auto;">
        <i class="fas fa-times close-form" onclick="fecharPacoteModal()"></i>
        
        <h2 id="nomePacotePersonal" style="color: var(--primary); margin-bottom: 5px;">Contratar Pacote</h2>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i> Selecione o pacote, os dias do mês e o horário desejado para agendar seus treinos
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label style="display: block; margin-bottom: 15px;">
                    <span style="color: var(--primary); font-weight: 900; text-transform: uppercase; font-size: 0.7rem;">Frequência Semanal</span>
                </label>
                
                <div id="listaPacotes" style="display: flex; flex-direction: column; gap: 10px; max-height: 150px; overflow-y: auto; padding-right: 10px; margin-bottom: 20px;"></div>

                <div style="background: rgba(212, 255, 0, 0.05); padding: 12px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 20px;">
                    <p style="margin: 0 0 8px 0; font-size: 0.75rem; color: var(--text-muted);">
                        <i class="fas fa-calendar"></i> <span style="color: var(--primary); font-weight: 900;" id="contadorDias">0</span> dia(s) selecionado(s)
                    </p>
                    <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted);">
                        <i class="fas fa-clock"></i> Horário: <span id="horarioSelecionado" style="color: var(--primary); font-weight: 900;">Nenhum</span>
                    </p>
                </div>

                <form id="formContratacao" action="{{ route('pacotes.contratar') }}" method="POST">
                    @csrf
                    <input type="hidden" id="pacote_personal_id" name="personal_id">
                    <input type="hidden" id="pacote_frequencia" name="frequencia_pacote">
                    <input type="hidden" id="pacote_valor" name="valor_pacote">
                    <input type="hidden" id="pacote_dias" name="dias_selecionados" value="[]">
                    <input type="hidden" id="pacote_hora_inicio" name="hora_inicio" value="">
                    <input type="hidden" id="pacote_hora_fim" name="hora_fim" value="">
                    <input type="hidden" id="pacote_id" name="pacote_id" value="">
                    <input type="hidden" id="pacote_academia_nome" name="academia_nome" value="">

                    <div id="pacoteAcademiaContainer" style="margin-bottom: 15px;"></div>

                    <button type="submit" class="btn-action" style="margin-top: 0;" id="btnConfirmarContratacao" disabled>
                        <i class="fas fa-check-circle"></i> Confirmar Contratação
                    </button>
                </form>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <button type="button" onclick="mesAnterior()" class="btn-action btn-outline" style="padding: 8px 12px; margin: 0; width: auto; font-size: 0.7rem;">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span id="mesSelecionado" style="color: var(--primary); font-weight: 900; text-transform: uppercase; font-size: 0.8rem; min-width: 150px; text-align: center;">
                        Fevereiro 2025
                    </span>
                    <button type="button" onclick="mesProximo()" class="btn-action btn-outline" style="padding: 8px 12px; margin: 0; width: auto; font-size: 0.7rem;">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-bottom: 10px; text-align: center;">
                    @foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dia)
                        <div style="color: var(--primary); font-weight: 700; font-size: 0.65rem; padding: 8px 0;">
                            {{ $dia }}
                        </div>
                    @endforeach
                </div>

                <div id="calendarGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; max-height: 280px; overflow-y: auto;"></div>

                <p id="diasSelecionados" style="color: var(--text-muted); font-size: 0.75rem; margin-top: 15px; text-align: center;">
                    Clique nos dias para selecionar
                </p>
            </div>
        </div>
    </div>
</div>

{{-- MODAL SELETOR DE HORÁRIOS --}}
<div id="horarioModal" class="modal-overlay" style="display: none;">
    <div class="profile-card" style="width: 90%; max-width: 450px; border: 1px solid var(--primary);">
        <i class="fas fa-times close-form" onclick="fecharHorarioModal()"></i>
        <h2 style="color: var(--primary); margin-bottom: 5px;" id="tituloHorarioModal">Selecione um Horário</h2>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Escolha o horário desejado para este dia</p>
        <div id="listaHorariosDisp" style="max-height: 400px; overflow-y: auto;"></div>
    </div>
</div>

{{-- MODAL GALERIA VISUALIZAÇÃO --}}
<div id="modalGaleriaView" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.95); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(8px);">
    <div style="background:var(--card-bg); border-radius:24px; padding:30px; width:90%; max-width:550px; border:1px solid var(--border); position:relative;">
        <i class="fas fa-times" onclick="fecharGaleria()" style="position:absolute; top:20px; right:25px; cursor:pointer; color:var(--text-muted); font-size:1.2rem;"></i>
        <h3 id="galeriaViewTitulo" style="color:var(--primary); margin:0 0 20px; font-size:1.1rem; font-weight:900;"></h3>
        <div id="galeriaViewGrid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;"></div>
    </div>
</div>

{{-- LIGHTBOX --}}
<div id="lightbox" onclick="fecharLightbox()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.98); z-index:99999; justify-content:center; align-items:center; cursor:zoom-out;">
    <img id="lightboxImg" style="max-width:90vw; max-height:90vh; border-radius:12px; object-fit:contain;">
</div>

<script>
    // ============ DADOS DOS PERSONALS ============
    window.personalsData = {
        @foreach($personals as $p)
        {{ $p->id }}: {
            id: {{ $p->id }},
            nome: '{{ addslashes($p->nome) }}',
            foto: '{{ $p->foto ? asset("storage/" . $p->foto) : "" }}',
            avaliacao: '{{ $p->media_avaliacao }}',
            valor_secao: {{ $p->valor_secao ?? 0 }},
            academias: {!! json_encode($p->academias ?? '') !!},
            fotos: [
                @foreach($p->fotos ?? [] as $foto)
                { url: '{{ asset("storage/" . $foto->path) }}', legenda: '{{ addslashes($foto->legenda ?? "") }}' },
                @endforeach
            ],
            total_avaliacoes: {{ $p->avaliacoes->count() ?? 0 }}
        },
        @endforeach
    };

    let personalSelecionadoId = null;

    // ============ MODAL DE DETALHES DO PERSONAL ============
    function abrirDetalhesPersonal(personalId) {
        const personal = window.personalsData[personalId];
        if (!personal) return;

        personalSelecionadoId = personalId;

        // HEADER
        const fotoHTML = personal.foto 
            ? `<img src="${personal.foto}" alt="${personal.nome}" class="detalhes-foto">`
            : `<div class="detalhes-foto-placeholder"><i class="fas fa-user"></i></div>`;

        document.getElementById('detalhesHeader').innerHTML = `
            ${fotoHTML}
            <div class="detalhes-info-header">
                <h2>${personal.nome}</h2>
                <p>Personal Trainer Certificado</p>
                <div class="detalhes-avaliacao">
                    <i class="fas fa-star"></i> ${personal.avaliacao} 
                    <span style="color: var(--text-muted); font-size: 0.7rem;">(${personal.total_avaliacoes} avaliações)</span>
                </div>
            </div>
        `;

        // GALERIA DE FOTOS
        if (personal.fotos && personal.fotos.length > 0) {
            document.getElementById('detalhesGaleriaSection').style.display = 'block';
            document.getElementById('detalhesGaleria').innerHTML = personal.fotos.map(f => `
                <img src="${f.url}" alt="Foto" onclick="abrirLightbox('${f.url}')">
            `).join('');
        } else {
            document.getElementById('detalhesGaleriaSection').style.display = 'none';
        }

        // ACADEMIAS
        if (personal.academias && personal.academias.trim()) {
            document.getElementById('detalhesAcademiasSection').style.display = 'block';
            const academiasArray = personal.academias.split('\n').filter(a => a.trim());
            document.getElementById('detalhesAcademias').innerHTML = academiasArray.map(a => `
                <div class="detalhes-academia-item">
                    <i class="fas fa-building"></i>
                    <span>${a.trim()}</span>
                </div>
            `).join('');
        } else {
            document.getElementById('detalhesAcademiasSection').style.display = 'none';
        }

        // RESUMO
        const pacotes = window.pacotesPorPersonal?.[personalId] || [];
        const menorPacote = pacotes.length > 0 ? Math.min(...pacotes.map(p => p.valor_mensal)) : 0;

        document.getElementById('detalhesResumo').innerHTML = `
            <div class="detalhes-resumo-card">
                <i class="fas fa-dollar-sign"></i>
                <div class="label">Aula Avulsa</div>
                <div class="valor">R$ ${parseFloat(personal.valor_secao).toFixed(2).replace('.', ',')}</div>
            </div>
            <div class="detalhes-resumo-card">
                <i class="fas fa-box"></i>
                <div class="label">Pacote a partir de</div>
                <div class="valor">${menorPacote > 0 ? 'R$ ' + parseFloat(menorPacote).toFixed(2).replace('.', ',') : '-'}</div>
            </div>
            <div class="detalhes-resumo-card">
                <i class="fas fa-images"></i>
                <div class="label">Fotos</div>
                <div class="valor">${personal.fotos.length}</div>
            </div>
        `;

        document.getElementById('detalhesPersonalModal').style.display = 'flex';
    }

    function fecharDetalhesPersonal() {
        document.getElementById('detalhesPersonalModal').style.display = 'none';
    }

    function abrirAgendaDoDetalhes() {
        const personal = window.personalsData[personalSelecionadoId];
        fecharDetalhesPersonal();
        abrirAgenda(personalSelecionadoId, personal.nome);
    }

    function abrirPacoteDoDetalhes() {
        const personal = window.personalsData[personalSelecionadoId];
        fecharDetalhesPersonal();
        abrirPacoteModal(personalSelecionadoId, personal.nome);
    }

    // ============ HISTÓRICO ============
    window.historicoData = {!! json_encode($historico->map(function($t) {
        return [
            'personal' => $t->personal->nome ?? 'N/A',
            'academia' => $t->academia ? $t->academia->nome : null,
            'data' => \Carbon\Carbon::parse($t->data)->format('d/m/Y'),
            'hora_inicio' => \Carbon\Carbon::parse($t->hora_inicio)->format('H:i'),
            'hora_fim' => \Carbon\Carbon::parse($t->hora_fim)->format('H:i')
        ];
    })) !!};

    function abrirHistoricoModal() {
        const container = document.getElementById('listaHistoricoCompleto');
        
        if (window.historicoData.length === 0) {
            container.innerHTML = '<div class="stat-card" style="padding: 20px; border-style: dashed;"><p style="color: var(--text-muted); margin: 0; font-size: 0.85rem;"><i class="fas fa-history"></i> Nenhum treino realizado ainda.</p></div>';
        } else {
            container.innerHTML = window.historicoData.map(treino => `
                <div class="list-item" style="border-left-color: var(--text-muted); opacity: 0.8; flex-direction: column; align-items: flex-start; gap: 10px;">
                    <div style="width: 100%;">
                        <strong style="display: block; font-size: 1rem; color: #fff;">
                            ${treino.personal}
                        </strong>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">
                            <i class="far fa-calendar-alt"></i> ${treino.data}
                            às ${treino.hora_inicio}
                        </span>
                        ${treino.academia ? `<span style="display: block; color: var(--text-muted); font-size: 0.75rem; margin-top: 3px;"><i class="fas fa-dumbbell"></i> ${treino.academia}</span>` : ''}
                    </div>
                    <div style="width: 100%; text-align: right;">
                        <div class="badge-status" style="background: rgba(160,160,160,0.1); color: var(--text-muted); display: inline-block;">Concluído</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">
                            ${treino.hora_inicio} - ${treino.hora_fim}
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        document.getElementById('historicoModal').style.display = 'flex';
    }

    function fecharHistoricoModal() {
        document.getElementById('historicoModal').style.display = 'none';
    }

    // ============ PAGINAÇÃO DA AGENDA ============
    let paginaAtualAgenda = 1;
    const itensPorPagina = 5;
    let totalPaginasAgenda = 1;

    function inicializarPaginacaoAgenda() {
        if (window.agendamentosData && window.agendamentosData.length > 0) {
            totalPaginasAgenda = Math.ceil(window.agendamentosData.length / itensPorPagina);
            exibirPaginaAgenda(1);
            atualizarBotoesPaginacao();
        }
    }

    function exibirPaginaAgenda(pagina) {
        if (pagina < 1 || pagina > totalPaginasAgenda) return;
        paginaAtualAgenda = pagina;

        const inicio = (pagina - 1) * itensPorPagina;
        const fim = inicio + itensPorPagina;
        const itemsVistos = window.agendamentosData.slice(inicio, fim);

        const container = document.getElementById('agendaItems');
        container.innerHTML = itemsVistos.map(agendamento => `
            <div class="list-item">
                <div>
                    <strong style="display: block; font-size: 1rem;">${agendamento.personal}</strong>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">
                        <i class="far fa-calendar-alt"></i> ${agendamento.data}
                        às ${agendamento.hora}
                    </span>
                </div>
                <div class="badge-status">Confirmado</div>
            </div>
        `).join('');

        atualizarBotoesPaginacao();
    }

    function atualizarBotoesPaginacao() {
        document.getElementById('paginaAtualInfo').textContent = paginaAtualAgenda;
        document.getElementById('totalPaginasInfo').textContent = totalPaginasAgenda;

        document.getElementById('btnAnterior').disabled = paginaAtualAgenda === 1;
        document.getElementById('btnProxima').disabled = paginaAtualAgenda === totalPaginasAgenda;
        document.getElementById('btnPrimeira').disabled = paginaAtualAgenda === 1;
        document.getElementById('btnUltima').disabled = paginaAtualAgenda === totalPaginasAgenda;

        const container = document.getElementById('paginasBotoes');
        let botoesHTML = '';
        for (let i = 1; i <= totalPaginasAgenda; i++) {
            const classe = i === paginaAtualAgenda ? 'active' : '';
            botoesHTML += `<button class="pagination-btn ${classe}" onclick="irParaPaginaAgenda(${i})">${i}</button>`;
        }
        container.innerHTML = botoesHTML;
    }

    function irParaPaginaAgenda(pagina) {
        if (pagina === -1) pagina = totalPaginasAgenda;
        exibirPaginaAgenda(pagina);
    }

    function paginaAnteriorAgenda() {
        exibirPaginaAgenda(paginaAtualAgenda - 1);
    }

    function proximaPaginaAgenda() {
        exibirPaginaAgenda(paginaAtualAgenda + 1);
    }

    // ============ DADOS GLOBAIS DE PACOTES ============
    window.pacotesPorPersonal = {
        @foreach($personals as $p)
        {{ $p->id }}: [
            @php
                $pacotesPersonal = \App\Models\Cadastro\Pacote::where('personal_id', $p->id)->get();
            @endphp
            @foreach($pacotesPersonal as $pacote)
            {
                id: {{ $pacote->id }},
                frequencia: {{ $pacote->frequencia }},
                valor_mensal: {{ $pacote->valor_mensal }}
            },
            @endforeach
        ],
        @endforeach
    };

    window.diasOcupadosPorPersonal = {
        @foreach($personals as $p)
        {{ $p->id }}: [
            @php
                $diasOcupados = \App\Models\Agenda::where('personal_id', $p->id)
                    ->where('cancelado', false)
                    ->whereBetween('data', [
                        now()->startOfMonth()->format('Y-m-d'),
                        now()->addMonth()->endOfMonth()->format('Y-m-d')
                    ])
                    ->pluck('data')
                    ->toArray();
            @endphp
            @foreach($diasOcupados as $dia)
            '{{ $dia }}',
            @endforeach
        ],
        @endforeach
    };

    // ============ VARIÁVEIS DO MODAL DE PACOTES ============
    let dataAtual = new Date();
    let mesModalAberto = new Date();
    let pacoteSelecionado = null;
    let diasSelecionados = [];
    let horaInicio = null;
    let horaFim = null;
    let diaEmSelecao = null;

    function abrirPacoteModal(personalId, personalNome) {
        document.getElementById('nomePacotePersonal').innerText = 'Contratar Pacote - ' + personalNome;
        document.getElementById('pacote_personal_id').value = personalId;
        dataAtual = new Date();
        mesModalAberto = new Date();
        diasSelecionados = [];
        pacoteSelecionado = null;
        horaInicio = null;
        horaFim = null;

        // Popula o select de academias do personal
        const personal = window.personalsData[personalId];
        const container = document.getElementById('pacoteAcademiaContainer');
        const academias = personal && personal.academias
            ? personal.academias.split('\n').map(a => a.trim()).filter(a => a.length > 0)
            : [];

        container.style.display = 'block';
        container.dataset.temAcademias = academias.length > 0 ? 'true' : 'false';
        document.getElementById('pacote_academia_nome').value = '';

        if (academias.length > 0) {
            container.innerHTML = `
                <label style="display:block; color:var(--primary); font-weight:900; text-transform:uppercase; font-size:0.7rem; margin-bottom:6px;">
                    <i class="fas fa-map-marker-alt"></i> Academia / Local de Treino
                </label>
                <select id="academiaPackSelect"
                        onchange="document.getElementById('pacote_academia_nome').value = this.value; atualizarBotao();"
                        style="width:100%; background:var(--bg-card,#111); border:1px solid var(--border,#333); color:#fff; padding:10px 12px; border-radius:10px; font-size:0.85rem; outline:none; cursor:pointer;">
                    <option value="">Selecione uma academia</option>
                    ${academias.map(a => `<option value="${a}">${a}</option>`).join('')}
                </select>`;
            if (academias.length === 1) {
                document.getElementById('academiaPackSelect').value = academias[0];
                document.getElementById('pacote_academia_nome').value = academias[0];
            }
        } else {
            container.innerHTML = `
                <p style="color:var(--text-muted); font-size:0.8rem; padding:10px 12px; background:rgba(255,255,255,0.04); border-radius:10px; border:1px solid var(--border,#333); margin:0;">
                    <i class="fas fa-info-circle"></i> O personal ainda não informou as academias em que trabalha.
                </p>`;
        }

        carregarPacotes(personalId);
        atualizarCalendario();
        atualizarBotao();
        document.getElementById('pacoteModal').style.display = 'flex';
    }

    function fecharPacoteModal() {
        document.getElementById('pacoteModal').style.display = 'none';
        fecharHorarioModal();
    }

    function carregarPacotes(personalId) {
        const pacotesData = window.pacotesPorPersonal?.[personalId] || [];
        const container = document.getElementById('listaPacotes');
        
        if (pacotesData.length === 0) {
            container.innerHTML = '<p style="color: var(--text-muted); font-size: 0.75rem;">Nenhum pacote disponível</p>';
            return;
        }

        container.innerHTML = pacotesData.map((pacote, idx) => `
            <div class="pacote-item" onclick="selecionarPacote(${pacote.frequencia}, ${pacote.valor_mensal}, ${idx}, ${pacote.id || 0})">
                <span class="pacote-freq">${pacote.frequencia}x na semana</span>
                <span class="pacote-valor">R$ ${(pacote.valor_mensal).toFixed(2).replace('.', ',')}</span>
            </div>
        `).join('');
    }

    function selecionarPacote(frequencia, valor, idx, pacoteId) {
        document.querySelectorAll('.pacote-item').forEach(el => el.classList.remove('selecionado'));
        document.querySelectorAll('.pacote-item')[idx].classList.add('selecionado');

        pacoteSelecionado = { id: pacoteId, frequencia, valor };
        document.getElementById('pacote_frequencia').value = frequencia;
        document.getElementById('pacote_valor').value = valor;
        document.getElementById('pacote_id').value = pacoteId;

        diasSelecionados = [];
        horaInicio = null;
        horaFim = null;
        document.getElementById('contadorDias').textContent = '0';
        document.getElementById('horarioSelecionado').textContent = 'Nenhum';
        atualizarCalendario();
        atualizarBotao();
    }

    function atualizarCalendario() {
        const ano = mesModalAberto.getFullYear();
        const mes = mesModalAberto.getMonth();
        const primeiroDay = new Date(ano, mes, 1);
        const ultimoDay = new Date(ano, mes + 1, 0);
        const diasDoMes = ultimoDay.getDate();
        const diaInicio = primeiroDay.getDay();

        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                       'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        document.getElementById('mesSelecionado').innerText = `${meses[mes]} ${ano}`;

        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';

        const diasMesAnterior = new Date(ano, mes, 0).getDate();
        for (let i = diaInicio - 1; i >= 0; i--) {
            const dia = diasMesAnterior - i;
            const div = document.createElement('div');
            div.className = 'dia-calendario outro-mes';
            div.textContent = dia;
            grid.appendChild(div);
        }

        for (let dia = 1; dia <= diasDoMes; dia++) {
            const dataCompleta = new Date(ano, mes, dia);
            const ehPassado = dataCompleta < new Date() && mes === new Date().getMonth() && ano === new Date().getFullYear();
            const chaveData = `${ano}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
            const personalId = document.getElementById('pacote_personal_id').value;
            const estaOcupado = window.diasOcupadosPorPersonal?.[personalId]?.includes(chaveData) || false;
            
            const div = document.createElement('div');
            div.className = 'dia-calendario';
            div.textContent = dia;

            if (ehPassado) {
                div.classList.add('outro-mes');
            } else if (estaOcupado) {
                div.classList.add('ocupado');
            } else {
                div.classList.add('disponivel');
                div.onclick = () => abrirSeletorHorario(dia, chaveData, personalId, ano, mes);
            }

            if (diasSelecionados.includes(dia + '_' + mes + '_' + ano)) {
                div.classList.add('selecionado');
            }

            grid.appendChild(div);
        }

        const diasRestantes = 42 - (diaInicio + diasDoMes);
        for (let dia = 1; dia <= diasRestantes; dia++) {
            const div = document.createElement('div');
            div.className = 'dia-calendario outro-mes';
            div.textContent = dia;
            grid.appendChild(div);
        }
    }

    function abrirSeletorHorario(dia, chaveData, personalId, ano, mes) {
        if (pacoteSelecionado === null) {
            alert('Selecione um pacote primeiro!');
            return;
        }

        const chaveComMes = dia + '_' + mes + '_' + ano;
        const frequencia = pacoteSelecionado.frequencia;

        if (diasSelecionados.includes(chaveComMes)) {
            diasSelecionados = diasSelecionados.filter(d => d !== chaveComMes);
            atualizarCalendario();
            atualizarBotao();
            return;
        }

        if (diasSelecionados.length >= frequencia) {
            alert(`Você já selecionou ${frequencia} dia(s). O pacote permite apenas ${frequencia}x na semana.`);
            return;
        }

        diaEmSelecao = dia;
        const modal = document.getElementById('horarioModal');
        const container = document.getElementById('listaHorariosDisp');
        
        document.getElementById('tituloHorarioModal').innerText = `Selecione um Horário - ${dia}/${(mes + 1)}/${ano}`;
        container.innerHTML = '<p style="text-align:center; color: var(--text-muted);">Buscando horários disponíveis...</p>';
        modal.style.display = 'flex';

        fetch(`/horarios-disponiveis/${personalId}/${chaveData}`)
            .then(r => r.json())
            .then(horarios => {
                if (horarios.length === 0) {
                    container.innerHTML = '<p style="text-align:center; color: var(--text-muted);">Nenhum horário disponível neste dia.</p>';
                    return;
                }

                container.innerHTML = horarios.map(h => `
                    <div class="horario-selecionavel" onclick="selecionarHorario(${dia}, '${h.inicio}', '${h.fim}', ${ano}, ${mes})">
                        <span style="font-weight: 700; color: var(--primary);">${h.label}</span>
                        <i class="fas fa-check" style="color: var(--primary);"></i>
                    </div>
                `).join('');
            })
            .catch(err => {
                container.innerHTML = '<p style="color: var(--error);">Erro ao buscar horários</p>';
                console.error(err);
            });
    }

    function selecionarHorario(dia, inicio, fim, ano, mes) {
        const chaveComMes = dia + '_' + mes + '_' + ano;
        
        if (!diasSelecionados.includes(chaveComMes)) {
            diasSelecionados.push(chaveComMes);
        }
        
        horaInicio = inicio;
        horaFim = fim;

        document.getElementById('contadorDias').textContent = diasSelecionados.length;
        document.getElementById('horarioSelecionado').textContent = `${inicio} - ${fim}`;
        
        const apenasNumeros = diasSelecionados.map(d => parseInt(d.split('_')[0]));
        document.getElementById('pacote_dias').value = JSON.stringify(apenasNumeros);
        document.getElementById('pacote_hora_inicio').value = horaInicio;
        document.getElementById('pacote_hora_fim').value = horaFim;

        fecharHorarioModal();
        atualizarCalendario();
        atualizarBotao();
    }

    function fecharHorarioModal() {
        document.getElementById('horarioModal').style.display = 'none';
    }

    function atualizarBotao() {
        const btn = document.getElementById('btnConfirmarContratacao');
        const temPacote = pacoteSelecionado !== null;
        const temDias = diasSelecionados.length > 0;
        const temHorario = horaInicio !== null;

        const container = document.getElementById('pacoteAcademiaContainer');
        const precisaAcademia = container.dataset.temAcademias === 'true';
        const temAcademia = !precisaAcademia || document.getElementById('pacote_academia_nome').value.trim() !== '';

        btn.disabled = !(temPacote && temDias && temHorario && temAcademia);
    }

    function mesAnterior() {
        mesModalAberto.setMonth(mesModalAberto.getMonth() - 1);
        atualizarCalendario();
    }

    function mesProximo() {
        mesModalAberto.setMonth(mesModalAberto.getMonth() + 1);
        atualizarCalendario();
    }

    // ============ FUNÇÕES GERAIS ============
    function toggleEditForm() {
        const summary = document.getElementById('dashboardSummary');
        const form    = document.getElementById('editFormContainer');
        const header  = document.getElementById('mainHeader');
        const isOpening = form.style.display === 'none' || form.style.display === '';
        form.style.display    = isOpening ? 'block' : 'none';
        summary.style.display = isOpening ? 'none'  : 'block';
        header.style.display  = isOpening ? 'none'  : 'block';
    }

    function toggleMenu() {
        const menu = document.getElementById('dropdownMenu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    function abrirAgenda(id, nome) {
        document.getElementById('nomePersonalAgenda').innerText = nome;
        document.querySelectorAll('.horario-item').forEach(h => h.style.display = 'none');
        const filtrados = document.querySelectorAll('.personal-horario-' + id);
        const msgVazio  = document.getElementById('msgSemHorario');
        if (filtrados.length > 0) {
            filtrados.forEach(h => h.style.display = 'flex');
            msgVazio.style.display = 'none';
        } else {
            msgVazio.style.display = 'block';
        }

        // Popula o select de academias do personal
        const personal = window.personalsData[id];
        const container = document.getElementById('academiaAvulsaContainer');
        const academias = personal && personal.academias
            ? personal.academias.split('\n').map(a => a.trim()).filter(a => a.length > 0)
            : [];

        container.style.display = 'block';
        if (academias.length > 0) {
            container.innerHTML = `
                <label style="display:block; color:var(--primary); font-weight:900; text-transform:uppercase; font-size:0.7rem; margin-bottom:6px;">
                    <i class="fas fa-map-marker-alt"></i> Academia / Local de Treino
                </label>
                <select id="academiaAvulsaSelect" onchange="atualizarAcademiaAvulsa(this.value)"
                        style="width:100%; background:var(--bg-card,#111); border:1px solid var(--border,#333); color:#fff; padding:10px 12px; border-radius:10px; font-size:0.85rem; outline:none; cursor:pointer;">
                    <option value="">Selecione uma academia</option>
                    ${academias.map(a => `<option value="${a}">${a}</option>`).join('')}
                </select>`;
            if (academias.length === 1) {
                document.getElementById('academiaAvulsaSelect').value = academias[0];
                atualizarAcademiaAvulsa(academias[0]);
            } else {
                atualizarAcademiaAvulsa('');
            }
        } else {
            container.innerHTML = `
                <p style="color:var(--text-muted); font-size:0.8rem; padding:10px 12px; background:rgba(255,255,255,0.04); border-radius:10px; border:1px solid var(--border,#333); margin:0;">
                    <i class="fas fa-info-circle"></i> O personal ainda não informou as academias em que trabalha.
                </p>`;
            atualizarAcademiaAvulsa('');
        }

        document.getElementById('agendaModal').style.display = 'flex';
    }

    function atualizarAcademiaAvulsa(valor) {
        document.querySelectorAll('.academia-nome-avulsa').forEach(input => {
            input.value = valor;
        });
    }

    function fecharAgenda() { document.getElementById('agendaModal').style.display = 'none'; }

    const fotosData = {
        @foreach($personals as $p)
        'personal_{{ $p->id }}': {
            titulo: '{{ addslashes($p->nome) }}',
            fotos: [
                @foreach($p->fotos as $foto)
                { url: '{{ asset("storage/" . $foto->path) }}', legenda: '{{ addslashes($foto->legenda ?? "") }}' },
                @endforeach
            ]
        },
        @endforeach
        @foreach($academias as $academia)
        'academia_{{ $academia->id }}': {
            titulo: '{{ addslashes($academia->nome) }}',
            fotos: [
                @foreach($academia->fotos as $foto)
                { url: '{{ asset("storage/" . $foto->path) }}', legenda: '{{ addslashes($foto->legenda ?? "") }}' },
                @endforeach
            ]
        },
        @endforeach
    };

    function abrirGaleria(tipo, id) {
        const key  = tipo + '_' + id;
        const data = fotosData[key];
        if (!data || data.fotos.length === 0) return;

        document.getElementById('galeriaViewTitulo').textContent = data.titulo;
        const grid = document.getElementById('galeriaViewGrid');
        grid.innerHTML = data.fotos.map(f => `
            <div style="position:relative; aspect-ratio:1; border-radius:12px; overflow:hidden; border:1px solid var(--border); cursor:pointer;"
                 onclick="abrirLightbox('${f.url}')">
                <img src="${f.url}" style="width:100%; height:100%; object-fit:cover; transition:0.3s;"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform='scale(1)'">
                ${f.legenda ? `<div style="position:absolute; bottom:0; left:0; right:0; background:rgba(0,0,0,0.7); color:#fff; font-size:0.68rem; padding:4px 8px; text-align:center;">${f.legenda}</div>` : ''}
            </div>
        `).join('');

        document.getElementById('modalGaleriaView').style.display = 'flex';
    }

    function fecharGaleria()  { document.getElementById('modalGaleriaView').style.display = 'none'; }
    function abrirLightbox(url) { document.getElementById('lightboxImg').src = url; document.getElementById('lightbox').style.display = 'flex'; }
    function fecharLightbox() { document.getElementById('lightbox').style.display = 'none'; }

    function abrirAvaliacao(id, nome) {
        document.getElementById('nomePersonalAvaliacao').innerText = 'Avaliar ' + nome;
        document.getElementById('personal_id_avaliacao').value = id;
        
        fetch(`/cliente/pode-avaliar/${id}`)
            .then(r => r.json())
            .then(data => {
                const avisoDiv = document.getElementById('avisoAvaliacao');
                const formDiv = document.querySelector('#avaliacaoModal form');
                
                if (data.pode_avaliar) {
                    avisoDiv.style.display = 'none';
                    formDiv.style.display = 'block';
                } else {
                    avisoDiv.style.display = 'block';
                    formDiv.style.display = 'none';
                    
                    setTimeout(() => {
                        avisoDiv.style.display = 'none';
                        formDiv.style.display = 'block';
                    }, 7000);
                }
                
                document.getElementById('avaliacaoModal').style.display = 'flex';
            })
            .catch(err => {
                console.error('Erro ao verificar avaliação:', err);
                document.getElementById('avaliacaoModal').style.display = 'flex';
            });
    }

    function fecharAvaliacao() {
        document.getElementById('avaliacaoModal').style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target.id === 'agendaModal') fecharAgenda();
        if (e.target.id === 'pacoteModal') fecharPacoteModal();
        if (e.target.id === 'horarioModal') fecharHorarioModal();
        if (e.target.id === 'modalGaleriaView') fecharGaleria();
        if (e.target.id === 'avaliacaoModal') fecharAvaliacao();
        if (e.target.id === 'historicoModal') fecharHistoricoModal();
        if (e.target.id === 'detalhesPersonalModal') fecharDetalhesPersonal();
        if (!e.target.closest('.menu-container')) document.getElementById('dropdownMenu').style.display = 'none';
    }

    const mascaras = {
        cep: function(value) {
            return value.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2').replace(/(-\d{3})\d+?$/, '$1');
        }
    }

    document.addEventListener('DOMContentLoaded', inicializarPaginacaoAgenda);

    // ============ FLUXO DE PAGAMENTO PIX (Asaas) ============
    let pixPollingInterval = null;

    document.getElementById('formContratacao').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('btnConfirmarContratacao');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando Pix...';

        const payload = {
            tipo:              'pacote',
            personal_id:       document.getElementById('pacote_personal_id').value,
            pacote_id:         document.getElementById('pacote_id').value,
            frequencia:        document.getElementById('pacote_frequencia').value,
            valor_pacote:      document.getElementById('pacote_valor').value,
            dias_selecionados: document.getElementById('pacote_dias').value,
            hora_inicio:       document.getElementById('pacote_hora_inicio').value,
            hora_fim:          document.getElementById('pacote_hora_fim').value,
            academia_nome:     document.getElementById('pacote_academia_nome').value,
        };

        try {
            const response = await fetch('/api/criar-pagamento', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Erro ao gerar pagamento.');

            // Exibe modal Pix
            document.getElementById('pixQrCodeImg').src = 'data:image/png;base64,' + data.pixQrCode;
            document.getElementById('pixCopiaCola').value = data.pixPayload;
            document.getElementById('pixValor').textContent = 'R$ ' + parseFloat(data.amount).toFixed(2).replace('.', ',');
            document.getElementById('modalPix').style.display = 'flex';

            // Inicia polling
            pixPollingInterval = setInterval(async () => {
                try {
                    const statusRes  = await fetch('/api/pagamento/status/' + data.asaasPaymentId, {
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    const statusData = await statusRes.json();
                    if (statusData.confirmed) {
                        clearInterval(pixPollingInterval);
                        document.getElementById('pixStatusMsg').textContent  = '✅ Pagamento confirmado!';
                        document.getElementById('pixStatusMsg').style.color  = 'var(--success)';
                        document.getElementById('pixStatusMsg').style.display = 'block';
                        setTimeout(() => { window.location.href = '/pagamento/sucesso'; }, 2000);
                    }
                } catch (_) {}
            }, 4000);

        } catch (error) {
            alert('Erro ao iniciar pagamento: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            atualizarBotao();
        }
    });

    function fecharModalPix() {
        document.getElementById('modalPix').style.display = 'none';
        clearInterval(pixPollingInterval);
    }

    function copiarPixCola() {
        const input = document.getElementById('pixCopiaCola');
        input.select();
        document.execCommand('copy');
        const btn = document.getElementById('btnCopiarPix');
        btn.textContent = 'Copiado!';
        setTimeout(() => { btn.textContent = 'Copiar código'; }, 2500);
    }
</script>

{{-- MODAL PIX (Asaas) --}}
<div id="modalPix" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88); z-index:99999; flex-direction:column; justify-content:center; align-items:center; backdrop-filter:blur(6px); padding:20px;">
    <div style="background:#16181d; border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:32px; max-width:420px; width:100%; text-align:center; position:relative;">
        <button onclick="fecharModalPix()" style="position:absolute; top:16px; right:16px; background:none; border:none; color:#a0a0a0; font-size:1.2rem; cursor:pointer;">✕</button>

        <h3 style="color:var(--primary); font-size:1.1rem; font-weight:900; margin:0 0 4px;">PAGAMENTO VIA PIX</h3>
        <p id="pixValor" style="color:#fff; font-size:1.5rem; font-weight:700; margin:0 0 20px;"></p>

        <img id="pixQrCodeImg" src="" alt="QR Code Pix" style="width:200px; height:200px; border-radius:12px; background:#fff; padding:8px; margin-bottom:16px;">

        <p style="color:#a0a0a0; font-size:0.8rem; margin:0 0 8px;">Ou copie o código Pix:</p>
        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <input id="pixCopiaCola" type="text" readonly style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:8px 12px; color:#fff; font-size:0.75rem; outline:none;">
            <button id="btnCopiarPix" onclick="copiarPixCola()" style="background:var(--primary); color:#000; border:none; border-radius:8px; padding:8px 14px; font-weight:700; font-size:0.8rem; cursor:pointer; white-space:nowrap;">Copiar código</button>
        </div>

        <div style="display:flex; align-items:center; gap:8px; justify-content:center; color:#a0a0a0; font-size:0.8rem; margin-bottom:12px;">
            <div style="width:10px; height:10px; border:2px solid rgba(212,255,0,0.3); border-top-color:var(--primary); border-radius:50%; animation:spinPix 1s linear infinite;"></div>
            Aguardando confirmação do pagamento...
        </div>

        <p id="pixStatusMsg" style="display:none; font-weight:700; font-size:0.9rem; margin:0;"></p>
    </div>
</div>
<style>
    @@keyframes spinPix { to { transform: rotate(360deg); } }
</style>

{{-- MODAL PLANOS DA ACADEMIA --}}
<div id="modalPlanosAcademia" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88); z-index:99998; flex-direction:column; justify-content:center; align-items:center; backdrop-filter:blur(6px); padding:20px;">
    <div style="background:#16181d; border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:32px; max-width:500px; width:100%; position:relative; max-height:90vh; overflow-y:auto;">
        <button onclick="fecharPlanosAcademia()" style="position:absolute; top:16px; right:16px; background:none; border:none; color:#a0a0a0; font-size:1.2rem; cursor:pointer;">✕</button>
        <h3 id="planosAcademiaNome" style="color:#d4ff00; font-size:1.1rem; font-weight:900; margin:0 0 6px;"></h3>
        <p style="color:#a0a0a0; font-size:0.8rem; margin:0 0 20px;">Selecione um plano e pague via PIX para se associar.</p>
        <div id="planosAcademiaLista"></div>
    </div>
</div>

{{-- MODAL PIX — ACADEMIA --}}
<div id="modalPixAcademia" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88); z-index:99999; flex-direction:column; justify-content:center; align-items:center; backdrop-filter:blur(6px); padding:20px;">
    <div style="background:#16181d; border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:32px; max-width:420px; width:100%; text-align:center; position:relative;">
        <button onclick="fecharModalPixAcademia()" style="position:absolute; top:16px; right:16px; background:none; border:none; color:#a0a0a0; font-size:1.2rem; cursor:pointer;">✕</button>
        <h3 style="color:#d4ff00; font-size:1.1rem; font-weight:900; margin:0 0 4px;">PAGAMENTO VIA PIX</h3>
        <p id="pixAcademiaDescricao" style="color:#a0a0a0; font-size:0.8rem; margin:0 0 8px;"></p>
        <p id="pixAcademiaValor" style="color:#fff; font-size:1.5rem; font-weight:700; margin:0 0 20px;"></p>
        <img id="pixAcademiaQr" src="" alt="QR Code" style="width:200px; height:200px; border-radius:12px; background:#fff; padding:8px; margin-bottom:16px;">
        <p style="color:#a0a0a0; font-size:0.8rem; margin:0 0 8px;">Ou copie o código Pix:</p>
        <div style="display:flex; gap:8px; margin-bottom:16px;">
            <input id="pixAcademiaCopia" type="text" readonly style="flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:8px 12px; color:#fff; font-size:0.75rem; outline:none;">
            <button onclick="copiarPixAcademia()" style="background:#d4ff00; color:#000; border:none; border-radius:8px; padding:8px 14px; font-weight:700; font-size:0.8rem; cursor:pointer; white-space:nowrap;">Copiar código</button>
        </div>
        <div style="display:flex; align-items:center; gap:8px; justify-content:center; color:#a0a0a0; font-size:0.8rem; margin-bottom:12px;">
            <div style="width:10px; height:10px; border:2px solid rgba(212,255,0,0.3); border-top-color:#d4ff00; border-radius:50%; animation:spinPix 1s linear infinite;"></div>
            Aguardando confirmação do pagamento...
        </div>
        <p id="pixAcademiaStatus" style="display:none; font-weight:700; font-size:0.9rem; margin:0;"></p>
    </div>
</div>

<script>
    let pixAcademiaPolling = null;

    function abrirPlanosAcademia(academiaId, academiaNome, planos) {
        document.getElementById('planosAcademiaNome').textContent = academiaNome;
        const lista = document.getElementById('planosAcademiaLista');

        if (!planos || planos.length === 0) {
            lista.innerHTML = '<p style="color:#a0a0a0; text-align:center; padding:20px;">Esta academia ainda não possui planos disponíveis.</p>';
        } else {
            lista.innerHTML = planos.map(p => `
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(212,255,0,0.2); border-radius:14px; padding:16px; margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                        <div>
                            <div style="font-weight:800; font-size:1rem; color:#d4ff00; margin-bottom:4px;">${p.nome}</div>
                            <div style="font-size:0.8rem; color:#a0a0a0;">
                                <i class="fas fa-calendar-alt"></i> ${p.duracao} ${p.duracao === 1 ? 'mês' : 'meses'}
                                ${p.descricao ? `<br><i class="fas fa-list-ul"></i> ${p.descricao}` : ''}
                            </div>
                        </div>
                        <div style="text-align:right; flex-shrink:0;">
                            <div style="font-size:1.2rem; font-weight:900; color:#fff;">R$ ${parseFloat(p.valor).toFixed(2).replace('.', ',')}</div>
                            <small style="color:#a0a0a0;">/mês</small>
                        </div>
                    </div>
                    <button onclick="pagarPlanoAcademia(${academiaId}, ${p.id}, '${p.nome}', '${academiaNome}', ${p.valor})"
                        style="width:100%; margin-top:12px; background:#d4ff00; color:#000; border:none; border-radius:8px; padding:10px; font-weight:900; font-size:0.8rem; cursor:pointer; transition:0.2s;"
                        onmouseover="this.style.background='#e8ff40'" onmouseout="this.style.background='#d4ff00'">
                        <i class="fas fa-qrcode"></i> Pagar via PIX
                    </button>
                </div>
            `).join('');
        }
        document.getElementById('modalPlanosAcademia').style.display = 'flex';
    }

    function fecharPlanosAcademia() {
        document.getElementById('modalPlanosAcademia').style.display = 'none';
    }

    async function pagarPlanoAcademia(academiaId, planoId, planoNome, academiaNome, valor) {
        fecharPlanosAcademia();

        document.getElementById('pixAcademiaDescricao').textContent = `Plano ${planoNome} — ${academiaNome}`;
        document.getElementById('pixAcademiaValor').textContent = 'Gerando QR Code...';
        document.getElementById('pixAcademiaQr').src = '';
        document.getElementById('pixAcademiaCopia').value = '';
        document.getElementById('pixAcademiaStatus').style.display = 'none';
        document.getElementById('modalPixAcademia').style.display = 'flex';

        try {
            const res = await fetch('/api/criar-pagamento-academia', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ academia_id: academiaId, plano_id: planoId }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao gerar pagamento.');

            document.getElementById('pixAcademiaQr').src = 'data:image/png;base64,' + data.pixQrCode;
            document.getElementById('pixAcademiaCopia').value = data.pixPayload;
            document.getElementById('pixAcademiaValor').textContent = 'R$ ' + parseFloat(data.amount).toFixed(2).replace('.', ',');

            pixAcademiaPolling = setInterval(async () => {
                try {
                    const sr = await fetch('/api/pagamento/status/' + data.asaasPaymentId, {
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    const sd = await sr.json();
                    if (sd.confirmed) {
                        clearInterval(pixAcademiaPolling);
                        const msg = document.getElementById('pixAcademiaStatus');
                        msg.textContent = '✅ Pagamento confirmado! Bem-vindo à academia!';
                        msg.style.color = 'var(--success)';
                        msg.style.display = 'block';
                        setTimeout(() => { window.location.reload(); }, 2500);
                    }
                } catch (_) {}
            }, 4000);

        } catch (err) {
            alert('Erro ao iniciar pagamento: ' + err.message);
            fecharModalPixAcademia();
        }
    }

    function fecharModalPixAcademia() {
        document.getElementById('modalPixAcademia').style.display = 'none';
        if (pixAcademiaPolling) { clearInterval(pixAcademiaPolling); pixAcademiaPolling = null; }
    }

    function copiarPixAcademia() {
        const input = document.getElementById('pixAcademiaCopia');
        input.select();
        document.execCommand('copy');
    }

    window.addEventListener('click', e => {
        if (e.target === document.getElementById('modalPlanosAcademia')) fecharPlanosAcademia();
        if (e.target === document.getElementById('modalPixAcademia')) fecharModalPixAcademia();
    });
</script>

</body>
</html>
