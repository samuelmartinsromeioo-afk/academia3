<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichas de Treino - {{ $cliente->nome }}</title>
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

                        <p class="ficha-nome">{{ $ficha->nome_treino }}</p>

                        @if($ficha->observacoes)
                            <p class="ficha-obs">{{ $ficha->observacoes }}</p>
                        @endif

                        <div class="exercicios-box">
                            <p class="exercicios-title"><i class="fas fa-list-ul"></i> Exercícios</p>
                            
                            @if($ficha->exercicios->isEmpty())
                                <p class="empty-ex">Nenhum exercício adicionado</p>
                            @else
                                <table class="exercicios-table">
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
                                            <td>{{ $exercicio->nome_exercicio }}</td>
                                            <td style="text-align: center;">{{ $exercicio->series }}</td>
                                            <td style="text-align: center;">{{ $exercicio->repeticoes }}</td>
                                            <td style="text-align: center;">{{ $exercicio->peso ? $exercicio->peso . ' kg' : '-' }}</td>
                                            <td style="text-align: center;">
                                                <button class="btn-delete-ex" onclick="deletarExercicio({{ $exercicio->id }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <button class="btn-add-ex" onclick="adicionarExercicio({{ $ficha->id }})">
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
                    <input type="text" name="nome_treino" placeholder="Ex: Peito e Costas" required>
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
            
            <form id="formAdicionarExercicio" method="POST">
                @csrf

                <div class="form-group">
                    <label>Nome do Exercício</label>
                    <input type="text" name="nome_exercicio" placeholder="Ex: Supino" required>
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
                    <label>Observações</label>
                    <textarea name="observacoes" placeholder="Técnica, cuidados, etc"></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="fecharModalAdicionarExercicio()">CANCELAR</button>
                    <button type="submit" class="btn-submit">ADICIONAR</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalNovaFicha = document.getElementById('modalNovaFicha');
        const modalEditarFicha = document.getElementById('modalEditarFicha');
        const modalAdicionarExercicio = document.getElementById('modalAdicionarExercicio');

        function abrirModalNovaFicha() {
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

        function adicionarExercicio(fichaId) {
            const form = document.getElementById('formAdicionarExercicio');
            form.action = `/personal/fichas-treino/${fichaId}/exercicio`;
            modalAdicionarExercicio.classList.add('active');
        }

        function fecharModalAdicionarExercicio() {
            modalAdicionarExercicio.classList.remove('active');
            document.getElementById('formAdicionarExercicio').reset();
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

        document.getElementById('btnNovaFicha').addEventListener('click', abrirModalNovaFicha);

        window.addEventListener('click', (e) => {
            if (e.target === modalNovaFicha) fecharModalNovaFicha();
            if (e.target === modalEditarFicha) fecharModalEditarFicha();
            if (e.target === modalAdicionarExercicio) fecharModalAdicionarExercicio();
        });
    </script>

</body>

</html>