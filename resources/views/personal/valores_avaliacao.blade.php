<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valores das Avaliações — {{ $personal->nome }}</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --success: #00ff88;
            --error: #ff4444;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg-dark); font-family: 'Inter', sans-serif; color: var(--text-main); margin: 0; padding: 0; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: rgba(0,0,0,0.4); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .page-title { color: var(--primary); font-size: 1.4rem; font-weight: 900; margin: 0 0 6px; }
        .page-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 30px; }
        .card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); padding: 24px; margin-bottom: 16px; transition: 0.3s; }
        .card:hover { border-color: rgba(212,255,0,0.2); }
        .btn-primary { background: var(--primary); color: #000; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 900; font-size: 0.8rem; cursor: pointer; text-transform: uppercase; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(212,255,0,0.2); }
        .btn-back { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-weight: 700; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-back:hover { border-color: var(--primary); color: var(--primary); }
        .section-label { font-size: 0.7rem; color: var(--primary); text-transform: uppercase; font-weight: 900; letter-spacing: 1px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .section-label::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .alert-success { background: rgba(0,255,136,0.08); border: 1px solid rgba(0,255,136,0.3); color: var(--success); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }
        .alert-error { background: rgba(255,68,68,0.08); border: 1px solid rgba(255,68,68,0.3); color: var(--error); padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 700; }
        @media (max-width: 600px) { .top-bar { padding: 15px 20px; } }

        /* Preços avulsos por tipo */
        .preco-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 12px; }
        .preco-item { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; }
        .preco-item label { display: flex; align-items: center; gap: 8px; color: var(--text-main); font-size: 0.8rem; font-weight: 700; margin-bottom: 8px; }
        .preco-item label i { color: var(--primary); width: 16px; text-align: center; }
        .preco-input-wrap { display: flex; align-items: center; gap: 6px; background: rgba(0,0,0,0.25); border: 1px solid var(--border); border-radius: 8px; padding: 0 10px; }
        .preco-input-wrap span { color: var(--text-muted); font-size: 0.8rem; font-weight: 800; }
        .preco-input-wrap input { background: transparent; border: none; color: #fff; padding: 10px 0; font-size: 0.9rem; outline: none; width: 100%; }
        .preco-hint { color: var(--text-muted); font-size: 0.72rem; margin: 6px 0 16px; }

        /* Pacotes */
        .btn-outline { background: transparent; border: 1px dashed rgba(212,255,0,0.5); color: var(--primary); padding: 12px 18px; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-outline:hover { background: rgba(212,255,0,0.08); }
        .pacote-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; padding: 18px 20px; margin-bottom: 12px; }
        .pacote-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
        .pacote-nome { font-size: 1rem; font-weight: 900; margin: 0; }
        .pacote-valor { color: var(--primary); font-size: 1.1rem; font-weight: 900; }
        .pacote-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .chip { background: rgba(212,255,0,0.08); border: 1px solid rgba(212,255,0,0.25); color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 800; }
        .pacote-actions { display: flex; gap: 8px; }
        .btn-icon { background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-main); padding: 7px 12px; border-radius: 8px; font-size: 0.72rem; font-weight: 800; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .btn-icon:hover { border-color: var(--primary); color: var(--primary); }
        .btn-icon.danger:hover { border-color: var(--error); color: var(--error); }
        .badge-inativo { background: rgba(255,255,255,0.06); color: var(--text-muted); border: 1px solid var(--border); padding: 3px 10px; border-radius: 20px; font-size: 0.62rem; font-weight: 800; text-transform: uppercase; }

        /* Modal pacote */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(6px); padding: 20px; }
        .modal-content { background: var(--card-bg); border-radius: 24px; border: 1px solid var(--border); padding: 30px; width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-content h2 { color: var(--primary); font-size: 1.2rem; font-weight: 900; margin: 0 0 20px; }
        .modal-content .fg { margin-bottom: 16px; }
        .modal-content .fg label { display: block; color: var(--text-muted); font-size: 0.65rem; text-transform: uppercase; font-weight: 800; margin-bottom: 6px; }
        .modal-content .fg input { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid var(--border); color: #fff; padding: 12px 14px; border-radius: 10px; font-size: 0.9rem; outline: none; }
        .modal-content .fg input:focus { border-color: var(--primary); }
        .modal-close { position: absolute; top: 18px; right: 22px; cursor: pointer; color: var(--text-muted); font-size: 1.2rem; background: none; border: none; }
        .modal-close:hover { color: var(--error); }
        .tipos-check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 8px; }
        .tipo-check { display: flex; align-items: center; gap: 8px; padding: 9px 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; cursor: pointer; font-size: 0.78rem; font-weight: 600; }
        .tipo-check input { accent-color: var(--primary); width: 16px; height: 16px; }
        .tipo-check i { color: var(--primary); width: 16px; text-align: center; }
    </style>
</head>
<body>

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <a href="{{ route('personal.dashboard') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
        <a href="{{ route('personal.avaliacao-fisica') }}" class="btn-back"><i class="fas fa-heart-pulse"></i> Avaliação Física</a>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ $personal->foto ? asset('storage/'.$personal->foto) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" style="width:38px; height:38px; border-radius:50%; border:2px solid var(--primary); object-fit:cover;">
        <span style="font-weight:700; font-size:0.9rem;">{{ $personal->nome }}</span>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-tags" style="margin-right:10px;"></i>Valores das Avaliações</h1>
    <p class="page-sub">Defina os preços avulsos de cada avaliação e monte pacotes. É isso que o cliente vê na hora de contratar.</p>

    @if(session('success'))
        <div class="alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    @php
        $tiposMeta = \App\Models\AvaliacaoFisica::META;
        $tiposCriaveis = \App\Models\AvaliacaoFisica::TIPOS;
    @endphp

    {{-- PREÇOS AVULSOS POR TIPO --}}
    <div class="section-label">Preços avulsos por avaliação</div>
    <p class="preco-hint">Defina o valor de cada avaliação vendida separadamente. As avaliações que você deixar <strong>em branco</strong> e que não estiverem em nenhum pacote aparecem para o cliente como <em>“personal não trabalha com essa avaliação”</em>.</p>
    <div class="card">
        <form action="{{ route('personal.avaliacao-fisica.precos') }}" method="POST">
            @csrf
            <div class="preco-grid">
                @foreach($tiposCriaveis as $key)
                <div class="preco-item">
                    <label><i class="fas {{ $tiposMeta[$key]['icon'] }}"></i> {{ $tiposMeta[$key]['label'] }}</label>
                    <div class="preco-input-wrap">
                        <span>R$</span>
                        <input type="number" step="0.01" min="0" name="precos[{{ $key }}]" value="{{ $precos[$key] ?? '' }}" placeholder="—">
                    </div>
                </div>
                @endforeach
            </div>
            <button type="submit" class="btn-primary" style="margin-top:18px;"><i class="fas fa-save"></i> Salvar preços</button>
        </form>
    </div>

    {{-- PACOTES DE AVALIAÇÃO --}}
    <div class="section-label" style="margin-top:30px;">Pacotes de avaliação</div>
    <p class="preco-hint">Agrupe avaliações num pacote com um valor próprio. Ex.: “Pacote Postural + Força” por R$ 300.</p>

    @if($pacotesAvaliacao->isEmpty())
        <div class="card" style="text-align:center; color:var(--text-muted); font-size:0.85rem;">
            Você ainda não criou nenhum pacote de avaliação.
        </div>
    @else
        @foreach($pacotesAvaliacao as $pac)
        <div class="pacote-card">
            <div class="pacote-head">
                <div>
                    <p class="pacote-nome">{{ $pac->nome }} @unless($pac->ativo)<span class="badge-inativo">Inativo</span>@endunless</p>
                    <div class="pacote-chips">
                        @foreach($pac->tipos as $t)
                            <span class="chip"><i class="fas {{ $tiposMeta[$t]['icon'] ?? 'fa-clipboard' }}"></i> {{ $tiposMeta[$t]['label'] ?? $t }}</span>
                        @endforeach
                    </div>
                </div>
                <div style="text-align:right;">
                    <div class="pacote-valor">R$ {{ number_format($pac->valor, 2, ',', '.') }}</div>
                    <div class="pacote-actions" style="margin-top:10px;">
                        <button type="button" class="btn-icon"
                            onclick='abrirModalPacote({{ \Illuminate\Support\Js::from([
                                "id" => $pac->id,
                                "nome" => $pac->nome,
                                "valor" => $pac->valor,
                                "tipos" => $pac->tipos,
                                "ativo" => $pac->ativo,
                            ]) }})'><i class="fas fa-pen"></i> Editar</button>
                        <form action="{{ route('personal.avaliacao-fisica.pacotes.destroy', $pac->id) }}" method="POST" onsubmit="return confirm('Excluir este pacote?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon danger"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    @endif

    <button type="button" class="btn-outline" style="margin-top:6px;" onclick="abrirModalPacote()"><i class="fas fa-plus"></i> Novo pacote de avaliação</button>
