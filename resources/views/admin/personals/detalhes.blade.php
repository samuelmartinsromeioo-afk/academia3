<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - {{ $personal->nome ?? 'Personal' }}</title>
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
            --error: #ff4444;
            --success: #28a745;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .btn-back {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.8rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: var(--primary);
            color: #000;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* CABEÇALHO COM FOTO E INFO */
        .header-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 32px;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 32px;
            align-items: start;
        }

        .photo-container {
            width: 150px;
            height: 150px;
            border-radius: 16px;
            background: rgba(212, 255, 0, 0.1);
            border: 2px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 3rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header-info h1 {
            font-size: 1.8rem;
            margin-bottom: 12px;
        }

        .header-info p {
            color: var(--text-muted);
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 24px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: rgba(212, 255, 0, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .info-content h3 {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .info-content p {
            font-size: 1rem;
            margin: 0;
        }

        /* SEÇÃO DE STATUS */
        .section-title {
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 20px;
            margin-top: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: var(--primary);
        }

        .status-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 32px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .status-pendente {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .status-aprovado {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .status-rejeitado {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.2);
        }

        /* FORMULÁRIOS DE AÇÃO */
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            align-items: center;
        }

        .action-buttons form {
            margin: 0;
        }

        .btn-approve,
        .btn-reject,
        .btn-delete {
            padding: 14px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-approve {
            background: var(--success);
            color: #fff;
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.2);
        }

        .btn-reject {
            background: transparent;
            border: 1px solid var(--error);
            color: var(--error);
        }

        .btn-reject:hover {
            background: var(--error);
            color: #fff;
        }

        .btn-delete {
            background: transparent;
            border: 1px solid var(--text-muted);
            color: var(--text-muted);
            margin-left: auto;
        }

        .btn-delete:hover {
            border-color: var(--error);
            color: var(--error);
        }

        .btn-view-cert {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-view-cert:hover {
            background: var(--primary);
            color: #000;
        }

        /* MODAL REJEIÇÃO */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }

        .modal-content h2 {
            margin-bottom: 16px;
            color: var(--error);
        }

        .modal-content p {
            color: var(--text-muted);
            margin-bottom: 24px;
            font-size: 0.95rem;
        }

        .modal-content textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 12px;
            border-radius: 10px;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            margin-bottom: 20px;
            outline: none;
        }

        .modal-content textarea:focus {
            border-color: var(--primary);
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .modal-buttons .confirm {
            background: var(--error);
            color: #fff;
        }

        .modal-buttons .cancel {
            background: var(--input-bg);
            color: var(--text-main);
            border: 1px solid var(--border);
        }

        /* GRID DE INFORMAÇÕES */
        .info-section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 32px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 16px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 1rem;
            color: var(--text-main);
        }

        /* CERTIFICADO */
        .cert-container {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 32px;
        }

        .cert-preview {
            width: 100%;
            max-height: 600px;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .cert-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .header-card {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-delete {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>

{{-- TOP BAR --}}
<div class="top-bar">
    <h2><i class="fas fa-user"></i> {{ $personal->nome ?? 'N/A' }}</h2>
    <a href="{{ route('admin.personals.lista') }}" class="btn-back">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="container">

    @if(session('success'))
        <div style="background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.4); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #4caf50; font-weight: 700;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: rgba(255,68,68,0.1); border: 1px solid rgba(255,68,68,0.3); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #ff6b6b; font-weight: 700;">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- CABEÇALHO COM FOTO E INFO BÁSICA --}}
    <div class="header-card">
        <div class="photo-container">
            @if($personal->foto)
                <img src="{{ asset('storage/' . $personal->foto) }}" alt="Foto de {{ $personal->nome ?? 'Personal' }}">
            @else
                <i class="fas fa-user"></i>
            @endif
        </div>

        <div class="header-info">
            <h1>{{ $personal->nome ?? 'N/A' }}</h1>
            <p>{{ $personal->especialidade ?? 'Especialidade não informada' }}</p>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p>{{ $personal->email ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Telefone</h3>
                        <p>{{ $personal->telefone ?? 'Não informado' }}</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="info-content">
                        <h3>Cadastro</h3>
                        <p>{{ $personal->created_at ? $personal->created_at->format('d/m/Y') : 'Data não disponível' }}</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-content">
                        <h3>Clientes</h3>
                        <p>{{ $totalClientes ?? 0 }} cliente(s)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CERTIFICADO --}}
    @if($personal->certificado)
        <h2 class="section-title">Certificado</h2>
        <div class="cert-container">
            @if(str_ends_with(strtolower($personal->certificado), '.pdf'))
                <iframe src="{{ asset('storage/' . $personal->certificado) }}" class="cert-preview" type="application/pdf"></iframe>
            @elseif(in_array(strtolower(pathinfo($personal->certificado, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                <img src="{{ asset('storage/' . $personal->certificado) }}" alt="Certificado" class="cert-preview">
            @endif
            
            <div class="cert-actions">
                <a href="{{ asset('storage/' . $personal->certificado) }}" target="_blank" class="btn-view-cert" download>
                    <i class="fas fa-download"></i> Baixar Certificado
                </a>
                <a href="{{ asset('storage/' . $personal->certificado) }}" target="_blank" class="btn-view-cert">
                    <i class="fas fa-eye"></i> Abrir em Nova Aba
                </a>
            </div>
        </div>
    @endif

    {{-- STATUS ATUAL --}}
    <h2 class="section-title">Status</h2>
    <div class="status-card">
        <span class="status-badge status-{{ $personal->status ?? 'pendente' }}">
            <i class="fas fa-circle-{{ ($personal->status ?? 'pendente') === 'aprovado' ? 'check' : 'info' }}"></i>
            {{ ucfirst($personal->status ?? 'pendente') }}
        </span>

        @if($personal->status === 'rejeitado' && $personal->motivo_rejeicao)
            <div style="background: rgba(255, 68, 68, 0.05); padding: 16px; border-radius: 10px; border-left: 4px solid var(--error); margin-top: 16px;">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 6px; text-transform: uppercase; font-weight: 700;">
                    <i class="fas fa-ban"></i> Motivo da Rejeição
                </p>
                <p style="margin: 0; color: var(--text-main);">{{ $personal->motivo_rejeicao }}</p>
            </div>
        @endif
    </div>

    {{-- DADOS FINANCEIROS --}}
    <h2 class="section-title">Financeiro</h2>
    <div class="info-section">
        <div class="info-row">
            <div>
                <p class="info-label">Aulas de Pacote (Mês)</p>
                <p class="info-value" style="color: var(--primary); font-size: 1.3rem; font-weight: 700;">
                    R$ {{ number_format($financeiro['pacotes'] ?? 0, 2, ',', '.') }}
                </p>
            </div>
            <div>
                <p class="info-label">Aulas Avulsas (Mês)</p>
                <p class="info-value" style="color: var(--primary); font-size: 1.3rem; font-weight: 700;">
                    R$ {{ number_format($financeiro['avulsas'] ?? 0, 2, ',', '.') }}
                </p>
            </div>
        </div>
        <div style="padding-top: 20px; border-top: 1px solid var(--border); margin-top: 20px;">
            <p class="info-label">Total (Mês)</p>
            <p class="info-value" style="color: #00ff88; font-size: 1.5rem; font-weight: 900;">
                R$ {{ number_format(($financeiro['pacotes'] ?? 0) + ($financeiro['avulsas'] ?? 0), 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- AÇÕES --}}
    @if($personal->status === 'pendente')
        <h2 class="section-title">Ações</h2>
        <div class="action-buttons">
            <form method="POST" action="{{ route('admin.personals.aprovar', $personal->id) }}">
                @csrf
                <button type="submit" class="btn-approve">
                    <i class="fas fa-check"></i> Aprovar Cadastro
                </button>
            </form>

            <button type="button" class="btn-reject" onclick="abrirModalRejeicao()">
                <i class="fas fa-times"></i> Rejeitar Cadastro
            </button>

            <form method="POST" action="{{ route('admin.personals.deletar', $personal->id) }}" onsubmit="return confirm('Tem certeza? Esta ação é irreversível!');" style="margin-left: auto;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-delete">
                    <i class="fas fa-trash"></i> Deletar
                </button>
            </form>
        </div>
    @elseif($personal->status === 'rejeitado')
        <div style="background: rgba(255, 68, 68, 0.1); padding: 20px; border-radius: 12px; border: 1px solid rgba(255, 68, 68, 0.2);">
            <p style="color: var(--error); margin: 0; font-weight: 700;">
                <i class="fas fa-exclamation-circle"></i> Este cadastro foi rejeitado
            </p>
        </div>
    @else
        <h2 class="section-title">Ações</h2>

        <div style="background: rgba(40, 167, 69, 0.1); padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(40, 167, 69, 0.2); margin-bottom: 16px;">
            <p style="color: var(--success); margin: 0; font-weight: 700;">
                <i class="fas fa-check-circle"></i> Este personal foi aprovado
            </p>
        </div>

        {{-- Status da conta Asaas --}}
        @if($personal->asaas_wallet_id)
            <div style="background: rgba(212, 255, 0, 0.07); padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(212, 255, 0, 0.3); margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-check-circle" style="color: var(--primary); font-size: 1.2rem;"></i>
                <div>
                    <p style="margin: 0; font-weight: 700; color: #fff; font-size: 0.9rem;">Conta Asaas configurada</p>
                    <p style="margin: 0; color: #a0a0a0; font-size: 0.75rem;">Split de pagamentos ativo — 90% vai direto para a carteira deste personal.</p>
                    <p style="margin: 4px 0 0; color: #a0a0a0; font-size: 0.72rem; font-family: monospace;">Wallet ID: {{ $personal->asaas_wallet_id }}</p>
                </div>
            </div>
        @else
            <div style="background: rgba(255, 165, 0, 0.08); padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(255, 165, 0, 0.3); margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-exclamation-triangle" style="color: #ffa500; font-size: 1.2rem;"></i>
                    <div>
                        <p style="margin: 0; font-weight: 700; color: #fff; font-size: 0.9rem;">Conta Asaas não configurada</p>
                        <p style="margin: 0; color: #a0a0a0; font-size: 0.75rem;">Os repasses estão sendo feitos manualmente via PIX.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.personals.criar-asaas', $personal->id) }}"
                    onsubmit="return confirm('Criar conta Asaas para {{ $personal->nome }}? Os dados serão enviados à Asaas agora.');">
                    @csrf
                    <button type="submit" style="background: #ffa500; color: #000; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 900; font-size: 0.82rem; cursor: pointer; white-space: nowrap;">
                        <i class="fas fa-plus-circle"></i> Criar Conta Asaas
                    </button>
                </form>
            </div>
        @endif

        <div class="action-buttons" style="margin-top: 8px;">
            <form method="POST" action="{{ route('admin.personals.deletar', $personal->id) }}"
                onsubmit="return confirm('Tem certeza? Esta ação é irreversível!');" style="margin-left: auto;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-delete">
                    <i class="fas fa-trash"></i> Deletar Personal
                </button>
            </form>
        </div>
    @endif
</div>

{{-- MODAL DE REJEIÇÃO --}}
<div class="modal" id="modalRejeicao">
    <div class="modal-content">
        <h2><i class="fas fa-exclamation-circle"></i> Rejeitar Cadastro</h2>
        <p>Explique o motivo pelo qual está rejeitando este personal:</p>

        <form action="{{ route('admin.personals.rejeitar', $personal->id) }}" method="POST">
            @csrf
            <textarea 
                name="motivo" 
                placeholder="Digite o motivo da rejeição... (mínimo 10 caracteres)" 
                required
                minlength="10"
            ></textarea>

            <div class="modal-buttons">
                <button type="submit" class="confirm">
                    <i class="fas fa-check"></i> Confirmar Rejeição
                </button>
                <button type="button" class="cancel" onclick="fecharModalRejeicao()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalRejeicao() {
        document.getElementById('modalRejeicao').classList.add('active');
    }

    function fecharModalRejeicao() {
        document.getElementById('modalRejeicao').classList.remove('active');
    }

    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modalRejeicao');
        if (e.target === modal) {
            fecharModalRejeicao();
        }
    });
</script>

</body>
</html>