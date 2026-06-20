<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $academia->nome }} | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
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
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%); font-family: 'Inter', sans-serif; color: var(--text-main); min-height: 100vh; }

        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 18px 40px; background: rgba(0,0,0,0.3); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .logo { font-family: 'Syncopate', sans-serif; font-size: 1.1rem; letter-spacing: 3px; }
        .logo span { color: var(--primary); }
        .btn-top { background: transparent; border: 1px solid var(--border); color: var(--text-main); padding: 9px 16px; border-radius: 8px; font-weight: 700; font-size: 0.78rem; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 1100px; margin: 0 auto; padding: 36px 20px; }

        .header-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; padding: 28px; margin-bottom: 28px; display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; }
        .header-icon { width: 80px; height: 80px; border-radius: 20px; background: rgba(212,255,0,0.1); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 2rem; flex-shrink: 0; }
        .header-info { flex: 1; min-width: 240px; }
        .header-info h1 { font-size: 1.5rem; font-weight: 900; margin-bottom: 4px; }
        .header-info .modalidades { color: var(--primary); font-weight: 700; font-size: 0.85rem; margin-bottom: 10px; }
        .header-info .meta { color: var(--text-muted); font-size: 0.83rem; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
        .header-info .meta i { color: var(--primary); width: 15px; text-align: center; }
        .descricao { color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-top: 10px; }

        .section { background: var(--card-bg); border: 1px solid var(--border); border-radius: 18px; padding: 28px; margin-bottom: 28px; }
        .section-title { font-size: 1.05rem; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--primary); }
        .empty { color: var(--text-muted); font-size: 0.88rem; }

        .galeria-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
        .galeria-grid img { width: 100%; height: 130px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border); cursor: pointer; transition: 0.2s; }
        .galeria-grid img:hover { transform: scale(1.03); border-color: var(--primary); }

        .prof-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .prof-card { background: var(--input-bg); border: 1px solid var(--border); border-radius: 14px; padding: 16px; display: flex; align-items: center; gap: 12px; }
        .prof-card .avatar { width: 44px; height: 44px; border-radius: 12px; background: rgba(212,255,0,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .prof-card .nome { font-weight: 800; font-size: 0.92rem; }
        .prof-card .ver { color: var(--text-muted); font-size: 0.72rem; }
        .prof-card.clickable { cursor: pointer; transition: 0.2s; }
        .prof-card.clickable:hover { border-color: var(--primary); }

        .aulas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
        .aula-card { background: var(--input-bg); border: 1px solid var(--border); border-radius: 14px; padding: 18px; }
        .aula-card h4 { font-size: 1rem; font-weight: 800; margin-bottom: 6px; }
        .aula-card .horario { color: var(--primary); font-size: 0.76rem; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .aula-card .resumo { color: var(--text-muted); font-size: 0.82rem; line-height: 1.5; }
        .aula-card .prof-link { color: var(--primary); text-decoration: underline; cursor: pointer; font-weight: 700; }

        .infra { color: var(--text-muted); font-size: 0.9rem; line-height: 1.7; white-space: pre-line; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.88); z-index: 99999; justify-content: center; align-items: center; backdrop-filter: blur(6px); padding: 20px; }
        .modal-overlay.aberto { display: flex; }
        .modal-box { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; padding: 32px; max-width: 460px; width: 100%; position: relative; max-height: 90vh; overflow-y: auto; }
        .modal-fechar { position: absolute; top: 16px; right: 16px; background: none; border: none; color: var(--text-muted); font-size: 1.2rem; cursor: pointer; }
        .modal-box h3 { font-size: 1.1rem; font-weight: 900; margin-bottom: 4px; }
        .modal-box h3 i { color: var(--primary); }
        .modal-box .modal-resumo { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; white-space: pre-line; margin-top: 12px; }
        .modal-box h3 { font-size: 1.1rem; font-weight: 900; margin-bottom: 4px; }
        .modal-box .modal-sub { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 4px; }
        .modal-box .modal-valor { font-size: 1.4rem; font-weight: 700; margin-bottom: 20px; }

        /* MENSALIDADE BASE */
        .mensalidade-box {
            background: rgba(212,255,0,0.06);
            border: 1px solid rgba(212,255,0,0.25);
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .mensalidade-box .label { color: var(--text-muted); font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .mensalidade-box .valor { font-size: 1.6rem; font-weight: 900; color: var(--primary); }
        .mensalidade-box .valor small { color: var(--text-muted); font-weight: 400; font-size: 0.72rem; }

        /* PLANOS */
        .planos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; }
        .plano-card { background: var(--input-bg); border: 1px solid rgba(212,255,0,0.2); border-radius: 14px; padding: 20px; display: flex; flex-direction: column; }
        .plano-card h4 { color: var(--primary); font-size: 1rem; font-weight: 800; margin-bottom: 6px; }
        .plano-card .valor { font-size: 1.3rem; font-weight: 900; margin-bottom: 4px; }
        .plano-card .valor small { color: var(--text-muted); font-weight: 400; font-size: 0.7rem; }
        .plano-card .desc { color: var(--text-muted); font-size: 0.78rem; margin-bottom: 14px; line-height: 1.5; flex: 1; }

        .botoes-pagamento { display: flex; gap: 8px; }
        .btn-pix, .btn-cartao { flex: 1; border: none; border-radius: 9px; padding: 10px; font-weight: 900; font-size: 0.78rem; cursor: pointer; transition: 0.2s; font-family: inherit; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-pix { background: var(--primary); color: #000; }
        .btn-pix:hover { background: #e8ff40; }
        .btn-cartao { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
        .btn-cartao:hover { background: rgba(255,255,255,0.15); }

        .pix-qr { display: block; margin: 0 auto 16px; width: 220px; height: 220px; border-radius: 12px; background: #fff; }
        .pix-copia-wrap { display: flex; gap: 8px; margin-bottom: 14px; }
        .pix-copia-wrap input { flex: 1; background: var(--input-bg); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; color: var(--text-muted); font-size: 0.72rem; outline: none; }
        .pix-copia-wrap button { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 10px; padding: 0 16px; cursor: pointer; font-family: inherit; }
        .pix-status { display: none; text-align: center; font-weight: 800; font-size: 0.9rem; padding: 10px 0; }

        .form-cartao input { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; padding: 11px 14px; color: #fff; font-size: 0.9rem; outline: none; font-family: inherit; }
        .form-cartao .campo { margin-bottom: 14px; }
        .form-cartao label { display: block; font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; }
        .form-cartao .linha { display: flex; gap: 12px; }
        .form-cartao .linha > div { flex: 1; }
        .erro-msg { display: none; color: #ff6b6b; font-size: 0.82rem; background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
        .ok-msg { display: none; color: #4caf50; font-size: 0.9rem; font-weight: 700; text-align: center; padding: 10px 0; }
        .btn-confirmar { width: 100%; background: var(--primary); color: #000; border: none; border-radius: 12px; padding: 14px; font-weight: 900; font-size: 0.95rem; cursor: pointer; font-family: inherit; }

        @media (max-width: 600px) {
            .top-bar { padding: 14px 20px; }
            .section, .header-card { padding: 20px; }
        }
    </style>
</head>
<body>

@php
    $diasSemana = [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];
@endphp

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span></div>
    <a href="{{ route('academias.explorar') }}" class="btn-top"><i class="fas fa-arrow-left"></i> Voltar às academias</a>
</div>

<div class="container">

    {{-- HEADER --}}
    <div class="header-card">
        <div class="header-icon"><i class="fas fa-dumbbell"></i></div>
        <div class="header-info">
            <h1>{{ $academia->nome }}</h1>
            @if($academia->tipos_aulas)
                <div class="modalidades"><i class="fas fa-bolt"></i> {{ $academia->tipos_aulas }}</div>
            @endif
            <div class="meta"><i class="fas fa-map-marker-alt"></i> {{ $academia->endereco ?? ($academia->cidade . ' - ' . $academia->estado) }}</div>
            <div class="meta"><i class="fas fa-id-card"></i> Mensalidade: <strong style="color:#fff;">R$ {{ number_format($academia->valor_mensalidade ?? 0, 2, ',', '.') }}</strong></div>
            @if($academia->descricao)
                <p class="descricao">{{ $academia->descricao }}</p>
            @endif
        </div>
    </div>

    {{-- GALERIA --}}
    @if($academia->fotos->isNotEmpty())
        <div class="section">
            <div class="section-title"><i class="fas fa-images"></i> Fotos da academia</div>
            <div class="galeria-grid">
                @foreach($academia->fotos as $foto)
                    <img src="{{ asset('storage/' . $foto->path) }}" alt="{{ $foto->legenda ?? $academia->nome }}" title="{{ $foto->legenda }}" onclick="window.open(this.src, '_blank')">
                @endforeach
            </div>
        </div>
    @endif

    {{-- PLANOS E MENSALIDADE --}}
    <div class="section">
        <div class="section-title"><i class="fas fa-id-card"></i> Planos e mensalidade</div>

        <div class="mensalidade-box">
            <div>
                <div class="label">Mensalidade</div>
                <div style="color:var(--text-muted); font-size:0.8rem;">Valor base da academia</div>
            </div>
            <div class="valor">R$ {{ number_format($academia->valor_mensalidade ?? 0, 2, ',', '.') }} <small>/mês</small></div>
        </div>

        @if($academia->planos->isEmpty())
            <p class="empty">Esta academia ainda não cadastrou planos para contratação online.</p>
        @else
            <div class="planos-grid">
                @foreach($academia->planos as $plano)
                    <div class="plano-card">
                        <h4>{{ $plano->nome }}</h4>
                        <div class="valor">R$ {{ number_format($plano->valor, 2, ',', '.') }} <small>/mês · {{ $plano->duracao_meses }} {{ $plano->duracao_meses == 1 ? 'mês' : 'meses' }}</small></div>
                        @if($plano->descricao)
                            <p class="desc">{{ $plano->descricao }}</p>
                        @else
                            <p class="desc"></p>
                        @endif
                        <div class="botoes-pagamento">
                            <button class="btn-pix" onclick="pagarPlanoPix({{ $plano->id }}, {!! json_encode($plano->nome) !!}, {{ $plano->valor }})"><i class="fas fa-qrcode"></i> PIX</button>
                            <button class="btn-cartao" onclick="pagarPlanoCartao({{ $plano->id }}, {!! json_encode($plano->nome) !!}, {{ $plano->valor }})"><i class="fas fa-credit-card"></i> Cartão</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- PROFISSIONAIS --}}
    <div class="section">
        <div class="section-title"><i class="fas fa-user-tie"></i> Profissionais</div>
        @if($academia->professores->isEmpty())
            <p class="empty">A academia ainda não cadastrou seus profissionais.</p>
        @else
            <div class="prof-grid">
                @foreach($academia->professores as $prof)
                    <div class="prof-card {{ $prof->resumo ? 'clickable' : '' }}"
                         @if($prof->resumo) onclick="verProfissional({!! json_encode($prof->nome) !!}, {!! json_encode($prof->resumo) !!})" @endif>
                        <div class="avatar"><i class="fas fa-user"></i></div>
                        <div>
                            <div class="nome">{{ $prof->nome }}</div>
                            @if($prof->resumo)<div class="ver"><i class="fas fa-eye"></i> Ver resumo</div>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- AULAS --}}
    <div class="section">
        <div class="section-title"><i class="fas fa-people-group"></i> Aulas</div>
        @if($academia->aulas->isEmpty())
            <p class="empty">A academia ainda não divulgou suas aulas.</p>
        @else
            <div class="aulas-grid">
                @foreach($academia->aulas as $aula)
                    <div class="aula-card">
                        <h4>{{ $aula->nome }}</h4>
                        @if(!is_null($aula->dia_semana) || $aula->hora_inicio || $aula->professor)
                            <div class="horario">
                                @if(!is_null($aula->dia_semana))<span><i class="fas fa-calendar-day"></i> {{ $diasSemana[$aula->dia_semana] ?? '' }}</span>@endif
                                @if($aula->hora_inicio)<span>· {{ \Illuminate\Support\Str::substr($aula->hora_inicio, 0, 5) }}@if($aula->duracao_min) ({{ $aula->duracao_min }}min)@endif</span>@endif
                                @if($aula->professor)
                                    <span>· @if($aula->professor->resumo)<a class="prof-link" onclick="verProfissional({!! json_encode($aula->professor->nome) !!}, {!! json_encode($aula->professor->resumo) !!})">{{ $aula->professor->nome }}</a>@else{{ $aula->professor->nome }}@endif</span>
                                @endif
                            </div>
                        @endif
                        @if($aula->resumo)<p class="resumo">{{ $aula->resumo }}</p>@endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- INFRAESTRUTURA --}}
    <div class="section">
        <div class="section-title"><i class="fas fa-building"></i> Infraestrutura</div>
        @if($academia->infraestrutura)
            <p class="infra">{{ $academia->infraestrutura }}</p>
        @else
            <p class="empty">A academia ainda não descreveu sua infraestrutura.</p>
        @endif
    </div>
</div>

{{-- MODAL PROFISSIONAL --}}
<div class="modal-overlay" id="profModal">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharProfModal()">✕</button>
        <h3><i class="fas fa-user-tie"></i> <span id="profModalNome">Profissional</span></h3>
        <p class="modal-resumo" id="profModalResumo"></p>
    </div>
</div>

{{-- MODAL PIX --}}
<div class="modal-overlay" id="modalPix">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharModalPix()">✕</button>
        <h3><i class="fas fa-qrcode"></i> PAGAMENTO PIX</h3>
        <p class="modal-sub" id="pixDescricao"></p>
        <p class="modal-valor" id="pixValor">Gerando QR Code...</p>
        <p id="pixRecorrenteNota" style="display:none; color:#d4ff00; font-size:0.78rem; font-weight:700; margin:0 0 12px; background:rgba(212,255,0,0.08); border:1px solid rgba(212,255,0,0.25); border-radius:10px; padding:8px 12px;">
            🔁 Assinatura mensal — uma nova cobrança PIX é gerada todo mês.
        </p>
        <img class="pix-qr" id="pixQr" src="" alt="QR Code PIX">
        <div class="pix-copia-wrap">
            <input type="text" id="pixCopia" readonly>
            <button onclick="copiarPix()"><i class="fas fa-copy"></i></button>
        </div>
        <p style="color: var(--text-muted); font-size: 0.75rem; text-align: center;">Escaneie o QR Code ou use o copia e cola. A confirmação é automática.</p>
        <p class="pix-status" id="pixStatus"></p>
    </div>
</div>

{{-- MODAL CARTÃO --}}
<div class="modal-overlay" id="modalCartao">
    <div class="modal-box">
        <button class="modal-fechar" onclick="fecharModalCartao()">✕</button>
        <h3><i class="fas fa-credit-card"></i> PAGAMENTO COM CARTÃO</h3>
        <p class="modal-sub" id="cartaoDescricao"></p>
        <p class="modal-valor" id="cartaoValor"></p>

        <form class="form-cartao" id="formCartao" onsubmit="submeterCartao(event)" autocomplete="off">
            <div class="campo">
                <label>Número do cartão</label>
                <input id="cartaoNumero" type="text" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" oninput="formatarNumeroCartao(this)" required>
            </div>
            <div class="campo">
                <label>Nome impresso no cartão</label>
                <input id="cartaoNomeTitular" type="text" placeholder="NOME SOBRENOME" maxlength="100" oninput="this.value=this.value.toUpperCase()" required>
            </div>
            <div class="linha campo">
                <div>
                    <label>Validade (MM/AA)</label>
                    <input id="cartaoValidade" type="text" inputmode="numeric" maxlength="5" placeholder="MM/AA" oninput="formatarValidade(this)" required>
                </div>
                <div>
                    <label>CVV</label>
                    <input id="cartaoCCV" type="text" inputmode="numeric" maxlength="4" placeholder="123" oninput="this.value=this.value.replace(/\D/g,'')" required>
                </div>
            </div>

            <hr style="border:none; border-top:1px solid var(--border); margin:4px 0 16px;">
            <p style="color: var(--text-muted); font-size: 0.72rem; margin-bottom: 12px;">Dados do titular (necessários para antifraude)</p>

            <div class="campo">
                <label>CPF do titular</label>
                <input id="cartaoCPF" type="text" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" oninput="formatarCPF(this)" required>
            </div>
            <div class="linha campo">
                <div style="flex:2;">
                    <label>CEP</label>
                    <input id="cartaoCEP" type="text" inputmode="numeric" maxlength="9" placeholder="00000-000" oninput="formatarCEP(this)" required>
                </div>
                <div>
                    <label>Número</label>
                    <input id="cartaoNumeroEnd" type="text" maxlength="20" placeholder="277" required>
                </div>
            </div>
            <div class="campo">
                <label>Telefone / WhatsApp</label>
                <input id="cartaoTelefone" type="text" inputmode="numeric" maxlength="15" placeholder="(11) 99999-9999" oninput="formatarTelefone(this)" required>
            </div>

            <p class="erro-msg" id="cartaoErro"></p>
            <p class="ok-msg" id="cartaoSucesso">✅ Pagamento aprovado! Atualizando...</p>

            <button type="submit" class="btn-confirmar" id="btnSubmeterCartao"><i class="fas fa-lock"></i> Pagar com Segurança</button>
        </form>
    </div>
</div>

<script>
    const ACADEMIA_ID   = {!! json_encode($academia->id) !!};
    const ACADEMIA_NOME = {!! json_encode($academia->nome) !!};
    const CSRF          = '{{ csrf_token() }}';
    const CLIENTE_TEL   = {!! json_encode($cliente->whatsapp ?? '') !!};
    const CLIENTE_CEP   = {!! json_encode($cliente->cep ?? '') !!};

    function verProfissional(nome, resumo) {
        document.getElementById('profModalNome').textContent = nome || 'Profissional';
        document.getElementById('profModalResumo').textContent = resumo || 'Sem resumo disponível.';
        document.getElementById('profModal').classList.add('aberto');
    }
    function fecharProfModal() {
        document.getElementById('profModal').classList.remove('aberto');
    }

    // ============ PIX ============
    let pixPolling = null;

    function abrirModalPix(descricao) {
        document.getElementById('pixDescricao').textContent = descricao;
        document.getElementById('pixValor').textContent = 'Gerando QR Code...';
        document.getElementById('pixQr').src = '';
        document.getElementById('pixCopia').value = '';
        document.getElementById('pixStatus').style.display = 'none';
        document.getElementById('modalPix').classList.add('aberto');
    }
    function fecharModalPix() {
        document.getElementById('modalPix').classList.remove('aberto');
        if (pixPolling) { clearInterval(pixPolling); pixPolling = null; }
    }
    function copiarPix() {
        const input = document.getElementById('pixCopia');
        input.select();
        document.execCommand('copy');
    }

    async function pagarPlanoPix(planoId, planoNome, valor) {
        abrirModalPix(`Plano ${planoNome} — ${ACADEMIA_NOME}`);
        try {
            const res = await fetch('/api/criar-pagamento-academia', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ academia_id: ACADEMIA_ID, plano_id: planoId }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao gerar pagamento.');

            document.getElementById('pixQr').src = 'data:image/png;base64,' + data.pixQrCode;
            document.getElementById('pixCopia').value = data.pixPayload;
            document.getElementById('pixValor').textContent = 'R$ ' + parseFloat(data.amount).toFixed(2).replace('.', ',');
            document.getElementById('pixRecorrenteNota').style.display = data.recorrente ? 'block' : 'none';

            pixPolling = setInterval(async () => {
                try {
                    const sr = await fetch('/api/pagamento/status/' + data.asaasPaymentId, { headers: { 'X-CSRF-TOKEN': CSRF } });
                    const sd = await sr.json();
                    if (sd.confirmed) {
                        clearInterval(pixPolling);
                        const msg = document.getElementById('pixStatus');
                        msg.textContent = '✅ Pagamento confirmado! Bem-vindo à academia!';
                        msg.style.color = 'var(--primary)';
                        msg.style.display = 'block';
                        setTimeout(() => window.location.reload(), 2500);
                    }
                } catch (_) {}
            }, 4000);
        } catch (err) {
            alert('Erro ao iniciar pagamento: ' + err.message);
            fecharModalPix();
        }
    }

    // ============ CARTÃO ============
    let cartaoCtx = null;

    function pagarPlanoCartao(planoId, planoNome, valor) {
        cartaoCtx = {
            endpoint: '/api/criar-pagamento-cartao-academia',
            payload: { academia_id: ACADEMIA_ID, plano_id: planoId },
        };
        document.getElementById('cartaoDescricao').textContent = `Plano ${planoNome} — ${ACADEMIA_NOME}`;
        document.getElementById('cartaoValor').textContent = 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
        document.getElementById('formCartao').reset();
        document.getElementById('cartaoErro').style.display = 'none';
        document.getElementById('cartaoSucesso').style.display = 'none';
        const btn = document.getElementById('btnSubmeterCartao');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Pagar com Segurança';
        document.getElementById('cartaoTelefone').value = CLIENTE_TEL || '';
        document.getElementById('cartaoCEP').value = CLIENTE_CEP || '';
        document.getElementById('modalCartao').classList.add('aberto');
    }
    function fecharModalCartao() {
        document.getElementById('modalCartao').classList.remove('aberto');
        cartaoCtx = null;
    }

    async function submeterCartao(e) {
        e.preventDefault();
        if (!cartaoCtx) return;

        const validade = document.getElementById('cartaoValidade').value.trim();
        const parts = validade.split('/');
        if (parts.length !== 2 || parts[0].length !== 2 || parts[1].length !== 2) {
            const erro = document.getElementById('cartaoErro');
            erro.textContent = 'Validade inválida. Use o formato MM/AA.';
            erro.style.display = 'block';
            return;
        }

        const cardData = {
            card_holder:       document.getElementById('cartaoNomeTitular').value.trim(),
            card_number:       document.getElementById('cartaoNumero').value.replace(/\s/g, ''),
            card_expiry_month: parts[0],
            card_expiry_year:  '20' + parts[1],
            card_ccv:          document.getElementById('cartaoCCV').value.trim(),
            cpf:               document.getElementById('cartaoCPF').value.replace(/\D/g, ''),
            cep:               document.getElementById('cartaoCEP').value.replace(/\D/g, ''),
            numero:            document.getElementById('cartaoNumeroEnd').value.trim(),
            telefone:          document.getElementById('cartaoTelefone').value.replace(/\D/g, ''),
        };

        const btn = document.getElementById('btnSubmeterCartao');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        document.getElementById('cartaoErro').style.display = 'none';

        try {
            const res = await fetch(cartaoCtx.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ ...cartaoCtx.payload, ...cardData }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erro ao processar cartão.');

            if (data.confirmed) {
                document.getElementById('cartaoSucesso').style.display = 'block';
                setTimeout(() => { fecharModalCartao(); window.location.reload(); }, 2000);
            } else {
                throw new Error('Pagamento não confirmado. Verifique os dados e tente novamente.');
            }
        } catch (err) {
            document.getElementById('cartaoErro').textContent = err.message;
            document.getElementById('cartaoErro').style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Pagar com Segurança';
        }
    }

    function formatarNumeroCartao(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 16);
        input.value = v.replace(/(.{4})/g, '$1 ').trim();
    }
    function formatarValidade(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 4);
        if (v.length >= 3) v = v.substring(0, 2) + '/' + v.substring(2);
        input.value = v;
    }
    function formatarCPF(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = v;
    }
    function formatarCEP(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 8);
        if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5);
        input.value = v;
    }
    function formatarTelefone(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 11);
        if (v.length > 2) v = '(' + v.substring(0, 2) + ') ' + v.substring(2);
        if (v.length > 10) v = v.substring(0, 10) + '-' + v.substring(10);
        input.value = v;
    }

    window.addEventListener('click', e => {
        if (e.target === document.getElementById('profModal')) fecharProfModal();
        if (e.target === document.getElementById('modalPix')) fecharModalPix();
        if (e.target === document.getElementById('modalCartao')) fecharModalCartao();
    });
</script>
</body>
</html>