</div>

{{-- MODAL CRIAR/EDITAR PACOTE --}}
<div id="modalPacote" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close" onclick="fecharModalPacote()"><i class="fas fa-times"></i></button>
        <h2 id="modalPacoteTitulo"><i class="fas fa-box"></i> Novo Pacote de Avaliação</h2>
        <form id="formPacote" method="POST" action="{{ route('personal.avaliacao-fisica.pacotes.store') }}">
            @csrf
            <input type="hidden" name="_method" id="pacoteMethod" value="POST">
            <div class="fg">
                <label>Nome do pacote</label>
                <input type="text" name="nome" id="pacoteNome" placeholder="Ex: Pacote Postural + Força" required>
            </div>
            <div class="fg">
                <label>Valor (R$)</label>
                <input type="number" step="0.01" min="0" name="valor" id="pacoteValor" placeholder="Ex: 300.00" required>
            </div>
            <div class="fg">
                <label>Avaliações inclusas</label>
                <div class="tipos-check-grid">
                    @foreach($tiposCriaveis as $key)
                    <label class="tipo-check">
                        <input type="checkbox" name="tipos[]" value="{{ $key }}" class="pacote-tipo-check">
                        <i class="fas {{ $tiposMeta[$key]['icon'] }}"></i> {{ $tiposMeta[$key]['label'] }}
                    </label>
                    @endforeach
                </div>
            </div>
            <label class="tipo-check" style="margin-bottom:16px; width:fit-content;">
                <input type="checkbox" name="ativo" id="pacoteAtivo" value="1" checked> Pacote ativo (visível para clientes)
            </label>
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center;"><i class="fas fa-save"></i> Salvar pacote</button>
        </form>
    </div>
