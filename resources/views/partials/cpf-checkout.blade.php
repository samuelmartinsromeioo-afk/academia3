{{--
    CPF no checkout — modal + o wrapper de criação de cobrança.

    O processador de pagamentos exige CPF para criar o cliente, e o cadastro do
    aluno não o coleta de propósito: quem só usa a parte gratuita nunca precisa
    entregar documento (minimização de dado, LGPD art. 6º, III). Então ele é
    pedido aqui, na PRIMEIRA cobrança da vida do aluno, e guardado em
    `clientes.cpf` — nas vezes seguintes nada disto aparece.

    Quem decide se o modal abre é o SERVIDOR, que responde 422 com
    `precisa_cpf: true` só quando falta de verdade (ver
    `AsaasService::garantirCpfDoCliente`). O front não adivinha nem mostra o
    campo por via das dúvidas: quem já tem cliente criado no Asaas nunca chega
    a esse ponto.

    Partial, e não uma cópia por view, porque são QUATRO telas que criam
    cobrança (cliente/index, academia-detalhes, studio-detalhes,
    loja-detalhes) — e nenhuma delas estende um layout comum, então sem isto a
    regra existiria em quatro lugares e divergiria no primeiro ajuste.

    Inclua ANTES do </body>. Nada aqui roda sozinho: só responde a clique.
--}}

<div id="modalCpf" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:100000; flex-direction:column; justify-content:center; align-items:center; backdrop-filter:blur(6px); padding:20px;">
    <div style="background:#16181d; border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:30px; max-width:380px; width:100%;">
        <h3 style="color:var(--primary); font-size:1rem; font-weight:900; margin:0 0 10px;">CONFIRME SEU CPF</h3>
        <p style="color:#a0a0a0; font-size:0.82rem; line-height:1.55; margin:0 0 16px;">
            O CPF é exigido pelo banco que processa o pagamento. Pedimos uma vez só —
            nas próximas compras ele já fica guardado.
        </p>

        <input id="cpfCheckout" type="text" inputmode="numeric" placeholder="000.000.000-00"
               oninput="formatarCpfInput(this)" maxlength="14"
               style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:12px 14px; color:#fff; font-size:1rem; outline:none; margin-bottom:8px;">

        <p id="cpfCheckoutMsg" style="color:#ff8a8a; font-size:0.78rem; min-height:1.1em; margin:0 0 14px;"></p>

        <div style="display:flex; gap:10px;">
            <button id="cpfCheckoutCancelar" type="button"
                    style="flex:1; background:rgba(255,255,255,0.06); color:#cfcfcf; border:none; border-radius:10px; padding:12px; font-weight:700; font-size:0.85rem; cursor:pointer;">Cancelar</button>
            <button id="cpfCheckoutOk" type="button"
                    style="flex:2; background:var(--primary); color:#000; border:none; border-radius:10px; padding:12px; font-weight:900; font-size:0.85rem; cursor:pointer;">Continuar</button>
        </div>
    </div>
</div>

<script>
    /** Pede o CPF no modal e resolve com os 11 dígitos, ou null se desistir. */
    function pedirCpf(mensagem) {
        return new Promise((resolve) => {
            const fundo    = document.getElementById('modalCpf');
            const campo    = document.getElementById('cpfCheckout');
            const aviso    = document.getElementById('cpfCheckoutMsg');
            const okBtn    = document.getElementById('cpfCheckoutOk');
            const cancelar = document.getElementById('cpfCheckoutCancelar');

            aviso.textContent = mensagem || '';
            campo.value = '';
            fundo.style.display = 'flex';
            setTimeout(() => campo.focus(), 50);

            // Os handlers são soltos ao fechar: sem isto, pagar → cancelar →
            // pagar acumularia listeners e um clique resolveria duas promessas.
            const encerrar = (valor) => {
                fundo.style.display = 'none';
                okBtn.onclick = null;
                cancelar.onclick = null;
                campo.onkeydown = null;
                resolve(valor);
            };

            const confirmar = () => {
                const digitos = campo.value.replace(/\D/g, '');
                if (digitos.length !== 11) {
                    aviso.textContent = 'O CPF precisa ter 11 números.';
                    return;
                }
                // A validação de verdade (dígitos verificadores) é do servidor;
                // aqui só se evita o ida-e-volta do caso óbvio.
                encerrar(digitos);
            };

            okBtn.onclick = confirmar;
            cancelar.onclick = () => encerrar(null);
            campo.onkeydown = (e) => { if (e.key === 'Enter') confirmar(); };
        });
    }

    function formatarCpfInput(input) {
        let v = input.value.replace(/\D/g, '').substring(0, 11);
        if (v.length > 9)      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '$1.$2.$3-$4');
        else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
        else if (v.length > 3) v = v.replace(/(\d{3})(\d+)/, '$1.$2');
        input.value = v;
    }

    /**
     * Cria a cobrança, pedindo o CPF se — e só se — o servidor disser que falta.
     *
     * Devolve o corpo da resposta e lança `Error` com a mensagem do servidor em
     * qualquer falha, que é a forma que os `catch` das telas já esperam.
     */
    async function criarCobranca(payload, url = '/api/criar-pagamento') {
        const chamar = async (corpo) => {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(corpo),
            });
            return { res, dados: await res.json() };
        };

        let { res, dados } = await chamar(payload);

        if (!res.ok && dados.precisa_cpf) {
            const cpf = await pedirCpf(dados.error);
            if (!cpf) throw new Error('Pagamento cancelado.');
            ({ res, dados } = await chamar({ ...payload, cpf }));
        }

        if (!res.ok) throw new Error(dados.error || 'Erro ao gerar pagamento.');
        return dados;
    }
</script>
