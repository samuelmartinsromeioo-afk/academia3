<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - {{ $academia->nome }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255, 255, 255, 0.08);
            --success: #00ff88;
            --error: #ff4444;
        }

        body {
            background: var(--bg-dark);
            font-family: Inter, sans-serif;
            color: var(--text-main);
            margin: 0;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }

        .avatar-img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            object-fit: cover;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 700;
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
            transition: 0.3s;
        }

        .dots-btn:hover {
            border-color: var(--primary);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 50px;
            left: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 240px;
            overflow: hidden;
            z-index: 10;
        }

        .dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            width: 100%;
            border: none;
            background: none;
            color: #fff;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
        }

        .dropdown-menu button:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--primary);
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 30px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid var(--border);
            transition: 0.3s;
        }
        
        .card:hover {
            border-color: rgba(212, 255, 0, 0.3);
            transform: translateY(-2px);
        }

        .card h3 {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 900;
            margin-top: 10px;
            color: var(--primary);
        }

        .btn {
            background: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 800;
            color: var(--bg-dark);
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn:hover {
            box-shadow: 0 5px 15px rgba(212, 255, 0, 0.2);
        }

        .students-list {
            margin-top: 30px;
            background: var(--card-bg);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .student {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 12px;
            border-bottom: 1px solid var(--border);
        }

        .student:last-child {
            border: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
        }

        .list-action-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
            transition: 0.3s;
        }

        .list-action-link:hover {
            opacity: 0.8;
        }
    </style>
</head>

<body>

    <div class="top-bar">
        <div class="menu-container">
            <button class="dots-btn" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>

            <div class="dropdown-menu" id="menu">
                <button onclick="openPerfil()">
                    <i class="fas fa-user"></i> Perfil
                </button>

                {{-- Botão Alunos agora aponta para a rota que lista todos --}}
                <button onclick="location.href='{{ route('academia.alunos') }}'">
                    <i class="fas fa-users"></i> Alunos
                </button>

                <button onclick="location.href='{{ route('academia.planos') }}'">
                    <i class="fas fa-dumbbell"></i> Planos
                </button>

                <button onclick="openFinance()">
                    <i class="fas fa-wallet" style="color:var(--success)"></i> Financeiro
                </button>

                <form action="{{ route('login.logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" style="color:var(--error)">
                        <i class="fas fa-power-off"></i> Sair
                    </button>
                </form>
            </div>
        </div>

        <div class="profile-header">
            <span>{{ $academia->nome }}</span>
            <img class="avatar-img"
                src="{{ $academia->logo ? asset('storage/' . $academia->logo) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" alt="Logo Academia">
        </div>
    </div>

    <div class="container">
        @if ($errors->any())
            <div style="background: rgba(255,68,68,0.1); border: 1px solid var(--error); color: var(--text-main); padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <strong>Atenção:</strong>
                <ul style="margin-top: 10px; margin-bottom: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h1 style="margin-bottom:30px;">Painel da Academia</h1>

        <div class="cards">
            <div class="card">
                <h3>Total de Alunos</h3>
                <div class="card-value">{{ $totalAlunos }}</div>
            </div>

            <div class="card">
                <h3>Planos Ativos</h3>
                <div class="card-value">{{ $planosAtivos }}</div>
            </div>

            <div class="card">
                <h3>Faturamento Estimado</h3>
                <div class="card-value">R$ {{ number_format($faturamento, 2, ',', '.') }}</div>
            </div>

            <div class="card">
                <h3>Treinadores</h3>
                <div class="card-value">{{ $personals }}</div>
            </div>
        </div>

        <div class="students-list">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                {{-- O título muda conforme a variável verTodos que o controller envia --}}
                <h2 style="margin: 0;">{{ (isset($verTodos) && $verTodos) ? 'Todos os Clientes' : 'Últimos Clientes' }}</h2>
                
                {{-- Link dinâmico: Se estiver vendo todos, mostra o 'X' para voltar ao dashboard normal --}}
                @if(isset($verTodos) && $verTodos)
                    <a href="{{ route('academia.dashboard') }}" class="list-action-link">
                        <i class="fas fa-times"></i> Fechar Lista
                    </a>
                @else
                    <a href="{{ route('academia.alunos') }}" class="list-action-link">
                        Ver Todos
                    </a>
                @endif
            </div>

            @forelse($alunos as $aluno)
                <div class="student">
                    <div>
                        <strong>{{ $aluno->nome }}</strong>
                        <br>
                        <small style="color:var(--text-muted)">
                            {{-- Ajustado para mostrar o e-mail e plano do cliente --}}
                            {{ $aluno->email }} | Plano: {{ $aluno->plano ?? 'plano padrão' }}
                        </small>
                    </div>
                    <div>
                        {{-- Link para a visualização individual do cliente --}}
                        <button class="btn" onclick="location.href='/academia/cliente/{{ $aluno->id }}'">
                            Ver
                        </button>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>Nenhum cliente cadastrado ainda.</p>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById("menu");
            menu.style.display = menu.style.display === "block" ? "none" : "block";
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dots-btn') && !event.target.matches('.fa-bars')) {
                const menu = document.getElementById("menu");
                if (menu && menu.style.display === "block") {
                    menu.style.display = "none";
                }
            }
        }

        function openPerfil() {
            alert("Abrir perfil da academia");
        }

        function openFinance() {
            alert("Abrir relatório financeiro");
        }
    </script>
</body>
</html>