</div>

<script>
    const rotaPacoteStore = "{{ route('personal.avaliacao-fisica.pacotes.store') }}";
    const rotaPacoteUpdateBase = "{{ url('/personal/avaliacao-fisica/pacotes') }}";

    function abrirModalPacote(pac) {
        const form = document.getElementById('formPacote');
        document.querySelectorAll('.pacote-tipo-check').forEach(c => c.checked = false);

        if (pac) {
            document.getElementById('modalPacoteTitulo').innerHTML = '<i class="fas fa-pen"></i> Editar Pacote';
            form.action = rotaPacoteUpdateBase + '/' + pac.id;
            document.getElementById('pacoteMethod').value = 'PUT';
            document.getElementById('pacoteNome').value = pac.nome;
            document.getElementById('pacoteValor').value = parseFloat(pac.valor).toFixed(2);
            document.getElementById('pacoteAtivo').checked = !!pac.ativo;
            (pac.tipos || []).forEach(t => {
                const c = document.querySelector('.pacote-tipo-check[value="' + t + '"]');
                if (c) c.checked = true;
            });
        } else {
            document.getElementById('modalPacoteTitulo').innerHTML = '<i class="fas fa-box"></i> Novo Pacote de Avaliação';
            form.action = rotaPacoteStore;
            document.getElementById('pacoteMethod').value = 'POST';
            document.getElementById('pacoteNome').value = '';
            document.getElementById('pacoteValor').value = '';
            document.getElementById('pacoteAtivo').checked = true;
        }
        document.getElementById('modalPacote').style.display = 'flex';
    }
    function fecharModalPacote() {
        document.getElementById('modalPacote').style.display = 'none';
    }
    document.getElementById('modalPacote').addEventListener('click', function (e) {
        if (e.target === this) fecharModalPacote();
    });
    document.getElementById('formPacote').addEventListener('submit', function (e) {
        if (document.querySelectorAll('.pacote-tipo-check:checked').length === 0) {
            e.preventDefault();
            alert('Selecione ao menos uma avaliação para o pacote.');
        }
    });
</script>

</body>
</html>
