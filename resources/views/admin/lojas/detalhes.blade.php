<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $loja->nome }} - Administração</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00; --bg-dark: #0a0b0d; --card-bg: #16181d;
            --text-main: #ffffff; --text-muted: #a0a0a0; --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04); --error: #ff4444; --success: #28a745;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%); font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; color: var(--text-main); }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center; padding: 20px 40px;
            background: rgba(0, 0, 0, 0.3); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
        }
        .top-bar h2 { font-size: 1.2rem; font-weight: 900; color: var(--primary); }
        .btn-back {
            background: transparent; border: 1px solid var(--primary); color: var(--primary);
            padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.8rem;
            transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-back:hover { background: var(--primary); color: #000; }

        .container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }

        .alert { background: rgba(40,167,69,0.15); border: 1px solid rgba(40,167,69,0.4); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #4caf50; font-weight: 700; }
        .alert-error { background: rgba(255,68,68,0.1); border-color: rgba(255,68,68,0.3); color: #ff6b6b; }

        .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 28px; margin-bottom: 24px; }
        .card h3 { color: var(--primary); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .info-item label { display: block; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
        .info-item span { font-size: 0.95rem; }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-aprovado { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.2); }
        .status-rejeitado { background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); }

        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px; text-align: left; font-weight: 800; font-size: 0.72rem; color: var(--primary); text-transform: uppercase; border-bottom: 2px solid var(--border); }
        td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        tbody tr:last-child td { border-bottom: none; }
        .preco { color: var(--primary); font-weight: 800; }
        .empty { color: var(--text-muted); text-align: center; padding: 24px 0; }

        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            border: none; border-radius: 10px; padding: 13px 20px; font-weight: 800; font-size: 0.82rem;
            text-transform: uppercase; cursor: pointer; transition: 0.2s; font-family: inherit;
            display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: transparent; border: 1px solid var(--error); color: var(--error); }
        .btn-danger:hover { background: var(--error); color: #fff; }
        .btn-warning { background: transparent; border: 1px solid #ffc107; color: #ffc107; }
        .btn-warning:hover { background: #ffc107; color: #000; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 1000; padding: 60px 20px; backdrop-filter: blur(6px); }
        .modal-content { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 32px; max-width: 480px; margin: 0 auto; }
        .modal-content h2 { margin-bottom: 16px; }
        .modal-content textarea {
            width: 100%; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px;
            padding: 12px; color: #fff; outline: none; font-family: inherit; margin-bottom: 16px;
        }
        .modal-close { float: right; background: transparent; border: none; color: var(--text-muted); font-size: 1.3rem; cursor: pointer; }
    </style>
</head>
<body>

<div class="top-bar">
    <h2><i class="fas fa-store"></i> {{ $loja->nome }}</h2>
    <a href="{{ route('admin.lojas.lista') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<div class="container">
    @if(session('success'))<div class="alert"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>@endif

    <div class="card">
        <h3><i class="fas fa-circle-info"></i> Dados da Loja</h3>
        <div class="info-grid">
            <div class="info-item"><label>Status</label>
                <span class="status-badge status-{{ $loja->status }}">{{ $loja->status === 'rejeitado' ? 'Bloqueada' : 'Ativa' }}</span>
            </div>
            <div class="info-item"><label>CNPJ</label><span>{{ $loja->cnpj }}</span></div>
            <div class="info-item"><label>E-mail</label><span>{{ $loja->email }}</span></div>
            <div class="info-item"><label>WhatsApp</label><span>{{ $loja->whatsapp ?? '—' }}</span></div>
            <div class="info-item"><label>Endereço</label><span>{{ $loja->endereco }}</span></div>
            <div class="info-item"><label>Cidade/UF</label><span>{{ $loja->cidade }}/{{ $loja->estado }}</span></div>
            <div class="info-item"><label>Cadastro</label><span>{{ $loja->created_at ? $loja->created_at->format('d/m/Y H:i') : '—' }}</span></div>
        </div>
        @if($loja->descricao)
            <div class="info-item" style="margin-top:18px;"><label>Descrição</label><span>{{ $loja->descricao }}</span></div>
        @endif
        @if($loja->status === 'rejeitado' && $loja->motivo_rejeicao)
            <div class="info-item" style="margin-top:18px;"><label>Motivo do bloqueio</label><span style="color:#ff6b6b;">{{ $loja->motivo_rejeicao }}</span></div>
        @endif
    </div>

    <div class="card">
        <h3><i class="fas fa-box-open"></i> Produtos ({{ $loja->produtos->count() }})</h3>
        @if($loja->produtos->count() > 0)
            <table>
                <thead>
                    <tr><th>Produto</th><th>Categoria</th><th>Preço</th><th style="text-align:center;">Estoque</th><th>Visível</th></tr>
                </thead>
                <tbody>
                    @foreach($loja->produtos as $produto)
                        <tr>
                            <td style="font-weight:600;">{{ $produto->nome }}</td>
                            <td style="color:var(--text-muted);">{{ $produto->categoria ?? '—' }}</td>
                            <td class="preco">R$ {{ number_format($produto->preco, 2, ',', '.') }}</td>
                            <td style="text-align:center;">{{ $produto->estoque }}</td>
                            <td>{{ $produto->ativo ? 'Sim' : 'Não' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="empty">Esta loja ainda não cadastrou produtos.</p>
        @endif
    </div>

    <div class="card">
        <h3><i class="fas fa-gear"></i> Ações</h3>
        <div class="actions">
            @if($loja->status === 'rejeitado')
                <form method="POST" action="{{ route('admin.lojas.reativar', $loja->id) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="fas fa-circle-check"></i> Reativar Loja</button>
                </form>
            @else
                <button type="button" class="btn btn-warning" onclick="document.getElementById('modalBloquear').style.display='block'">
                    <i class="fas fa-ban"></i> Bloquear Loja
                </button>
            @endif
            <form method="POST" action="{{ route('admin.lojas.deletar', $loja->id) }}" onsubmit="return confirm('Excluir permanentemente a loja {{ addslashes($loja->nome) }} e todos os seus produtos?');" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Excluir Permanentemente</button>
            </form>
        </div>
    </div>
</div>

{{-- MODAL BLOQUEAR --}}
<div id="modalBloquear" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="document.getElementById('modalBloquear').style.display='none'">&times;</button>
        <h2 style="color:#ffc107;"><i class="fas fa-ban"></i> Bloquear Loja</h2>
        <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:16px;">A loja perderá o acesso ao painel. Informe o motivo:</p>
        <form method="POST" action="{{ route('admin.lojas.bloquear', $loja->id) }}">
            @csrf
            <textarea name="motivo" rows="4" minlength="10" maxlength="500" placeholder="Ex: Produtos em desacordo com as políticas da plataforma..." required></textarea>
            <button type="submit" class="btn btn-warning" style="width:100%; justify-content:center;"><i class="fas fa-ban"></i> Confirmar Bloqueio</button>
        </form>
    </div>
</div>

</body>
</html>
