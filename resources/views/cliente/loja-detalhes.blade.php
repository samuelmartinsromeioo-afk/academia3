<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $loja->nome }} | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --input-bg: rgba(255,255,255,0.04);
            --loja-color: #ff9d2e;
            --whats: #25d366;
            --success: #28a745;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%); font-family: 'Inter', sans-serif; color: var(--text-main); min-height: 100vh; }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 40px; background: rgba(0, 0, 0, 0.3); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
        }
        .logo { font-family: 'Syncopate', sans-serif; font-size: 1.1rem; letter-spacing: 3px; }
        .logo span { color: var(--primary); }
        .btn-top {
            background: transparent; border: 1px solid var(--border); color: var(--text-main);
            padding: 9px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.78rem;
            transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .top-actions { display: flex; align-items: center; gap: 10px; }

        /* Botão do carrinho no canto superior direito */
        .cart-top {
            position: relative; background: var(--loja-color); color: #1a1300; border: none;
            border-radius: 10px; padding: 9px 16px; font-weight: 800; font-size: 0.82rem; cursor: pointer;
            font-family: inherit; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;
        }
        .cart-top:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(255,157,46,0.4); }
        .cart-top .count {
            background: #1a1300; color: var(--loja-color); border-radius: 999px; min-width: 20px; height: 20px;
            display: inline-flex; align-items: center; justify-content: center; font-size: 0.72rem; padding: 0 6px; font-weight: 900;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 36px 20px 120px; }

        .loja-header {
            display: flex; gap: 22px; align-items: center; flex-wrap: wrap;
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 26px; margin-bottom: 30px;
        }
        .loja-logo {
            width: 88px; height: 88px; border-radius: 18px; object-fit: cover; border: 1px solid var(--border);
            background: rgba(255,157,46,0.08); display: flex; align-items: center; justify-content: center;
            color: var(--loja-color); font-size: 2rem; flex-shrink: 0; overflow: hidden;
        }
        .loja-logo img { width:100%; height:100%; object-fit:cover; }
        .loja-header h1 { font-size: 1.5rem; font-weight: 900; }
        .loja-header .meta { color: var(--text-muted); font-size: 0.85rem; margin-top: 6px; display: flex; align-items: center; gap: 8px; }
        .loja-header .meta i { color: var(--loja-color); }
        .loja-header p.desc { color: var(--text-muted); font-size: 0.9rem; margin-top: 10px; line-height: 1.5; }

        .btn-whats {
            background: var(--whats); color: #fff; border: none; border-radius: 12px; padding: 13px 20px;
            font-weight: 800; font-size: 0.9rem; cursor: pointer; text-decoration: none; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 8px; margin-left: auto;
        }
        .btn-whats:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(37,211,102,0.3); }

        .section-title {
            font-size: 1.1rem; font-weight: 900; color: var(--loja-color); margin: 0 0 18px;
            display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px;
        }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }

        .prod-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; transition: 0.2s; }
        .prod-card:hover { border-color: rgba(255,157,46,0.4); transform: translateY(-3px); }
        .prod-img {
            height: 150px; background: rgba(255,157,46,0.06); display: flex; align-items: center; justify-content: center;
            color: var(--loja-color); font-size: 2rem; position: relative; overflow: hidden;
        }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; }
        .prod-img .esgotado {
            position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;
        }
        .prod-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
        .prod-cat { font-size: 0.66rem; color: var(--loja-color); text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 4px; }
        .prod-body h3 { font-size: 0.98rem; font-weight: 700; margin-bottom: 6px; }
        .prod-body p { color: var(--text-muted); font-size: 0.8rem; line-height: 1.45; margin-bottom: 12px; }
        .prod-foot { margin-top: auto; }
        .prod-preco { color: var(--primary); font-weight: 900; font-size: 1.1rem; }
        .prod-estoque { font-size: 0.7rem; color: var(--text-muted); margin: 4px 0 10px; }
        .prod-estoque.ok { color: var(--whats); }

        .btn-add {
            width: 100%; background: var(--primary); color: #000; border: none; border-radius: 10px; padding: 10px;
            font-weight: 800; font-size: 0.8rem; cursor: pointer; transition: 0.2s; font-family: inherit;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-add:hover { background: #e8ff40; }
        .btn-add:disabled { background: rgba(255,255,255,0.08); color: var(--text-muted); cursor: not-allowed; }

        .empty-state { text-align: center; padding: 70px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.4; color: var(--loja-color); }

        /* MODAIS */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.88); z-index: 99999;
            justify-content: center; align-items: center; backdrop-filter: blur(6px); padding: 20px; overflow-y: auto;
        }
        .modal-overlay.aberto { display: flex; }
        .modal-box {
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 30px;
            max-width: 480px; width: 100%; position: relative; max-height: 92vh; overflow-y: auto;
        }
        .modal-fechar { position: absolute; top: 16px; right: 16px; background: none; border: none; color: var(--text-muted); font-size: 1.2rem; cursor: pointer; }
        .modal-box h3 { font-size: 1.1rem; font-weight: 900; margin-bottom: 16px; }
        .modal-box h3 i { color: var(--loja-color); }

        /* CART ITEMS */
        .cart-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .cart-item:last-of-type { border-bottom: none; }
        .cart-item .ci-nome { flex: 1; font-size: 0.88rem; font-weight: 600; }
        .cart-item .ci-preco { color: var(--text-muted); font-size: 0.75rem; }
        .qty { display: inline-flex; align-items: center; gap: 8px; }
        .qty button {
            width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--border); background: var(--input-bg);
            color: #fff; cursor: pointer; font-size: 0.9rem;
        }
        .qty button:hover { border-color: var(--loja-color); color: var(--loja-color); }
        .qty span { min-width: 22px; text-align: center; font-weight: 800; }
        .ci-remove { background: none; border: none; color: #ff6b6b; cursor: pointer; font-size: 0.85rem; }

        .cart-total { display: flex; justify-content: space-between; align-items: center; margin: 18px 0; font-weight: 900; font-size: 1.1rem; }
        .cart-total .v { color: var(--primary); }

        label { display: block; font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; }
        .campo { margin-bottom: 14px; }
        .linha { display: flex; gap: 12px; }
        .linha > div { flex: 1; }
        .modal-box input, .modal-box textarea {
            width: 100%; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px;
            padding: 11px 14px; color: #fff; font-size: 0.9rem; outline: none; font-family: inherit;
        }
        .modal-box input:focus, .modal-box textarea:focus { border-color: var(--loja-color); }

        .entrega-tabs { display: flex; gap: 10px; margin-bottom: 16px; }
        .entrega-tab {
            flex: 1; text-align: center; padding: 12px; border-radius: 12px; border: 1px solid var(--border);
            background: var(--input-bg); cursor: pointer; font-weight: 700; font-size: 0.82rem; transition: 0.2s;
        }
        .entrega-tab.active { border-color: var(--loja-color); background: rgba(255,157,46,0.1); color: var(--loja-color); }

        .botoes-pagamento { display: flex; gap: 10px; margin-top: 6px; }
        .btn-pix, .btn-cartao {
            flex: 1; border: none; border-radius: 10px; padding: 13px; font-weight: 900; font-size: 0.85rem;
            cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-pix { background: var(--primary); color: #000; }
        .btn-cartao { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.2); }

        .pix-qr { display: block; margin: 0 auto 16px; width: 220px; height: 220px; border-radius: 12px; background: #fff; }
        .pix-copia-wrap { display: flex; gap: 8px; margin-bottom: 14px; }
        .pix-copia-wrap input { flex: 1; font-size: 0.72rem; color: var(--text-muted); }
        .pix-copia-wrap button { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 10px; padding: 0 16px; cursor: pointer; }
        .pix-status { display: none; text-align: center; font-weight: 800; font-size: 0.9rem; padding: 10px 0; }
        .modal-valor { font-size: 1.4rem; font-weight: 700; margin-bottom: 18px; }
        .modal-sub { color: var(--text-muted); font-size: 0.82rem; margin-bottom: 6px; }

        .erro-msg { display: none; color: #ff6b6b; font-size: 0.82rem; background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
        .ok-msg { display: none; color: #4caf50; font-size: 0.9rem; font-weight: 700; text-align: center; padding: 10px 0; }
        .btn-confirmar { width: 100%; background: var(--primary); color: #000; border: none; border-radius: 12px; padding: 14px; font-weight: 900; font-size: 0.95rem; cursor: pointer; font-family: inherit; }
        .loading-cep { font-size: 0.72rem; color: var(--loja-color); display: none; margin-top: 4px; }

        @media (max-width: 600px) { .top-bar { padding: 14px 20px; } .btn-whats { margin-left: 0; width: 100%; justify-content: center; } }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span></div>
    <div class="top-actions">
        <button class="cart-top" id="cartTop" onclick="abrirCarrinho()" style="display:none;">
            <i class="ph ph-shopping-cart"></i> Carrinho <span class="count" id="cartCount">0</span>
        </button>
        <a href="{{ route('lojas.explorar') }}" class="btn-top"><i class="ph ph-arrow-left"></i> Todas as lojas</a>
    </div>
</div>

<div class="container">
    @php
        $whatsDigits = preg_replace('/\D/', '', $loja->whatsapp ?? '');
        if ($whatsDigits && strlen($whatsDigits) <= 11) { $whatsDigits = '55' . $whatsDigits; }
        $whatsMsg = rawurlencode("Olá! Vi a loja {$loja->nome} na SnrFit e gostaria de tirar uma dúvida.");
    @endphp

    <div class="loja-header">
        <div class="loja-logo">
            @if ($loja->logo)
                <img src="{{ asset('storage/' . $loja->logo) }}" alt="{{ $loja->nome }}">
            @else
                <i class="ph ph-storefront"></i>
            @endif
        </div>
        <div style="flex:1; min-width:200px;">
            <h1>{{ $loja->nome }}</h1>
            <div class="meta"><i class="ph ph-map-pin"></i> {{ $loja->cidade }}{{ $loja->estado ? ' - ' . $loja->estado : '' }}</div>
            @if ($loja->descricao)
                <p class="desc">{{ $loja->descricao }}</p>
            @endif
        </div>
        @if ($whatsDigits)
            <a href="https://wa.me/{{ $whatsDigits }}?text={{ $whatsMsg }}" target="_blank" class="btn-whats">
                <i class="ph ph-whatsapp-logo"></i> Falar com a loja
            </a>
        @endif
    </div>

    <h2 class="section-title"><i class="ph ph-package"></i> Produtos</h2>

    @if ($loja->produtos->isEmpty())
        <div class="empty-state">
            <i class="ph ph-package"></i>
            <p>Esta loja ainda não cadastrou produtos.</p>
        </div>
    @else
        <div class="grid">
            @foreach ($loja->produtos as $produto)
                <div class="prod-card">
                    <div class="prod-img">
                        @if ($produto->imagem)
                            <img src="{{ asset('storage/' . $produto->imagem) }}" alt="{{ $produto->nome }}">
                        @else
                            <i class="ph ph-package"></i>
                        @endif
                        @if ($produto->estoque <= 0)
                            <div class="esgotado">Esgotado</div>
                        @endif
                    </div>
                    <div class="prod-body">
                        @if ($produto->categoria)
                            <div class="prod-cat">{{ $produto->categoria }}</div>
                        @endif
                        <h3>{{ $produto->nome }}</h3>
                        @if ($produto->descricao)
                            <p>{{ \Illuminate\Support\Str::limit($produto->descricao, 80) }}</p>
                        @endif
                        <div class="prod-foot">
                            <div class="prod-preco">R$ {{ number_format($produto->preco, 2, ',', '.') }}</div>
                            @if ($produto->estoque > 0)
                                <div class="prod-estoque ok"><i class="ph ph-check"></i> {{ $produto->estoque }} em estoque</div>
                                <button class="btn-add" data-id="{{ $produto->id }}" data-nome="{{ $produto->nome }}" data-preco="{{ $produto->preco }}" data-estoque="{{ $produto->estoque }}">
                                    <i class="ph ph-shopping-cart"></i> Adicionar
                                </button>
                            @else
                                <div class="prod-estoque">Indisponível</div>
                                <button class="btn-add" disabled><i class="ph ph-prohibit"></i> Esgotado</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- MODAL CARRINHO + CHECKOUT --}}
<div class="modal-overlay" id="modalCheckout">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharCheckout()">✕</button>

        {{-- Passo 1: carrinho --}}
        <div id="stepCarrinho">
            <h3><i class="ph ph-shopping-cart"></i> Seu carrinho</h3>
            <div id="cartItens"></div>
            <p id="cartVazio" class="modal-sub" style="display:none;">Seu carrinho está vazio.</p>
            <div class="cart-total"><span>Total</span> <span class="v" id="cartTotal">R$ 0,00</span></div>
            <button class="btn-confirmar" id="btnIrCheckout" onclick="irParaEntrega()"><i class="ph ph-arrow-right"></i> Continuar</button>
        </div>

        {{-- Passo 2: entrega + pagamento --}}
        <div id="stepEntrega" style="display:none;">
            <h3><i class="ph ph-truck"></i> Entrega e pagamento</h3>

            <div class="entrega-tabs">
                <div class="entrega-tab active" id="tabRetirada" onclick="setEntrega('retirada')"><i class="ph ph-storefront"></i> Retirar na loja</div>
                <div class="entrega-tab" id="tabEntrega" onclick="setEntrega('entrega')"><i class="ph ph-truck"></i> Combinar entrega</div>
            </div>

            <div id="formEntrega" style="display:none;">
                <div class="campo">
                    <label>Quem vai receber</label>
                    <input id="entNome" type="text" maxlength="120" placeholder="Nome de quem recebe">
                </div>
                <div class="campo">
                    <label>Telefone / WhatsApp</label>
                    <input id="entTelefone" type="text" maxlength="15" placeholder="(11) 99999-9999" oninput="fmtTel(this)">
                </div>
                <div class="campo">
                    <label>CEP</label>
                    <input id="entCep" type="text" maxlength="9" placeholder="00000-000" oninput="fmtCep(this)" onblur="buscarCep()">
                    <span class="loading-cep" id="cepLoading"><i class="ph ph-spinner ph-spin"></i> Buscando...</span>
                </div>
                <div class="linha campo">
                    <div style="flex:3;"><label>Rua</label><input id="entRua" type="text" maxlength="255"></div>
                    <div style="flex:1;"><label>Nº</label><input id="entNumero" type="text" maxlength="20"></div>
                </div>
                <div class="campo">
                    <label>Complemento (opcional)</label>
                    <input id="entComplemento" type="text" maxlength="120" placeholder="Apto, bloco...">
                </div>
                <div class="linha campo">
                    <div style="flex:2;"><label>Bairro</label><input id="entBairro" type="text" maxlength="120"></div>
                    <div style="flex:2;"><label>Cidade</label><input id="entCidade" type="text" maxlength="120"></div>
                    <div style="flex:1;"><label>UF</label><input id="entEstado" type="text" maxlength="2"></div>
                </div>
            </div>

            <div class="campo">
                <label>Observação (opcional)</label>
                <textarea id="entObs" rows="2" maxlength="500" placeholder="Algo que a loja precisa saber?"></textarea>
            </div>

            <div class="cart-total"><span>Total</span> <span class="v" id="cartTotal2">R$ 0,00</span></div>
            <p class="erro-msg" id="checkoutErro"></p>
            <div class="botoes-pagamento">
                <button class="btn-pix" onclick="pagarPix()"><i class="ph ph-qr-code"></i> PIX</button>
                <button class="btn-cartao" onclick="pagarCartao()"><i class="ph ph-credit-card"></i> Cartão</button>
            </div>
            <button class="btn-top" style="margin-top:14px; width:100%; justify-content:center;" onclick="voltarCarrinho()"><i class="ph ph-arrow-left"></i> Voltar ao carrinho</button>
        </div>
    </div>
</div>

{{-- MODAL PIX --}}
<div class="modal-overlay" id="modalPix">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharModalPix()">✕</button>
        <h3><i class="ph ph-qr-code"></i> PAGAMENTO PIX</h3>
        <p class="modal-sub" id="pixDescricao"></p>
        <p class="modal-valor" id="pixValor">Gerando QR Code...</p>
        <img class="pix-qr" id="pixQr" src="" alt="QR Code PIX">
        <div class="pix-copia-wrap">
            <input type="text" id="pixCopia" readonly>
            <button onclick="copiarPix()"><i class="ph ph-copy"></i></button>
        </div>
        <p style="color: var(--text-muted); font-size: 0.75rem; text-align: center;">Escaneie o QR Code ou use o copia e cola. A confirmação é automática.</p>
        <p class="pix-status" id="pixStatus"></p>
        @include('partials.asaas-pagamento')
    </div>
</div>

{{-- MODAL CARTÃO --}}
<div class="modal-overlay" id="modalCartao">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharModalCartao()">✕</button>
        <h3><i class="ph ph-credit-card"></i> PAGAMENTO COM CARTÃO</h3>
        <p class="modal-valor" id="cartaoValor"></p>
        <form id="formCartao" onsubmit="submeterCartao(event)" autocomplete="off">
            <div class="campo"><label>Número do cartão</label><input id="cartaoNumero" type="text" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" oninput="fmtCartao(this)" required></div>
            <div class="campo"><label>Nome impresso no cartão</label><input id="cartaoNomeTitular" type="text" placeholder="NOME SOBRENOME" maxlength="100" oninput="this.value=this.value.toUpperCase()" required></div>
            <div class="linha campo">
                <div><label>Validade (MM/AA)</label><input id="cartaoValidade" type="text" inputmode="numeric" maxlength="5" placeholder="MM/AA" oninput="fmtValidade(this)" required></div>
                <div><label>CVV</label><input id="cartaoCCV" type="text" inputmode="numeric" maxlength="4" placeholder="123" oninput="this.value=this.value.replace(/\D/g,'')" required></div>
            </div>
            <hr style="border:none; border-top:1px solid var(--border); margin:4px 0 16px;">
            <p style="color: var(--text-muted); font-size: 0.72rem; margin-bottom: 12px;">Dados do titular (antifraude)</p>
            <div class="campo"><label>CPF do titular</label><input id="cartaoCPF" type="text" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" oninput="fmtCpf(this)" required></div>
            <div class="linha campo">
                <div style="flex:2;"><label>CEP</label><input id="cartaoCEP" type="text" inputmode="numeric" maxlength="9" placeholder="00000-000" oninput="fmtCep(this)" required></div>
                <div><label>Número</label><input id="cartaoNumeroEnd" type="text" maxlength="20" placeholder="277" required></div>
            </div>
            <div class="campo"><label>Telefone</label><input id="cartaoTelefone" type="text" inputmode="numeric" maxlength="15" placeholder="(11) 99999-9999" oninput="fmtTel(this)" required></div>
            <p class="erro-msg" id="cartaoErro"></p>
            <p class="ok-msg" id="cartaoSucesso">✅ Pagamento aprovado! Atualizando...</p>
            <button type="submit" class="btn-confirmar" id="btnSubmeterCartao"><i class="ph ph-lock"></i> Pagar com Segurança</button>
            @include('partials.asaas-pagamento')
        </form>
    </div>
</div>

<script>
    const LOJA_ID     = {!! json_encode($loja->id) !!};
    const LOJA_NOME   = {!! json_encode($loja->nome) !!};
    const CSRF        = '{{ csrf_token() }}';
    const CLIENTE_TEL = {!! json_encode($cliente->whatsapp ?? '') !!};
    const CLIENTE_CEP = {!! json_encode($cliente->cep ?? '') !!};
    const EH_CLIENTE  = {!! json_encode((bool) $cliente) !!};

    let cart = [];          // { id, nome, preco, estoque, qtd }
    let entregaTipo = 'retirada';

    function brl(v) { return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ','); }

    function addToCart(id, nome, preco, estoque) {
        if (!EH_CLIENTE) { alert('Entre como cliente (aluno) para comprar produtos.'); return; }
        const ex = cart.find(i => i.id === id);
        if (ex) {
            if (ex.qtd >= estoque) { alert('Você já adicionou todo o estoque disponível deste produto.'); return; }
            ex.qtd++;
        } else {
            cart.push({ id, nome, preco: parseFloat(preco), estoque, qtd: 1 });
        }
        atualizarFab();
        flash();
    }

    function flash() {
        const ph = document.getElementById('cartTop');
        ph.style.transform = 'scale(1.08)';
        setTimeout(() => ph.style.transform = '', 150);
    }

    function totalCart() { return cart.reduce((s, i) => s + i.preco * i.qtd, 0); }
    function countCart() { return cart.reduce((s, i) => s + i.qtd, 0); }

    function atualizarFab() {
        const ph = document.getElementById('cartTop');
        document.getElementById('cartCount').textContent = countCart();
        ph.style.display = cart.length === 0 ? 'none' : 'inline-flex';
    }

    // Liga os botões "Adicionar" (data-* evita problemas de aspas no onclick).
    document.querySelectorAll('.btn-add[data-id]').forEach(btn => {
        btn.addEventListener('click', () => {
            addToCart(
                parseInt(btn.dataset.id, 10),
                btn.dataset.nome,
                parseFloat(btn.dataset.preco),
                parseInt(btn.dataset.estoque, 10)
            );
        });
    });

    function changeQty(id, delta) {
        const it = cart.find(i => i.id === id);
        if (!it) return;
        it.qtd += delta;
        if (it.qtd <= 0) cart = cart.filter(i => i.id !== id);
        else if (it.qtd > it.estoque) it.qtd = it.estoque;
        renderCart();
        atualizarFab();
    }

    function removeItem(id) { cart = cart.filter(i => i.id !== id); renderCart(); atualizarFab(); }

    function renderCart() {
        const box = document.getElementById('cartItens');
        const vazio = document.getElementById('cartVazio');
        const btn = document.getElementById('btnIrCheckout');
        if (cart.length === 0) {
            box.innerHTML = ''; vazio.style.display = 'block'; btn.disabled = true; btn.style.opacity = .5;
        } else {
            vazio.style.display = 'none'; btn.disabled = false; btn.style.opacity = 1;
            box.innerHTML = cart.map(i => `
                <div class="cart-item">
                    <div style="flex:1;">
                        <div class="ci-nome">${escapeHtml(i.nome)}</div>
                        <div class="ci-preco">${brl(i.preco)} cada</div>
                    </div>
                    <div class="qty">
                        <button onclick="changeQty(${i.id},-1)">−</button>
                        <span>${i.qtd}</span>
                        <button onclick="changeQty(${i.id},1)">+</button>
                    </div>
                    <button class="ci-remove" onclick="removeItem(${i.id})"><i class="ph ph-trash"></i></button>
                </div>`).join('');
        }
        const t = brl(totalCart());
        document.getElementById('cartTotal').textContent = t;
        document.getElementById('cartTotal2').textContent = t;
    }

    function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}

    function abrirCarrinho() {
        document.getElementById('stepCarrinho').style.display = 'block';
        document.getElementById('stepEntrega').style.display = 'none';
        renderCart();
        document.getElementById('modalCheckout').classList.add('aberto');
    }
    function fecharCheckout() { document.getElementById('modalCheckout').classList.remove('aberto'); }
    function voltarCarrinho() { document.getElementById('stepEntrega').style.display='none'; document.getElementById('stepCarrinho').style.display='block'; }

    function irParaEntrega() {
        if (cart.length === 0) return;
        document.getElementById('stepCarrinho').style.display = 'none';
        document.getElementById('stepEntrega').style.display = 'block';
    }

    function setEntrega(tipo) {
        entregaTipo = tipo;
        document.getElementById('tabRetirada').classList.toggle('active', tipo === 'retirada');
        document.getElementById('tabEntrega').classList.toggle('active', tipo === 'entrega');
        document.getElementById('formEntrega').style.display = tipo === 'entrega' ? 'block' : 'none';
        if (tipo === 'entrega' && !document.getElementById('entTelefone').value) {
            document.getElementById('entTelefone').value = CLIENTE_TEL || '';
            document.getElementById('entCep').value = CLIENTE_CEP || '';
        }
    }

    function montarPayload() {
        const p = {
            loja_id: LOJA_ID,
            items: cart.map(i => ({ produto_id: i.id, quantidade: i.qtd })),
            entrega_tipo: entregaTipo,
            observacao: document.getElementById('entObs').value.trim() || null,
        };
        if (entregaTipo === 'entrega') {
            p.entrega_nome        = document.getElementById('entNome').value.trim();
            p.entrega_telefone    = document.getElementById('entTelefone').value.trim();
            p.entrega_cep         = document.getElementById('entCep').value.trim();
            p.entrega_rua         = document.getElementById('entRua').value.trim();
            p.entrega_numero      = document.getElementById('entNumero').value.trim();
            p.entrega_complemento = document.getElementById('entComplemento').value.trim();
            p.entrega_bairro      = document.getElementById('entBairro').value.trim();
            p.entrega_cidade      = document.getElementById('entCidade').value.trim();
            p.entrega_estado      = document.getElementById('entEstado').value.trim();
        }
        return p;
    }

    function validarEntrega() {
        const erro = document.getElementById('checkoutErro');
        erro.style.display = 'none';
        if (entregaTipo === 'entrega') {
            const req = ['entNome','entTelefone','entCep','entRua','entNumero','entBairro','entCidade','entEstado'];
            for (const id of req) {
                if (!document.getElementById(id).value.trim()) {
                    erro.textContent = 'Preencha o endereço de entrega completo.';
                    erro.style.display = 'block';
                    return false;
                }
            }
        }
        return true;
    }

    // ============ PIX ============
    let pixPolling = null;
    function abrirModalPix() {
        document.getElementById('pixValor').textContent = 'Gerando QR Code...';
        document.getElementById('pixQr').src = '';
        document.getElementById('pixCopia').value = '';
        document.getElementById('pixStatus').style.display = 'none';
        document.getElementById('modalPix').classList.add('aberto');
    }
    function fecharModalPix() { document.getElementById('modalPix').classList.remove('aberto'); if (pixPolling) { clearInterval(pixPolling); pixPolling = null; } }
    function copiarPix() { const i = document.getElementById('pixCopia'); i.select(); document.execCommand('copy'); }

    async function pagarPix() {
        if (!validarEntrega()) return;
        document.getElementById('pixDescricao').textContent = `Pedido — ${LOJA_NOME}`;
        abrirModalPix();
        try {
            const res = await fetch('/api/criar-pagamento-loja', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(montarPayload()),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao gerar pagamento.');

            document.getElementById('pixQr').src = 'data:image/png;base64,' + data.pixQrCode;
            document.getElementById('pixCopia').value = data.pixPayload;
            document.getElementById('pixValor').textContent = brl(data.amount);

            pixPolling = setInterval(async () => {
                try {
                    const sr = await fetch('/api/pagamento/status/' + data.asaasPaymentId, { headers: { 'X-CSRF-TOKEN': CSRF } });
                    const sd = await sr.json();
                    if (sd.confirmed) {
                        clearInterval(pixPolling);
                        const m = document.getElementById('pixStatus');
                        m.textContent = '✅ Pagamento confirmado! Pedido enviado à loja.';
                        m.style.color = 'var(--success)';
                        m.style.display = 'block';
                        cart = [];
                        setTimeout(() => window.location.reload(), 2500);
                    }
                } catch (_) {}
            }, 4000);
        } catch (err) {
            alert('Erro: ' + err.message);
            fecharModalPix();
        }
    }

    // ============ CARTÃO ============
    function pagarCartao() {
        if (!validarEntrega()) return;
        document.getElementById('cartaoValor').textContent = brl(totalCart());
        document.getElementById('formCartao').reset();
        document.getElementById('cartaoErro').style.display = 'none';
        document.getElementById('cartaoSucesso').style.display = 'none';
        const btn = document.getElementById('btnSubmeterCartao');
        btn.disabled = false; btn.innerHTML = '<i class="ph ph-lock"></i> Pagar com Segurança';
        document.getElementById('cartaoTelefone').value = CLIENTE_TEL || '';
        document.getElementById('cartaoCEP').value = CLIENTE_CEP || '';
        document.getElementById('modalCartao').classList.add('aberto');
    }
    function fecharModalCartao() { document.getElementById('modalCartao').classList.remove('aberto'); }

    async function submeterCartao(e) {
        e.preventDefault();
        const validade = document.getElementById('cartaoValidade').value.trim().split('/');
        if (validade.length !== 2 || validade[0].length !== 2 || validade[1].length !== 2) {
            const er = document.getElementById('cartaoErro'); er.textContent = 'Validade inválida (MM/AA).'; er.style.display = 'block'; return;
        }
        const card = {
            card_holder: document.getElementById('cartaoNomeTitular').value.trim(),
            card_number: document.getElementById('cartaoNumero').value.replace(/\s/g, ''),
            card_expiry_month: validade[0],
            card_expiry_year: '20' + validade[1],
            card_ccv: document.getElementById('cartaoCCV').value.trim(),
            cpf: document.getElementById('cartaoCPF').value.replace(/\D/g, ''),
            cep: document.getElementById('cartaoCEP').value.replace(/\D/g, ''),
            numero: document.getElementById('cartaoNumeroEnd').value.trim(),
            telefone: document.getElementById('cartaoTelefone').value.replace(/\D/g, ''),
        };
        const btn = document.getElementById('btnSubmeterCartao');
        btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Processando...';
        document.getElementById('cartaoErro').style.display = 'none';
        try {
            const res = await fetch('/api/criar-pagamento-cartao-loja', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ ...montarPayload(), ...card }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao processar cartão.');
            if (data.confirmed) {
                document.getElementById('cartaoSucesso').style.display = 'block';
                cart = [];
                setTimeout(() => window.location.reload(), 2000);
            } else {
                throw new Error('Pagamento não confirmado. Verifique os dados e tente novamente.');
            }
        } catch (err) {
            const er = document.getElementById('cartaoErro'); er.textContent = err.message; er.style.display = 'block';
            btn.disabled = false; btn.innerHTML = '<i class="ph ph-lock"></i> Pagar com Segurança';
        }
    }

    // ============ CEP entrega ============
    async function buscarCep() {
        const cep = document.getElementById('entCep').value.replace(/\D/g, '');
        if (cep.length !== 8) return;
        document.getElementById('cepLoading').style.display = 'block';
        try {
            const r = await fetch(`https://brasilapi.com.br/api/cep/v1/${cep}`);
            const d = await r.json();
            if (!d.errors) {
                document.getElementById('entRua').value = d.street || '';
                document.getElementById('entBairro').value = d.neighborhood || '';
                document.getElementById('entCidade').value = d.city || '';
                document.getElementById('entEstado').value = d.state || '';
            }
        } catch (_) {}
        document.getElementById('cepLoading').style.display = 'none';
    }

    // ============ máscaras ============
    function fmtCartao(i){let v=i.value.replace(/\D/g,'').substring(0,16);i.value=v.replace(/(.{4})/g,'$1 ').trim();}
    function fmtValidade(i){let v=i.value.replace(/\D/g,'').substring(0,4);if(v.length>=3)v=v.substring(0,2)+'/'+v.substring(2);i.value=v;}
    function fmtCpf(i){let v=i.value.replace(/\D/g,'').substring(0,11);v=v.replace(/(\d{3})(\d)/,'$1.$2');v=v.replace(/(\d{3})(\d)/,'$1.$2');v=v.replace(/(\d{3})(\d{1,2})$/,'$1-$2');i.value=v;}
    function fmtCep(i){let v=i.value.replace(/\D/g,'').substring(0,8);if(v.length>5)v=v.substring(0,5)+'-'+v.substring(5);i.value=v;}
    function fmtTel(i){let v=i.value.replace(/\D/g,'').substring(0,11);if(v.length>2)v='('+v.substring(0,2)+') '+v.substring(2);if(v.length>10)v=v.substring(0,10)+'-'+v.substring(10);i.value=v;}

    window.addEventListener('click', e => {
        if (e.target === document.getElementById('modalCheckout')) fecharCheckout();
        if (e.target === document.getElementById('modalPix')) fecharModalPix();
        if (e.target === document.getElementById('modalCartao')) fecharModalCartao();
    });
</script>
</body>
</html>
