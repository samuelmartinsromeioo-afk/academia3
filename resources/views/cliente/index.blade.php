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
        }

        body { background-color: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; padding: 0; overflow-x: hidden; }
        
        /* Top Bar */
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .menu-container { position: relative; }
        .dots-btn { background: var(--card-bg); border: 1px solid var(--border); color: var(--primary); width: 40px; height: 40px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .dots-btn:hover { background: var(--primary); color: #000; }
        
        .dropdown-menu { display: none; position: absolute; top: 50px; left: 0; background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; width: 220px; z-index: 1000; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.6); }
        .dropdown-menu button, .dropdown-menu a { display: flex; align-items: center; gap: 12px; padding: 15px 20px; color: #fff; text-decoration: none; font-size: 14px; width: 100%; text-align: left; background: none; border: none; cursor: pointer; transition: 0.2s; }
        .dropdown-menu button:hover, .dropdown-menu a:hover { background: rgba(255,255,255,0.05); color: var(--primary); }

        .profile-header { display: flex; align-items: center; gap: 20px; }
        .avatar-img { width: 45px; height: 45px; border-radius: 50%; border: 2px solid var(--primary); object-fit: cover; }

        /* Container & Layout */
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        
        /* Dashboard Resumo */
        .dashboard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 20px; border: 1px solid var(--border); text-align: center; transition: 0.3s; }
        .stat-card:hover { border-color: rgba(212, 255, 0, 0.3); }
        .stat-card i { color: var(--primary); font-size: 1.5rem; margin-bottom: 10px; display: block; }
        .stat-card span { display: block; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; font-weight: 800; }
        .stat-card h2 { margin: 5px 0 0; font-size: 1.5rem; }

        /* Meus Treinos */
        .treino-item { background: var(--card-bg); padding: 20px; border-radius: 15px; border-left: 4px solid var(--primary); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .badge-status { background: rgba(212, 255, 0, 0.1); color: var(--primary); padding: 5px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }

        /* Vitrine de Personals */
        .personal-card { text-align: left; position: relative; overflow: hidden; }
        .personal-card img { width: 50px; height: 50px; border-radius: 12px; border: 1px solid var(--primary); object-fit: cover; }

        /* Form e Modais */
        #editFormContainer, #agendaModal { display: none; }
        #editFormContainer { animation: fadeIn 0.4s ease; margin-bottom: 50px; }
        
        .profile-card { background: var(--card-bg); border-radius: 24px; padding: 35px; border: 1px solid var(--border); position: relative; }
        .close-form { position: absolute; top: 20px; right: 25px; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; transition: 0.2s; }
        .close-form:hover { color: var(--primary); transform: rotate(90deg); }

        .section-title { color: var(--primary); font-size: 0.8rem; margin: 30px 0 15px 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .section-title::after { content: ""; flex: 1; height: 1px; background: var(--border); }

        .form-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; }
        .col-6 { grid-column: span 6; }
        .col-3 { grid-column: span 3; }
        .col-2 { grid-column: span 2; }

        label { font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; margin-bottom: 5px; display: block; }
        
        .input-wrapper { display: flex; align-items: center; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px; padding: 0 12px; }
        .input-wrapper i { color: var(--primary); width: 18px; font-size: 0.9rem; }
        .input-wrapper input, .input-wrapper select, .input-wrapper textarea { flex: 1; background: transparent; border: none; padding: 12px; color: #fff; outline: none; font-size: 0.9rem; font-family: inherit; }
        .input-wrapper textarea { resize: none; height: 80px; }

        .btn-action { background: var(--primary); color: #000; width: 100%; padding: 18px; border-radius: 12px; font-weight: 900; border: none; cursor: pointer; text-transform: uppercase; transition: 0.3s; font-size: 0.8rem; margin-top: 20px; }
        .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(212, 255, 0, 0.15); }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1001; display: none; justify-content: center; align-items: center; backdrop-filter: blur(8px); }
        .horario-item { background: var(--input-bg); padding: 15px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .col-3, .col-2, .col-4 { grid-column: span 6; }
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
            <form action="{{ route('logout') }}" method="POST">
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
    <header id="mainHeader" style="margin-bottom: 30px;">
        <h1 style="margin:0; font-size: 1.8rem; font-weight: 900; letter-spacing: -1px;">CENTRAL DO ALUNO</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Bem-vindo de volta, foque nos seus objetivos.</p>
    </header>

    <div id="editFormContainer">
        <form action="{{ route('cliente.update', $cliente->id) }}" method="POST" class="profile-card">
            @csrf @method('PUT')
            <i class="fas fa-times close-form" onclick="toggleEditForm()"></i>
            
            <div class="section-title">Dados de Acesso</div>
            <div class="form-grid">
                <div class="col-3">
                    <label>Nome Completo</label>
                    <div class="input-wrapper"><i class="fas fa-user"></i><input type="text" name="nome" value="{{ $cliente->nome }}"></div>
                </div>
                <div class="col-3">
                    <label>E-mail</label>
                    <div class="input-wrapper"><i class="fas fa-envelope"></i><input type="email" name="email" value="{{ $cliente->email }}"></div>
                </div>
                <div class="col-3">
                    <label>Nova Senha (deixe em branco para manter)</label>
                    <div class="input-wrapper"><i class="fas fa-lock"></i><input type="password" name="senha" placeholder="********"></div>
                </div>
                <div class="col-3">
                    <label>Sexo</label>
                    <div class="input-wrapper">
                        <i class="fas fa-venus-mars"></i>
                        <select name="sexo">
                            <option value="M" {{ $cliente->sexo == 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ $cliente->sexo == 'F' ? 'selected' : '' }}>Feminino</option>
                            <option value="O" {{ $cliente->sexo == 'O' ? 'selected' : '' }}>Outro</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="section-title">Localização</div>
            <div class="form-grid">
                <div class="col-2">
                    <label>CEP</label>
                    <div class="input-wrapper"><i class="fas fa-map-marker-alt"></i><input type="text" name="cep" value="{{ $cliente->cep }}"></div>
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

            <div class="section-title">Ficha Técnica & Objetivos</div>
            <div class="form-grid">
                <div class="col-2">
                    <label>Altura (cm)</label>
                    <div class="input-wrapper"><i class="fas fa-ruler-vertical"></i><input type="number" name="altura" value="{{ $cliente->altura }}"></div>
                </div>
                <div class="col-2">
                    <label>Peso (kg)</label>
                    <div class="input-wrapper"><i class="fas fa-weight"></i><input type="number" step="0.1" name="peso" value="{{ $cliente->peso }}"></div>
                </div>
                <div class="col-2">
                    <label>Idade</label>
                    <div class="input-wrapper"><i class="fas fa-calendar-day"></i><input type="number" name="idade" value="{{ $cliente->idade }}"></div>
                </div>
                <div class="col-6">
                    <label>Frequência Semanal</label>
                    <div class="input-wrapper">
                        <i class="fas fa-dumbbell"></i>
                        <select name="frequencia_semanal">
                            <option value="1-2" {{ $cliente->frequencia_semanal == '1-2' ? 'selected' : '' }}>1 a 2 vezes</option>
                            <option value="3-4" {{ $cliente->frequencia_semanal == '3-4' ? 'selected' : '' }}>3 a 4 vezes</option>
                            <option value="5+" {{ $cliente->frequencia_semanal == '5+' ? 'selected' : '' }}>5 ou mais</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <label>Resumo do Objetivo</label>
                    <div class="input-wrapper"><textarea name="resumo_objetivo">{{ $cliente->resumo_objetivo }}</textarea></div>
                </div>
                <div class="col-6">
                    <label>Condição Clínica / Observações</label>
                    <div class="input-wrapper"><textarea name="condicao_clinica">{{ $cliente->condicao_clinica }}</textarea></div>
                </div>
            </div>

            <button type="submit" class="btn-action">Atualizar Perfil Completo</button>
        </form>
    </div>

    <div id="dashboardSummary">
        <div class="dashboard-grid">
            <div class="stat-card">
                <i class="fas fa-weight"></i>
                <span>Peso</span>
                <h2>{{ $cliente->peso ?? '--' }} <small style="font-size: 0.8rem;">kg</small></h2>
            </div>
            <div class="stat-card">
                <i class="fas fa-ruler-vertical"></i>
                <span>Altura</span>
                <h2>{{ $cliente->altura ?? '--' }} <small style="font-size: 0.8rem;">cm</small></h2>
            </div>
            <div class="stat-card">
                <i class="fas fa-fire"></i>
                <span>Idade</span>
                <h2>{{ $cliente->idade ?? '--' }} <small style="font-size: 0.8rem;">anos</small></h2>
            </div>
        </div>

        <div class="section-title">Minha Agenda de Treinos</div>
        @forelse($meusAgendamentos as $agendamento)
            <div class="treino-item">
                <div>
                    <strong style="display: block; font-size: 1rem;">{{ $agendamento->personal->nome }}</strong>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">
                        <i class="far fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($agendamento->data)->format('d/m/Y') }} 
                        às {{ \Carbon\Carbon::parse($agendamento->horario_inicio)->format('H:i') }}
                    </span>
                </div>
                <div class="badge-status">Confirmado</div>
            </div>
        @empty
            <div class="stat-card" style="padding: 20px; border-style: dashed;">
                <p style="color: var(--text-muted); margin: 0; font-size: 0.85rem;">Você não possui treinos agendados.</p>
            </div>
        @endforelse

        <div class="section-title">Personals Disponíveis</div>
        <div class="dashboard-grid" style="grid-template-columns: repeat(2, 1fr);">
            @foreach($personals as $p)
            <div class="stat-card personal-card">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($p->nome) }}&background=000&color=d4ff00">
                    <div>
                        <h3 style="margin:0; font-size: 0.9rem;">{{ $p->nome }}</h3>
                        <p style="margin:0; font-size: 0.6rem; color: var(--primary);">Ativo na plataforma</p>
                    </div>
                </div>
                <button onclick="abrirAgenda('{{ $p->id }}', '{{ $p->nome }}')" class="btn-action btn-outline" style="padding: 10px; font-size: 0.7rem; margin-top: 15px;">
                    <i class="fas fa-calendar-check"></i> Ver Agenda
                </button>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div id="agendaModal" class="modal-overlay">
    <div class="profile-card" style="width: 90%; max-width: 450px; border: 1px solid var(--primary);">
        <i class="fas fa-times close-form" onclick="fecharAgenda()"></i>
        <h2 id="nomePersonalAgenda" style="color: var(--primary); margin-bottom: 5px;">Personal</h2>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 20px;">Selecione um horário disponível:</p>
        
        <div id="listaHorarios" style="max-height: 400px; overflow-y: auto;">
            @forelse($horariosDisponiveis as $h)
            <div class="horario-item personal-horario-{{ $h->personal_id }}" style="display: none;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--primary);">
                        {{ \Carbon\Carbon::parse($h->data)->translatedFormat('d \d\e F (D)') }}
                    </span>
                    <span style="font-size: 0.75rem; color: #fff;">
                        {{ $h->horario_inicio }} às {{ $h->horario_fim }}
                    </span>
                </div>
                <form action="{{ route('agendar.horario') }}" method="POST">
                        @csrf

                        <input type="hidden" name="personal_id" value="{{ $h->personal_id }}">
                        <input type="hidden" name="data" value="{{ $h->data }}">
                        <input type="hidden" name="horario_inicio" value="{{ $h->horario_inicio }}">
                        <input type="hidden" name="horario_fim" value="{{ $h->horario_fim }}">

                        <div style="margin-bottom:10px;">
                            <select name="academia_id" required 
                            style="background:#111;border:1px solid #333;color:#fff;padding:6px;border-radius:6px;font-size:12px;width:100%;">
                                <option value="">Escolha a academia</option>

                                @foreach($academias as $academia)
                                    <option value="{{ $academia->id }}">
                                        {{ $academia->nome }} - {{ $academia->cidade }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <button type="submit" class="btn-action" style="width:auto; margin:0; padding: 8px 15px; font-size: 0.7rem;">
                            Agendar
                        </button>
                    </form>
            </div>
            @empty
                <p style="text-align: center; color: var(--text-muted);">Sem horários.</p>
            @endforelse
            <p id="msgSemHorario" style="display: none; text-align: center; color: var(--text-muted); padding: 20px;">Sem horários livres para este profissional.</p>
        </div>
    </div>
</div>

<script>
    function toggleEditForm() {
        const summary = document.getElementById('dashboardSummary');
        const form = document.getElementById('editFormContainer');
        const header = document.getElementById('mainHeader');
        const menu = document.getElementById('dropdownMenu');
        
        const isOpening = form.style.display === 'none' || form.style.display === '';
        
        form.style.display = isOpening ? 'block' : 'none';
        summary.style.display = isOpening ? 'none' : 'block';
        header.style.display = isOpening ? 'none' : 'block';
        menu.style.display = 'none'; // Fecha o menu ao abrir o form
    }

    function toggleMenu() {
        const menu = document.getElementById('dropdownMenu');
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    function abrirAgenda(id, nome) {
        document.getElementById('nomePersonalAgenda').innerText = nome;
        const todosHorarios = document.querySelectorAll('.horario-item');
        todosHorarios.forEach(h => h.style.display = 'none');

        const filtrados = document.querySelectorAll('.personal-horario-' + id);
        const msgVazio = document.getElementById('msgSemHorario');

        if (filtrados.length > 0) {
            filtrados.forEach(h => h.style.display = 'flex');
            msgVazio.style.display = 'none';
        } else {
            msgVazio.style.display = 'block';
        }
        document.getElementById('agendaModal').style.display = 'flex';
    }

    function fecharAgenda() {
        document.getElementById('agendaModal').style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target.className === 'modal-overlay') fecharAgenda();
        if (!e.target.closest('.menu-container')) {
            document.getElementById('dropdownMenu').style.display = 'none';
        }
    }
</script>
</body>
</html>