<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - {{ $academia->nome ?? 'Academia' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #d4ff00; --bg-dark: #0a0b0d; --card-bg: #16181d;
            --text-main: #ffffff; --text-muted: #a0a0a0; --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04); --error: #ff4444; --success: #28a745;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%); font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: var(--text-main); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; background: rgba(0,0,0,0.3); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .top-bar h2 { font-size: 1.1rem; font-weight: 900; color: var(--primary); }
        .btn-back { background: transparent; border: 1px solid var(--primary); color: var(--primary); padding: 10px 16px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back:hover { background: var(--primary); color: #000; }
        .container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .header-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 40px; margin-bottom: 32px; display: grid; grid-template-columns: auto 1fr; gap: 32px; align-items: start; }
        .photo-container { width: 150px; height: 150px; border-radius: 16px; background: rgba(212,255,0,0.1); border: 2px solid var(--primary); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 3rem; flex-shrink: 0; }
        .header-info h1 { font-size: 1.8rem; margin-bottom: 12px; }
        .header-info p { color: var(--text-muted); margin-bottom: 16px; font-size: 0.95rem; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; }
        .info-item { display: flex; align-items: center; gap: 12px; }
        .info-icon { width: 40px; height: 40px; background: rgba(212,255,0,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary); flex-shrink: 0; }
        .info-content h3 { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; margin-bottom: 4px; }
        .info-content p { font-size: 1rem; margin: 0; }
        .section-title { font-size: 1.2rem; font-weight: 900; color: var(--primary); margin-bottom: 20px; margin-top: 40px; display: flex; align-items: center; gap: 10px; }
        .section-title::before { content: ''; width: 4px; height: 20px; background: var(--primary); }
        .status-card, .info-section { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 28px; margin-bottom: 32px; }
        .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; margin-bottom: 16px; }
        .status-pendente { background: rgba(255,193,7,0.1); color: #ffc107; border: 1px solid rgba(255,193,7,0.2); }
        .status-aprovado { background: rgba(40,167,69,0.1); color: #28a745; border: 1px solid rgba(40,167,69,0.2); }
        .status-rejeitado { background: rgba(255,68,68,0.1); color: #ff4444; border: 1px solid rgba(255,68,68,0.2); }
        .action-buttons { display: flex; gap: 16px; margin-bottom: 32px; flex-wrap: wrap; align-items: center; }
        .action-buttons form { margin: 0; }
        .btn-approve, .btn-reject { padding: 14px 24px; border: none; border-radius: 10px; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-approve { background: var(--success); color: #fff; }
        .btn-approve:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(40,167,69,0.2); }
        .btn-reject { background: transparent; border: 1px solid var(--error); color: var(--error); }
        .btn-reject:hover { background: var(--error); color: #fff; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(8px); padding: 20px; }
        .modal.active { display: flex; }
        .modal-content { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 40px; width: 100%; max-width: 500px; }
        .modal-content h2 { margin-bottom: 16px; color: var(--error); }
        .modal-content p { color: var(--text-muted); margin-bottom: 24px; font-size: 0.95rem; }
        .modal-content textarea { width: 100%; background: var(--input-bg); border: 1px solid var(--border); color: var(--text-main); padding: 12px; border-radius: 10px; font-family: inherit; resize: vertical; min-height: 120px; margin-bottom: 20px; outline: none; }
        .modal-content textarea:focus { border-color: var(--primary); }
        .modal-buttons { display: flex; gap: 12px; }
        .modal-buttons button { flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-transform: uppercase; font-size: 0.8rem; }
        .modal-buttons .confirm { background: var(--error); color: #fff; }
        .modal-buttons .cancel { background: var(--input-bg); color: var(--text-main); border: 1px solid var(--border); }
        .info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; margin-bottom: 6px; }
        .info-value { font-size: 1rem; color: var(--text-main); }
        @media (max-width: 768px) { .header-card { grid-template-columns: 1fr; } .info-grid { grid-template-columns: 1fr; } .info-row { grid-template-columns: 1fr; } .action-buttons { flex-direction: column; } }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <h2><i class="ph ph-buildings"></i> {{ $academia->nome ?? 'N/A' }}</h2>
    <a href="{{ route('admin.academias.lista') }}" class="btn-back"><i class="ph ph-arrow-left"></i> Voltar</a>
</div>

<div class="container">
    @if(session('success'))
        <div style="background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.4); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #4caf50; font-weight: 700;">
            <i class="ph ph-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="header-card">
        <div class="photo-container"><i class="ph ph-buildings"></i></div>
        <div class="header-info">
            <h1>{{ $academia->nome ?? 'N/A' }}</h1>
            <p>{{ $academia->descricao ?? 'Sem descrição' }}</p>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon"><i class="ph ph-envelope"></i></div>
                    <div class="info-content"><h3>Email</h3><p>{{ $academia->email ?? 'N/A' }}</p></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ph ph-identification-card"></i></div>
                    <div class="info-content"><h3>CNPJ</h3><p>{{ $academia->cnpj ?? 'N/A' }}</p></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ph ph-users"></i></div>
                    <div class="info-content"><h3>Quantidade de alunos</h3><p>{{ $academia->quantidade_alunos ?? 'Não informado' }}</p></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ph ph-calendar"></i></div>
                    <div class="info-content"><h3>Cadastro</h3><p>{{ $academia->created_at ? $academia->created_at->format('d/m/Y') : 'N/A' }}</p></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ph ph-map-pin"></i></div>
                    <div class="info-content"><h3>Endereço</h3><p>{{ $academia->endereco ?? 'N/A' }}</p></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="ph ph-currency-circle-dollar"></i></div>
                    <div class="info-content"><h3>Valor mensal</h3><p>{{ $academia->valor_mensalidade ? 'R$ ' . number_format($academia->valor_mensalidade, 2, ',', '.') : 'A combinar' }}</p></div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="section-title">Status</h2>
    <div class="status-card">
        <span class="status-badge status-{{ $academia->status ?? 'pendente' }}">
            <i class="ph {{ ($academia->status ?? 'pendente') === 'aprovado' ? 'ph-check-circle' : 'ph-info' }}"></i>
            {{ ucfirst($academia->status ?? 'pendente') }}
        </span>
        @if($academia->status === 'rejeitado' && $academia->motivo_rejeicao)
            <div style="background: rgba(255,68,68,0.05); padding: 16px; border-radius: 10px; border-left: 4px solid var(--error); margin-top: 16px;">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 6px; text-transform: uppercase; font-weight: 700;"><i class="ph ph-prohibit"></i> Motivo da Rejeição</p>
                <p style="margin: 0; color: var(--text-main);">{{ $academia->motivo_rejeicao }}</p>
            </div>
        @endif
    </div>

    @if($academia->status === 'pendente')
        <h2 class="section-title">Ações</h2>
        <div class="action-buttons">
            <form method="POST" action="{{ route('admin.academias.aprovar', $academia->id) }}">
                @csrf
                <button type="submit" class="btn-approve"><i class="ph ph-check"></i> Aprovar Cadastro</button>
            </form>
            <button type="button" class="btn-reject" onclick="abrirModalRejeicao()"><i class="ph ph-x"></i> Rejeitar Cadastro</button>
        </div>
    @elseif($academia->status === 'rejeitado')
        <div style="background: rgba(255,68,68,0.1); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,68,68,0.2);">
            <p style="color: var(--error); margin: 0; font-weight: 700;"><i class="ph ph-warning-circle"></i> Este cadastro foi rejeitado</p>
        </div>
    @else
        <div style="background: rgba(40,167,69,0.1); padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(40,167,69,0.2);">
            <p style="color: var(--success); margin: 0; font-weight: 700;"><i class="ph ph-check-circle"></i> Esta academia foi aprovada</p>
        </div>
    @endif
</div>

<div class="modal" id="modalRejeicao">
    <div class="modal-content">
        <h2><i class="ph ph-warning-circle"></i> Rejeitar Cadastro</h2>
        <p>Explique o motivo pelo qual está rejeitando esta academia:</p>
        <form action="{{ route('admin.academias.rejeitar', $academia->id) }}" method="POST">
            @csrf
            <textarea name="motivo" placeholder="Digite o motivo da rejeição... (mínimo 10 caracteres)" required minlength="10"></textarea>
            <div class="modal-buttons">
                <button type="submit" class="confirm"><i class="ph ph-check"></i> Confirmar Rejeição</button>
                <button type="button" class="cancel" onclick="fecharModalRejeicao()"><i class="ph ph-x"></i> Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalRejeicao() { document.getElementById('modalRejeicao').classList.add('active'); }
    function fecharModalRejeicao() { document.getElementById('modalRejeicao').classList.remove('active'); }
    document.addEventListener('click', function (e) {
        if (e.target === document.getElementById('modalRejeicao')) fecharModalRejeicao();
    });
</script>

</body>
</html>
