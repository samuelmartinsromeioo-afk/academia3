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
        }

        .dropdown-menu button {
            display: flex;
            gap: 10px;
            padding: 15px;
            width: 100%;
            border: none;
            background: none;
            color: #fff;
            cursor: pointer;
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
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
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
            padding: 12px;
            border-bottom: 1px solid var(--border);
        }

        .student:last-child {
            border: none;
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

                <button onclick="location.href='{{ route('academia.alunos') }}'">
                    <i class="fas fa-users"></i> Alunos
                </button>

                <button onclick="location.href='{{ route('academia.planos') }}'">
                    <i class="fas fa-dumbbell"></i> Planos
                </button>

                <button onclick="openFinance()">
                    <i class="fas fa-wallet" style="color:var(--success)"></i> Financeiro
                </button>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" style="color:var(--error)">
                        <i class="fas fa-power-off"></i> Sair
                    </button>
                </form>

            </div>
        </div>

        <div class="profile-header">

            <img class="avatar-img"
                src="{{ $academia->logo ? asset('storage/' . $academia->logo) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}">

            <span>{{ $academia->nome }}</span>

        </div>

    </div>

    <div class="container">

        @if ($errors->any())
            <div style="background: rgba(255,0,0,0.2); border: 1px solid #ff4444; color: #fff; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <strong>Erro ao cadastrar:</strong>
                <ul>
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
                <div class="card-value">
                    {{ $totalAlunos }}
                </div>
            </div>

            <div class="card">
                <h3>Planos Ativos</h3>
                <div class="card-value">
                    {{ $planosAtivos }}
                </div>
            </div>

            <div class="card">
                <h3>Faturamento Mensal</h3>
                <div class="card-value">
                    R$ {{ number_format($faturamento, 2, ',', '.') }}
                </div>
            </div>

            <div class="card">
                <h3>Treinadores</h3>
                <div class="card-value">
                    {{ $personais }}
                </div>
            </div>

        </div>

        <div class="students-list">

            <h2 style="margin-bottom:20px;">Últimos Alunos</h2>

            @foreach($alunos as $aluno)

                <div class="student">

                    <div>
                        <strong>{{ $aluno->nome }}</strong>
                        <br>
                        <small style="color:var(--text-muted)">
                            Plano: {{ $aluno->plano ?? 'sem plano' }}
                        </small>
                    </div>

                    <div>
                        <button class="btn" onclick="location.href='/academia/aluno/{{ $aluno->id }}'">
                            Ver
                        </button>
                    </div>

                </div>

            @endforeach

        </div>

    </div>

    <script>

        function toggleMenu() {

            const menu = document.getElementById("menu")

            menu.style.display =
                menu.style.display === "block"
                    ? "none"
                    : "block"

        }

        function openPerfil() {

            alert("Abrir perfil da academia")

        }

        function openFinance() {

            alert("Abrir relatório financeiro")

        }

    </script>

</body>

</html>