<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade — SNR FIT</title>
    <link rel="icon" type="image/png" href="{{ asset('icone-snrfit.png') }}">
    @include('partials.brand-head')
    <style>
        :root { --bg:#0a0b0d; --card:#14161a; --text:#e8e8e8; --muted:#9a9a9a; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:var(--text); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; line-height:1.7; }
        a { color:var(--primary); }
        .top { padding:18px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px; position:sticky; top:0; background:rgba(10,11,13,0.9); backdrop-filter:blur(8px); }
        .top a.back { color:var(--primary); }
        .top .logo { font-weight:900; color:var(--primary); letter-spacing:0.5px; }
        .top .print { margin-left:auto; background:transparent; border:1px solid var(--border); color:var(--muted); border-radius:999px; padding:7px 14px; font-size:0.8rem; cursor:pointer; font-family:inherit; }
        .wrap { max-width:860px; margin:0 auto; padding:30px 22px 70px; }
        h1 { color:var(--primary); font-size:1.8rem; font-weight:900; margin-bottom:6px; }
        .upd { color:var(--muted); font-size:0.82rem; margin-bottom:26px; }
        h2 { color:#fff; font-size:1.15rem; margin:30px 0 10px; display:flex; align-items:center; gap:10px; }
        h2 i { color:var(--primary); font-size:0.95rem; }
        h3 { color:#fff; font-size:0.98rem; margin:20px 0 8px; }
        p, li { color:var(--text); font-size:0.95rem; margin-bottom:8px; }
        ul, ol { padding-left:22px; margin-bottom:10px; }
        strong { color:var(--primary); }
        .box { background:var(--card); border:1px solid var(--border); border-left:3px solid var(--primary); border-radius:10px; padding:14px 16px; margin:12px 0; font-size:0.9rem; }
        .sens { border-left-color:#ff5252; }
        .sens strong { color:#ff8a8a; }
        .muted { color:var(--muted); font-size:0.85rem; }
        .lei { color:var(--muted); font-size:0.8rem; opacity:0.85; }
        .lei strong { color:inherit; }
        .tbl-wrap { overflow-x:auto; margin:14px 0; }
        table { width:100%; border-collapse:collapse; font-size:0.86rem; min-width:620px; }
        th, td { border:1px solid var(--border); padding:9px 11px; text-align:left; vertical-align:top; }
        th { background:rgba(124,255,0,0.07); color:var(--primary); font-weight:700; }
        td strong { color:var(--text); }
        @media print {
            .top { display:none; }
            html, body { background:#fff; }
            h1, h2, h3 { color:#000; }
            p, li, td, th { color:#333; }
            .box { background:#f7f7f7; }
        }
    </style>
</head>

<body class="ed-page">
    @php
        $emp = fn ($c) => \App\Support\Empresa::get($c);
        $idadeAluno = \App\Support\CadastroHelper::IDADE_MINIMA_ALUNO;
        $idadeProf = \App\Support\CadastroHelper::IDADE_MINIMA_PROFISSIONAL;
        $vigencia = \Carbon\Carbon::parse(config('termos.vigente_desde'))->format('d/m/Y');
    @endphp

    <div class="top">
        <a href="{{ url()->previous() }}" class="back"><i class="ph ph-arrow-left"></i></a>
        <span class="logo">SNR FIT</span>
        <button class="print" onclick="window.print()"><i class="ph ph-printer"></i> Imprimir</button>
    </div>

    <div class="wrap">
        <h1>Política de Privacidade</h1>
        {{--
            A data é a de VIGÊNCIA, do config — não `now()`. Antes esta linha
            imprimia a data de hoje, de modo que o documento afirmava ter sido
            atualizado no dia em que a pessoa o abria. Num documento que serve de
            prova de informação ao titular, isso é uma afirmação falsa.
        --}}
        <p class="upd">Em conformidade com a Lei nº 13.709/2018 (LGPD) · Versão {{ config('termos.versao') }} ·
            Em vigor desde {{ $vigencia }}</p>

        <p>Esta Política explica, de forma clara e completa, <strong>quais dados pessoais</strong> o
            SNR FIT trata, <strong>para quê</strong>, <strong>com qual fundamento legal</strong>,
            <strong>com quem</strong> compartilha, <strong>por quanto tempo</strong> guarda e
            <strong>quais são os seus direitos</strong>. Ela integra os
            <a href="{{ route('termos') }}">Termos de Uso</a> e, quanto a dados pessoais, prevalece
            sobre eles em caso de divergência.</p>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-identification-card"></i> 1. Quem é o controlador dos seus dados</h2>
        <ul>
            <li><strong>Controlador:</strong> {{ $emp('razao_social') }}</li>
            <li><strong>CNPJ:</strong> {{ $emp('cnpj') }}</li>
            @if($endereco = \App\Support\Empresa::enderecoCompleto())
                <li><strong>Endereço:</strong> {{ $endereco }}</li>
            @endif
            <li><strong>Encarregado pelo Tratamento de Dados Pessoais (DPO):</strong>
                {{ $emp('dpo_nome') }} —
                <a href="mailto:{{ $emp('dpo_email') }}">{{ $emp('dpo_email') }}</a></li>
        </ul>
        <p class="lei">Identificação do controlador e do encarregado conforme os arts. 9º, I, e 41 da
            LGPD, e art. 2º do Decreto nº 7.962/2013.</p>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-users-three"></i> 2. Quem é controlador do quê (os papéis)</h2>
        <p>A Plataforma reúne vários atores, e <strong>não é a mesma pessoa que responde por todo
                tratamento</strong>. Saber quem é o controlador de cada parcela define a quem você
            dirige um pedido e quem responde por um uso indevido:</p>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tratamento</th>
                        <th>Controlador</th>
                        <th>Papel do SNR FIT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Sua conta, autenticação, segurança, cobrança e prevenção a fraude</td>
                        <td>SNR FIT</td>
                        <td><strong>Controlador</strong></td>
                    </tr>
                    <tr>
                        <td>Treinos, fichas, medidas e anamnese que o seu personal acessa para te atender</td>
                        <td>O <strong>Personal</strong> (quanto ao uso profissional que faz deles)</td>
                        <td>Controlador quanto à hospedagem e às ferramentas; <strong>operador</strong>
                            quanto ao uso profissional</td>
                    </tr>
                    <tr>
                        <td>Cadastro, planos, frequência e avaliações dos alunos de uma academia ou studio</td>
                        <td>A <strong>Academia</strong> ou o <strong>Studio</strong></td>
                        <td><strong>Operador</strong> (art. 39 da LGPD)</td>
                    </tr>
                    <tr>
                        <td>Dados de entrega e faturamento de um pedido da loja</td>
                        <td>A <strong>Loja</strong></td>
                        <td><strong>Operador</strong></td>
                    </tr>
                    <tr>
                        <td>Liquidação financeira, antifraude do pagamento e dados do cartão</td>
                        <td>{{ $emp('processador_pagamentos') }}</td>
                        <td>Não trata: <strong>não recebemos nem armazenamos o cartão completo</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p>Quando atuamos como <strong>operador</strong>, tratamos os dados <strong>conforme as
                instruções do controlador</strong> e não os usamos para finalidade própria. Profissionais
            e estabelecimentos assumem, nos Termos, o dever de ter base legal, manter sigilo e atender
            os titulares — e respondem por isso perante você e perante a ANPD.</p>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-database"></i> 3. Dados que coletamos</h2>

        <h3>3.1. Que você nos fornece</h3>
        <ul>
            <li><strong>Cadastro:</strong> nome, e-mail, senha (armazenada apenas como hash),
                telefone/WhatsApp, data de nascimento, sexo, CEP e endereço, foto.</li>
            <li><strong>Profissionais:</strong> adicionalmente CPF ou CNPJ, registro profissional
                (CREF), especialidades, preços e dados bancários/chave PIX para recebimento.</li>
            <li><strong>Dados de treino:</strong> fichas, exercícios, cargas, séries, frequência,
                metas e treinos concluídos.</li>
            <li><strong>Medidas corporais e fotos de progresso</strong>, quando você as registra.</li>
            <li><strong>Mensagens</strong> trocadas no chat com o profissional ou estabelecimento.</li>
            <li><strong>Avaliações</strong> que você escreve sobre serviços contratados.</li>
        </ul>

        <h3>3.2. Que o uso gera automaticamente</h3>
        <ul>
            <li><strong>Registros de acesso à aplicação:</strong> data, hora e endereço IP — guardados
                por 6 meses por <strong>obrigação legal</strong> (art. 15 da Lei nº 12.965/2014).</li>
            <li><strong>Dados do dispositivo e do app:</strong> modelo, sistema operacional, versão do
                aplicativo e identificador de notificação (token de push).</li>
            <li><strong>Localização aproximada</strong> — somente no momento em que você toca em
                "Perto de mim", para ordenar resultados por proximidade.
                <strong>Não rastreamos localização em segundo plano</strong> e o app
                <strong>não pede</strong> permissão de localização contínua. A coordenada vai ao
                nosso servidor apenas <strong>dentro daquela consulta</strong>, para calcular a
                distância até cada estabelecimento, e <strong>não é gravada</strong> em banco de
                dados nem associada ao seu cadastro. Antes de o sistema pedir a permissão, o app
                mostra uma tela explicando para que serve e o que continua funcionando se você
                recusar.
                <br>
                <span class="muted">Transparência sobre um detalhe técnico: a busca por proximidade é
                    uma requisição cujo endereço contém a coordenada, e o <strong>registro de acesso
                        do servidor</strong> (primeiro item desta lista) guarda esse endereço pelos 6
                    meses que a lei impõe. Para que esse registro nunca contenha mais do que
                    declaramos coletar, o app <strong>reduz a precisão</strong> da coordenada antes de
                    enviá-la, para cerca de <strong>110 metros</strong> — a mesma ordem de grandeza da
                    localização aproximada que pedimos ao sistema. Nenhuma função é afetada: para
                    ordenar uma lista por distância, 110 metros não mudam o resultado.</span></li>
            <li><strong>Cookies</strong> essenciais e, com seu consentimento, de medição (seção 9).</li>
        </ul>
        <p class="muted">As coordenadas de <strong>profissionais e estabelecimentos</strong> são
            outra coisa: vêm do <strong>endereço que eles próprios cadastram</strong>, convertido em
            latitude e longitude por serviço de mapas, e ficam guardadas no cadastro deles — é o que
            permite calcular a distância e ordenar a vitrine. São dados do negócio, exibidos
            publicamente por decisão de quem os cadastrou. <strong>Alunos não têm coordenada
                armazenada.</strong></p>

        <h3>3.3. Dados sensíveis de saúde</h3>
        <div class="box sens">
            <strong><i class="ph ph-heartbeat"></i> Anamnese e dados de saúde (art. 11 da LGPD).</strong>
            A anamnese (PAR-Q, lesões, doenças, cirurgias, medicamentos, gestação e restrições), as
            medidas corporais, os resultados de avaliação física e as fotos de progresso são
            <strong>dados pessoais sensíveis</strong>. Eles são coletados mediante o seu
            <strong>consentimento específico e destacado</strong> (art. 11, I) e usados
            <strong>exclusivamente</strong> para que o profissional monte e acompanhe um treino seguro.
            <ul style="margin:10px 0 0;">
                <li>O preenchimento é <strong>opcional</strong>: você pode deixar em branco e usar a
                    Plataforma.</li>
                <li>O consentimento é uma <strong>confirmação própria</strong>, que você marca na
                    tela da anamnese — no app e no site — e sem a qual nada é gravado. Cada
                    concessão e cada revogação fica <strong>registrada com data, hora, endereço IP e
                        origem</strong>, de forma incremental: a revogação não apaga o registro da
                    concessão anterior, que é o que explica por que o dado existiu até ali.</li>
                <li>Você pode <strong>revogar o consentimento</strong> a qualquer momento, sem
                    justificar, em <strong>Privacidade e meus dados</strong> (no app e no site) ou na
                    própria tela da anamnese. A revogação apaga a anamnese <strong>na hora</strong>,
                    você recebe a confirmação por escrito e a <strong>sua conta continua
                        ativa</strong> — você segue treinando e contratando normalmente.
                    <strong>Não é preciso excluir a conta para remover um dado de saúde.</strong></li>
                <li><strong>Medidas corporais, resultados de avaliação física e fotos de
                        progresso</strong> são removidos nas telas em que você os registrou, item por
                    item — e todos de uma vez se você excluir a conta.</li>
                <li>Se a <strong>academia ou o studio</strong> preencher a sua anamnese pelo painel
                    deles, o estabelecimento <strong>declara</strong> ter obtido a sua autorização, e
                    essa declaração fica registrada com a identidade dele, a data e o IP. Ninguém
                    consente pelo outro: a base legal continua sendo o <strong>seu</strong>
                    consentimento, o estabelecimento responde pela veracidade do que declarou, e
                    <strong>você revoga pelo mesmo caminho</strong> — como se tivesse preenchido você
                    mesmo.</li>
                <li>Esses dados <strong>nunca</strong> são usados para publicidade, perfilamento
                    comercial, venda, cessão a terceiro, nem compartilhados com a Meta ou qualquer
                    ferramenta de marketing.</li>
                <li>Quem os vê é apenas o <strong>profissional que te atende</strong> e, se você for
                    aluno de uma academia ou studio, a equipe técnica dela.</li>
            </ul>
        </div>

        <h3>3.4. O que NÃO coletamos</h3>
        <ul>
            <li><strong>Dados completos de cartão</strong> (número, CVV): são digitados e guardados
                pelo processador de pagamentos, não por nós.</li>
            <li><strong>Sua senha em texto legível</strong> — nem nós podemos lê-la.</li>
            <li><strong>Microfone, contatos, agenda, SMS, histórico de navegação externo</strong> ou
                localização em segundo plano.</li>
            <li><strong>Dados de origem racial, convicção religiosa, opinião política, filiação
                    sindical, vida sexual ou dado genético/biométrico.</strong></li>
            <li><strong>Dados de menores de {{ $idadeAluno }} anos</strong> (seção 11).</li>
        </ul>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-scales"></i> 4. Para que usamos, com qual base legal e por quanto tempo</h2>
        <p>Cada finalidade tem base legal própria. Não usamos "consentimento" como fundamento genérico:
            o que é necessário para executar o contrato não depende de autorização separada, e o que é
            opcional você pode recusar sem perder o serviço.</p>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Finalidade</th>
                        <th>Dados</th>
                        <th>Base legal (LGPD)</th>
                        <th>Retenção</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Criar e manter sua conta; autenticar o acesso</td>
                        <td>Cadastro, credencial</td>
                        <td><strong>Execução de contrato</strong> (art. 7º, V)</td>
                        <td>Enquanto a conta existir</td>
                    </tr>
                    <tr>
                        <td>Conectar você a profissionais; agendar; montar e acompanhar treino</td>
                        <td>Perfil, treinos, agenda, medidas</td>
                        <td><strong>Execução de contrato</strong> (art. 7º, V)</td>
                        <td>Enquanto a conta existir</td>
                    </tr>
                    <tr>
                        <td>Montar treino seguro considerando sua saúde</td>
                        <td>Anamnese e dados de saúde</td>
                        <td><strong>Consentimento específico</strong> (art. 11, I)</td>
                        <td>Até a revogação ou a exclusão da conta</td>
                    </tr>
                    <tr>
                        <td>Processar pagamento, assinatura e repasse</td>
                        <td>Identificação, valores, meio de pagamento</td>
                        <td><strong>Execução de contrato</strong> (art. 7º, V) e
                            <strong>obrigação legal</strong> (art. 7º, II)</td>
                        <td><strong>5 anos</strong> após a transação (prazos fiscal e prescricional)</td>
                    </tr>
                    <tr>
                        <td>Ordenar resultados por proximidade ("Perto de mim")</td>
                        <td>Localização aproximada, só no momento da busca</td>
                        <td><strong>Consentimento</strong> (art. 7º, I) — permissão do sistema
                            operacional, precedida de aviso no app</td>
                        <td><strong>Não armazenada</strong>: usada dentro da consulta e
                            descartada</td>
                    </tr>
                    <tr>
                        <td>Provar que você consentiu, e quando (saúde e cookies)</td>
                        <td>Tipo do consentimento, data, hora, IP, origem</td>
                        <td><strong>Obrigação legal</strong> (art. 7º, II) — o ônus de provar o
                            consentimento é nosso (art. 8º, §1º)</td>
                        <td><strong>5 anos</strong> após o fim do tratamento, para defesa em
                            processo</td>
                    </tr>
                    <tr>
                        <td>Avisos do serviço: mensagem no chat, confirmação de pagamento, aula
                            agendada, lembrete de aula</td>
                        <td>Contato, token de push</td>
                        <td><strong>Execução de contrato</strong> (art. 7º, V)</td>
                        <td>Enquanto a conta existir</td>
                    </tr>
                    <tr>
                        <td>Prevenção a fraude, abuso e segurança da Plataforma</td>
                        <td>IP, registros de uso, padrões de cadastro e indicação</td>
                        <td><strong>Legítimo interesse</strong> (art. 7º, IX)</td>
                        <td>Até 5 anos, conforme a necessidade de defesa</td>
                    </tr>
                    <tr>
                        <td>Guarda de registros de acesso</td>
                        <td>Data, hora, IP</td>
                        <td><strong>Obrigação legal</strong> (art. 7º, II; art. 15 do Marco Civil)</td>
                        <td><strong>6 meses</strong> (ou mais, por ordem judicial)</td>
                    </tr>
                    <tr>
                        <td>Exercício de direitos em processo administrativo ou judicial</td>
                        <td>O necessário ao caso</td>
                        <td><strong>Exercício regular de direitos</strong> (art. 7º, VI)</td>
                        <td>Até o fim do processo e dos prazos recursais</td>
                    </tr>
                    <tr>
                        <td>Medir e melhorar campanhas de anúncio <strong>(só no site)</strong></td>
                        <td>Eventos de navegação, identificadores pseudonimizados por hash, IP</td>
                        <td><strong>Consentimento</strong> (art. 7º, I) — banner de cookies; sem
                            aceite, nada é enviado, <strong>nem na renovação de assinatura</strong></td>
                        <td>Conforme a política da Meta; revogável a qualquer tempo pelo mesmo
                            banner</td>
                    </tr>
                    <tr>
                        <td>Comunicação promocional (quando houver)</td>
                        <td>Contato</td>
                        <td><strong>Consentimento</strong> (art. 7º, I), com descadastramento</td>
                        <td>Até a revogação</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="box">
            <strong>Avisos do serviço x publicidade.</strong> Os avisos operacionais acima — por
            exemplo, "sua aula é amanhã" ou "pagamento confirmado" — fazem parte do serviço que você
            contratou e não são publicidade. Comunicação <strong>promocional</strong> só é enviada com
            consentimento e sempre com meio de descadastramento; recusá-la não afeta o uso da
            Plataforma.
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-share-network"></i> 5. Com quem compartilhamos</h2>
        <ul>
            <li><strong>O profissional, academia, studio ou loja que você contratou:</strong> recebe os
                dados necessários para atendê-lo (nome, contato, treinos, evolução e — com seu
                consentimento — anamnese; no caso da loja, apenas dados de entrega e faturamento).</li>
            <li><strong>{{ $emp('processador_pagamentos') }}:</strong> processamento de pagamentos,
                liquidação, antifraude e repasse.</li>
            <li><strong>Provedores de infraestrutura, hospedagem e e-mail</strong>, na
                condição de operadores, apenas para entregar o serviço.</li>
            <li><strong>Serviços de notificação push:</strong> Expo e as plataformas Apple (APNs) e
                Google (FCM), exclusivamente para entregar o aviso ao seu aparelho.</li>
            {{--
                Esta frase só pode continuar escrita enquanto NÃO houver nenhum
                outro envio nosso para a Meta.

                Ela já foi falsa: até 2026-10-19 os avisos transacionais saíam
                pela WhatsApp Cloud API (`graph.facebook.com`), ou seja a Meta
                recebia o TELEFONE e o CONTEÚDO de cada aviso, por execução de
                contrato e sem passar por consentimento nenhum. O canal foi
                removido (ver a seção "WhatsApp" no CLAUDE.md) e a frase voltou
                a ser verdadeira. Religar qualquer envio à Meta exige reescrever
                este item ANTES, com a base legal correta.
            --}}
            <li><strong>Meta (Facebook/Instagram):</strong> apenas <strong>com o seu
                    consentimento</strong>, para medição de anúncios (seção 9). Não enviamos
                os seus dados à Meta para nenhuma outra finalidade.</li>
            <li><strong>Autoridades públicas e Judiciário:</strong> quando houver requisição legal ou
                ordem judicial, nos limites do pedido.</li>
            <li><strong>Em reorganização societária</strong> (fusão, aquisição, cisão): o adquirente,
                que fica sujeito a esta Política, com comunicação prévia a você.</li>
        </ul>
        <div class="box">
            <strong>Não vendemos seus dados pessoais</strong>, não os cedemos a corretores de dados e
            não os usamos para treinar modelos de inteligência artificial.
        </div>

        <h3>5.1. Transferência internacional</h3>
        <p>Alguns operadores (hospedagem, e-mail, push e Meta) podem tratar dados em servidores
            <strong>fora do Brasil</strong>. Nesses casos a transferência se dá com fundamento no
            <strong>art. 33 da LGPD</strong> — necessidade para a execução do contrato e cumprimento
            de obrigação, e mediante cláusulas contratuais e salvaguardas oferecidas pelo operador —,
            limitada às finalidades desta Política.</p>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-clock"></i> 6. Por quanto tempo guardamos e o que acontece ao excluir</h2>
        <p>Os prazos por finalidade estão na tabela da seção 4. Em resumo:</p>
        <ul>
            <li>Mantemos os dados <strong>enquanto a sua conta existir</strong>.</li>
            <li>Ao <strong>excluir a conta</strong>, anonimizamos o perfil e eliminamos os dados
                pessoais e sensíveis. O efeito é <strong>imediato e definitivo</strong>: não há
                carência, não há reativação e não há como desfazer. Enviamos a
                <strong>confirmação por escrito antes</strong> da eliminação, porque depois dela o
                seu contato não existe mais aqui. <strong>As suas assinaturas mensais são canceladas
                    junto</strong>, no mesmo ato e junto ao processador de pagamentos, de modo que não
                há cobrança futura — e a confirmação informa quantas foram canceladas.
                <ul style="margin:6px 0 0;">
                    <li><strong>Apagados:</strong> anamnese, medidas, fotos de progresso, metas,
                        histórico de treinos, registros de carga, mensagens do chat e avisos.</li>
                    <li><strong>Anonimizado:</strong> o seu perfil — nome, e-mail, telefone,
                        endereço, foto e documentos são substituídos, e a senha por um valor
                        aleatório, de modo que ninguém mais acessa a conta. A linha sobrevive sem
                        você dentro dela, porque apagá-la quebraria os registros financeiros que a
                        lei nos obriga a conservar.</li>
                    <li><strong>Mantidos:</strong> apenas os descritos no item seguinte.</li>
                </ul>
            </li>
            <li><strong>Permanecem</strong>, de forma segregada e apenas para a finalidade que a lei
                impõe: os <strong>registros financeiros</strong> (prazo fiscal e prescricional) e os
                <strong>registros de acesso</strong> (6 meses, art. 15 do Marco Civil). É a exceção do
                <strong>art. 16, I, da LGPD</strong>, que autoriza a conservação para cumprimento de
                obrigação legal.</li>
            <li>Dados já <strong>anonimizados</strong> podem ser mantidos para estatística, pois
                deixam de ser dados pessoais (art. 12 da LGPD).</li>
            <li>Conteúdo que você tornou público e que terceiros já replicaram (por exemplo, uma
                avaliação citada) pode persistir fora do nosso controle.</li>
        </ul>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-shield-check"></i> 7. Seus direitos e como exercê-los</h2>
        <p>Como titular, você pode, a qualquer momento e gratuitamente (art. 18 da LGPD):</p>
        <ul>
            <li><strong>Confirmar</strong> que tratamos dados seus e <strong>acessá-los</strong>;</li>
            <li><strong>Corrigir</strong> dado incompleto, inexato ou desatualizado;</li>
            <li>Pedir <strong>anonimização, bloqueio ou eliminação</strong> de dado desnecessário,
                excessivo ou tratado em desconformidade;</li>
            <li>Pedir a <strong>portabilidade</strong> a outro fornecedor;</li>
            <li><strong>Eliminar</strong> dados tratados com base no consentimento;</li>
            <li>Saber com quem <strong>compartilhamos</strong> seus dados;</li>
            <li>Ser informado sobre a <strong>possibilidade de não consentir</strong> e as
                consequências;</li>
            <li><strong>Revogar o consentimento</strong>, inclusive o de dados de saúde;</li>
            <li><strong>Opor-se</strong> a tratamento fundado em legítimo interesse.</li>
        </ul>
        <div class="box">
            <strong>Como exercer:</strong> na página <strong>"Privacidade e meus dados"</strong>,
            dentro do app (Configurações → Privacidade e meus dados) e do site (menu da sua conta).
            Ali você, por conta própria e sem falar com ninguém:
            <ul style="margin:8px 0 0;">
                <li><strong>exporta</strong> os seus dados em formato legível por máquina (JSON) —
                    <em>download</em> direto no site, e <em>anexo por e-mail</em> no app;</li>
                <li><strong>revoga</strong> o consentimento de dados de saúde (apaga a anamnese, sem
                    encerrar a conta) e a escolha sobre <strong>cookies de medição</strong>;</li>
                <li><strong>exclui a conta</strong>, com confirmação por escrito.</li>
            </ul>
            <p style="margin:8px 0 0;">Para <strong>corrigir</strong> um dado, use <em>Editar
                    perfil</em>, no app e no site. O que a tela não permitir, e qualquer outro pedido,
                vai pelo e-mail do Encarregado:
                <a href="mailto:{{ $emp('dpo_email') }}">{{ $emp('dpo_email') }}</a>. Respondemos em
                até <strong>15 dias corridos</strong> contados do recebimento do pedido (art. 19, II,
                da LGPD); se o pedido chegar depois das 18h, em fim de semana ou feriado, o prazo
                começa no primeiro dia útil seguinte. Podemos pedir a confirmação da sua identidade
                antes de atender, justamente para não entregar seus dados a quem se passe por
                você — e nesse caso o prazo volta a correr quando você confirmar.</p>
            <p style="margin:8px 0 0;">O que é feito <strong>pelo produto</strong> tem efeito
                <strong>imediato</strong>, sem prazo de análise: é o sistema que executa, não uma
                fila.</p>
        </div>
        <p>Você pode também <strong>peticionar à Autoridade Nacional de Proteção de Dados (ANPD)</strong>
            (art. 18, § 1º) e reclamar a órgãos de defesa do consumidor.</p>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-lock"></i> 8. Segurança e incidentes</h2>
        <ul>
            <li>Senhas armazenadas com <strong>hash</strong> (bcrypt), nunca em texto legível.</li>
            <li><strong>Controle de acesso</strong> por perfil: cada usuário vê apenas os dados do seu
                escopo; chave PIX de saque é armazenada de forma <strong>criptografada</strong>.</li>
            <li>Tráfego sob <strong>HTTPS</strong>, cabeçalhos de segurança, política de conteúdo
                (CSP), limite de tentativas de login e registro de eventos de segurança.</li>
            <li>Dados de cartão <strong>não transitam</strong> pelos nossos servidores.</li>
        </ul>
        <div class="box">
            <strong>Incidentes.</strong> Nenhum sistema é imune. Havendo incidente de segurança com
            risco ou dano relevante a você, comunicaremos <strong>a ANPD e você</strong> em prazo
            razoável, descrevendo os dados afetados, os riscos e as medidas adotadas, conforme o
            <strong>art. 48 da LGPD</strong>.
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-cookie"></i> 9. Cookies e tecnologias de medição</h2>
        <p><strong>Cookies essenciais:</strong> mantêm você autenticado e garantem o funcionamento e a
            segurança. São indispensáveis à prestação do serviço e, por isso, não dependem de
            consentimento.</p>
        <p><strong>Cookies e ferramentas de medição/marketing:</strong> usamos o <strong>Meta
                Pixel</strong> e a <strong>API de Conversões da Meta</strong> para medir desempenho de
            anúncios. Eles podem gravar cookies (<code>_fbp</code>, <code>_fbc</code>) e enviar à Meta
            eventos de navegação e conversão (cadastro, agendamento, compra), com identificadores
            <strong>pseudonimizados por hash SHA-256</strong> e o seu endereço IP.</p>
        <div class="box">
            <strong><i class="ph ph-check-circle"></i> Você no controle:</strong> essas ferramentas
            <strong>só são ativadas após o seu consentimento</strong> no banner de cookies. Enquanto
            você não aceitar, <strong>nada</strong> é enviado à Meta. Você pode recusar e seguir usando
            a Plataforma normalmente.
            <p style="margin:8px 0 0;"><strong>Para mudar de ideia</strong>, use o botão
                <em>Preferências de cookies</em> no rodapé deste documento e dos Termos de Uso, ou
                <strong>Privacidade e meus dados → Cookies e medição de anúncios → Mudar minha
                    escolha</strong>. O banner reabre mostrando a sua escolha atual, e o efeito é
                imediato — <strong>não é preciso limpar os cookies do navegador</strong>.</p>
            <p style="margin:8px 0 0;">A escolha vale também para os envios que a Plataforma faz
                <strong>do servidor</strong>, sem o seu navegador na frente — notadamente a
                <strong>renovação mensal de assinatura</strong>. Recusado o consentimento, essa
                renovação <strong>não</strong> é enviada à Meta.</p>
            <p style="margin:8px 0 0;"><strong>Dados de saúde, anamnese, medidas e fotos de progresso
                    nunca são enviados a essas ferramentas</strong>, com ou sem consentimento.</p>
        </div>
        <div class="box">
            <strong><i class="ph ph-device-mobile"></i> No aplicativo não existe isto.</strong> O app
            SNR FIT <strong>não contém</strong> Meta Pixel, SDK do Facebook, SDK de anúncios ou de
            atribuição, nem qualquer identificador de publicidade — não há, portanto, banner de
            cookies nele, porque não há nada a consentir. Nada do que você faz dentro do app é
            enviado para medição de publicidade, e o app <strong>não pede</strong> autorização de
            rastreamento (ATT) porque não rastreia.
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-device-mobile"></i> 10. Permissões do aplicativo</h2>
        <p>O app só pede acesso a recursos do aparelho <strong>quando você usa a função
                correspondente</strong>, sempre por um pedido explícito do sistema operacional, que
            você pode recusar. Nas permissões de <strong>localização</strong> e
            <strong>notificações</strong>, o app mostra <strong>antes</strong> uma tela explicando
            para que o dado serve e o que continua funcionando se você disser não — porque o pedido
            do sistema aparece uma única vez, e recusá-lo sem contexto é uma decisão difícil de
            desfazer.</p>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Permissão</th>
                        <th>Quando é pedida</th>
                        <th>Para quê</th>
                        <th>Se você recusar</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Localização</strong><br>(apenas em uso, aproximada)</td>
                        <td>Só ao tocar em <em>"Perto de mim"</em>, na tela de Serviços — nunca na
                            abertura do app</td>
                        <td>Ordenar academias, studios, personais e lojas por distância. A posição é
                            usada dentro da consulta e <strong>não é guardada</strong></td>
                        <td>A busca por <strong>nome, cidade ou bairro</strong> continua funcionando
                            igual; só não há ordenação por distância</td>
                    </tr>
                    <tr>
                        <td><strong>Câmera</strong></td>
                        <td>Só quando você escolhe <em>tirar</em> uma foto</td>
                        <td>Foto de perfil, de progresso do treino ou de produto da loja</td>
                        <td>Você pode escolher uma imagem já existente, ou ficar sem foto</td>
                    </tr>
                    <tr>
                        <td><strong>Fotos / galeria</strong></td>
                        <td>Só quando você escolhe uma imagem existente</td>
                        <td>Enviar a imagem que você selecionou. Acessamos
                            <strong>apenas a imagem escolhida</strong>, não a biblioteca</td>
                        <td>Você pode tirar a foto na hora, ou ficar sem foto</td>
                    </tr>
                    <tr>
                        <td><strong>Notificações</strong></td>
                        <td>Depois do login, com a explicação antes</td>
                        <td>Avisos da sua conta: mensagem no chat, pagamento confirmado, aula marcada
                            ou cancelada, ficha pronta, pedido na loja, lembrete da próxima aula.
                            <strong>Nenhum é promocional</strong></td>
                        <td>Nada deixa de funcionar; os mesmos avisos ficam em <em>Avisos</em>,
                            dentro do app. Pode desativar depois nos Ajustes do aparelho</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="box">
            O app <strong>não usa</strong> microfone, contatos, agenda, SMS, saúde do sistema
            (Apple Health / Google Fit), arquivos do aparelho nem localização em segundo plano — e
            não declara nenhuma dessas permissões. Também
            <strong>não submete seus dados a decisões automatizadas</strong> que
            afetem seus interesses (art. 20 da LGPD): não há perfilamento automático que negue
            serviço, defina preço individual ou classifique você. A ordenação da vitrine usa apenas
            critérios declarados (proximidade, avaliação, filtros que você escolhe). Se alguma função
            nova mudar isso, esta Política será atualizada antes.
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-user-circle-minus"></i> 11. Idade mínima — a Plataforma é para maiores de idade</h2>
        <div class="box">
            O uso como <strong>aluno</strong> exige idade mínima de <strong>{{ $idadeAluno }} anos</strong>;
            como <strong>personal</strong>, <strong>{{ $idadeProf }} anos</strong>. O cadastro valida a
            data de nascimento e recusa quem não atende ao limite.
        </div>
        <p>Por isso, <strong>não tratamos intencionalmente dados de crianças e adolescentes</strong> e
            não há, na Plataforma, coleta de dado de saúde de menor. Caso identifiquemos conta de
            menor de idade — por informação falsa no cadastro, por exemplo —
            <strong>encerraremos a conta e eliminaremos os dados</strong>, preservados apenas os que a
            lei obrigue a guardar. Se você é responsável legal e identificar o cadastro de um menor,
            escreva para <a href="mailto:{{ $emp('dpo_email') }}">{{ $emp('dpo_email') }}</a> e
            atuaremos prioritariamente.</p>
        <p class="lei">Referência: art. 14 da LGPD. A exigência de maioridade afasta a necessidade de
            consentimento de responsável, que seria obrigatório caso a Plataforma admitisse menores.</p>

        {{-- ───────────────────────────────────────────────────────── --}}

        <h2><i class="ph ph-arrows-clockwise"></i> 12. Alterações desta Política</h2>
        <p>Esta Política pode ser atualizada. Toda versão tem <strong>número e data de vigência</strong>.
            Mudanças relevantes — nova finalidade, nova base legal, novo compartilhamento ou mudança de
            retenção — são comunicadas pelos canais da Plataforma <strong>antes</strong> de produzirem
            efeito, e, quando a mudança exigir, pediremos <strong>novo consentimento</strong>. Nenhuma
            alteração retroage para legitimar tratamento feito sem base legal.</p>

        <h2><i class="ph ph-envelope"></i> 13. Contato</h2>
        <ul>
            <li><strong>Encarregado (DPO):</strong> {{ $emp('dpo_nome') }} —
                <a href="mailto:{{ $emp('dpo_email') }}">{{ $emp('dpo_email') }}</a></li>
            <li><strong>Atendimento geral:</strong>
                <a href="mailto:{{ $emp('email_suporte') }}">{{ $emp('email_suporte') }}</a></li>
            <li><strong>Controlador:</strong> {{ $emp('razao_social') }} — CNPJ {{ $emp('cnpj') }}</li>
        </ul>

        {{--
            Rever a escolha de cookies de dentro do documento que a explica.
            A seção 9 mandava "limpar os cookies do navegador" — procedimento do
            navegador, não da Plataforma, que deixava o titular sem caminho para
            revogar pelo mesmo meio em que consentiu. O botão só aparece quando há
            banner configurado (`banner-cookies` não define nada sem Pixel).
        --}}
        <p style="margin-top:30px;">
            <button type="button" onclick="window.snrfitCookies && window.snrfitCookies.abrir()"
                style="background:transparent;border:1px solid var(--border);color:var(--muted);
                       border-radius:999px;padding:8px 16px;font-size:0.82rem;cursor:pointer;font-family:inherit;">
                <i class="ph ph-cookie"></i> Preferências de cookies
            </button>
        </p>

        <p class="muted" style="margin-top:14px;">Versão {{ config('termos.versao') }} · Em vigor desde
            {{ $vigencia }}. Documento complementar aos
            <a href="{{ route('termos') }}">Termos de Uso</a>.</p>
    </div>

    {{-- Só a gestão da ESCOLHA de cookies: este partial nunca carrega o Pixel,
         e é por isso que pode entrar na própria página de privacidade. --}}
    @include('partials.banner-cookies')
</body>

</html>
