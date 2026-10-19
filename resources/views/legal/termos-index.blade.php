@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso')
@section('doc_subtitulo', 'Condições gerais da plataforma SNR FIT')
@section('doc_versao', config('termos.versao'))
@section('nav_index', 'active')

@section('doc_conteudo')

    @php
        /*
         * Todo número com efeito jurídico é LIDO da regra que o sistema aplica,
         * nunca escrito no texto. A razão é concreta: a Política de Privacidade
         * afirmou por meses que a idade mínima do aluno era 14 anos enquanto o
         * cadastro recusava qualquer pessoa com menos de 18 — e ninguém viu,
         * porque nada comparava o documento com o código. Um documento legal que
         * descreve errado o próprio sistema é pior que um documento omisso: ele
         * vira prova contra quem o escreveu.
         *
         * Ver tests/Feature/DocumentosLegaisTest.php, que trava essa igualdade.
         */
        $pct = fn ($f) => rtrim(rtrim(number_format($f * 100, 2, ',', '.'), '0'), ',') . '%';
        $reais = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');

        $taxaPlat = \App\Services\AsaasService::feeRate();
        $taxaLabel = $pct($taxaPlat);
        $minimo = \App\Services\AsaasService::valorMinimo();

        $idadeAluno = \App\Support\CadastroHelper::IDADE_MINIMA_ALUNO;
        $idadeProf = \App\Support\CadastroHelper::IDADE_MINIMA_PROFISSIONAL;

        $pctIndic = (float) config('indicacao.percentual', 0.10);
        $pctIndicLabel = $pct($pctIndic);
        $janelaDias = (int) config('indicacao.janela_dias', 35);
        $metaAlunos = (int) config('indicacao.meta_alunos', 6);
        $saqueMinimo = (float) config('indicacao.saque_minimo', 20.00);

        // Exemplo do bônus, calculado — nunca cravado.
        $exBase = (float) config('indicacao.exemplo_base', 2500.00);
        $exComissao = round($exBase * $taxaPlat, 2);
        $exBonus = round($exComissao * $pctIndic, 2);

        // Exemplo do preço, com a conta real do serviço.
        $exPreco = 100.00;
        $exContaPix = \App\Services\AsaasService::detalhar($exPreco, 'PIX');
        $exContaCartao = \App\Services\AsaasService::detalhar($exPreco, 'CREDIT_CARD');

        $emp = fn ($c) => \App\Support\Empresa::get($c);
    @endphp

    <div class="highlighted">
        <strong>Leia antes de usar.</strong> Este documento é um contrato. Ele descreve o que a SNR FIT
        faz, o que você pode e o que não pode fazer na Plataforma, como o dinheiro circula, de que a
        SNR FIT responde e de que <strong>não</strong> responde. Ao criar uma conta você declara ter
        lido e aceito estas condições, o documento específico do seu perfil e a
        <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#s0">0. Identificação do titular da Plataforma</a></li>
            <li><a href="#s1">1. Aceitação, documentos aplicáveis e definições</a>
                <ul><li><a href="#s1-4">1.4. Como os prazos são contados</a></li></ul>
            </li>
            <li><a href="#s2">2. O que a Plataforma é — e o que ela não é</a></li>
            <li><a href="#s3">3. Elegibilidade, cadastro e conta</a></li>
            <li><a href="#s4">4. O que você PODE fazer</a></li>
            <li><a href="#s5">5. O que você NÃO PODE fazer</a></li>
            <li><a href="#s6">6. Saúde, risco e limites da atividade física</a></li>
            <li><a href="#s7">7. Preço, taxa de intermediação e pagamentos</a></li>
            <li><a href="#s8">8. Cancelamento, arrependimento e reembolso</a></li>
            <li><a href="#s9">9. Programa de indicação ("Indique e ganhe")</a></li>
            <li><a href="#s10">10. Conteúdo de terceiros, avaliações e moderação</a></li>
            <li><a href="#s11">11. Propriedade intelectual, marca e imagem</a></li>
            <li><a href="#s12">12. O que a SNR FIT faz e pelo que ela RESPONDE</a>
                <ul><li><a href="#s12-1">12.1. Como exercer cada direito (procedimentos)</a></li></ul>
            </li>
            <li><a href="#s13">13. Pelo que a SNR FIT NÃO responde</a></li>
            <li><a href="#s14">14. Limitação de responsabilidade</a></li>
            <li><a href="#s15">15. Indenização (defesa e isenção)</a></li>
            <li><a href="#s16">16. Suspensão, encerramento e devido processo</a></li>
            <li><a href="#s17">17. Proteção de dados pessoais (LGPD)</a></li>
            <li><a href="#s18">18. Guarda de registros de acesso</a></li>
            <li><a href="#s19">19. Alterações destes Termos</a></li>
            <li><a href="#s20">20. Disposições gerais</a></li>
            <li><a href="#s21">21. Lei aplicável, foro e atendimento</a></li>
        </ul>
    </div>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s0">0. IDENTIFICAÇÃO DO TITULAR DA PLATAFORMA</h2>
    <p>A plataforma <strong>{{ $emp('marca') }}</strong> ("Plataforma", "SNR FIT", "nós") é
        disponibilizada, por meio de site e aplicativo, por:</p>
    <ul>
        <li><strong>Razão social:</strong> {{ $emp('razao_social') }}</li>
        <li><strong>CNPJ:</strong> {{ $emp('cnpj') }}</li>
        <li><strong>Atendimento (SAC):</strong> <a href="mailto:{{ $emp('email_suporte') }}">{{ $emp('email_suporte') }}</a></li>
        <li><strong>Encarregado pelo Tratamento de Dados Pessoais (LGPD, art. 41):</strong>
            {{ $emp('dpo_nome') }} — <a href="mailto:{{ $emp('dpo_email') }}">{{ $emp('dpo_email') }}</a></li>
    </ul>
    <p class="muted">Identificação exigida pelo art. 2º do Decreto nº 7.962/2013, que regulamenta o
        Código de Defesa do Consumidor para a contratação no comércio eletrônico.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s1">1. ACEITAÇÃO, DOCUMENTOS APLICÁVEIS E DEFINIÇÕES</h2>

    <h3>1.1. Aceitação e prova</h3>
    <p>O aceite é manifestado por meio eletrônico, no cadastro ou na tela de reaceite, e fica
        registrado com a <strong>versão aceita, a data e a hora, o endereço IP e o agente de usuário</strong>.
        Esse registro é a prova do consentimento contratual e é conservado de forma
        <strong>incremental</strong>: um novo aceite não apaga o anterior. A versão vigente deste
        documento é a <strong>{{ config('termos.versao') }}</strong>, em vigor desde
        {{ \Carbon\Carbon::parse(config('termos.vigente_desde'))->format('d/m/Y') }}.</p>

    <h3>1.2. Documentos que integram este contrato</h3>
    <p>Além destas condições gerais, integram e complementam este contrato, naquilo que for aplicável
        ao seu perfil:</p>
    <ul>
        <li><a href="{{ route('termos.aluno') }}"><strong>Termos do Aluno/Usuário</strong></a></li>
        <li><a href="{{ route('termos.personal') }}"><strong>Termos do Personal Trainer</strong></a></li>
        <li><a href="{{ route('termos.academia') }}"><strong>Termos da Academia</strong></a></li>
        <li><a href="{{ route('termos.studio') }}"><strong>Termos do Studio</strong></a></li>
        <li><a href="{{ route('termos.loja') }}"><strong>Termos da Loja</strong></a></li>
        <li><a href="{{ route('lgpd.politica') }}"><strong>Política de Privacidade</strong></a></li>
    </ul>
    <p>Havendo conflito entre estas condições gerais e o documento específico do perfil,
        <strong>prevalece o documento específico</strong>. Havendo conflito sobre tratamento de dados
        pessoais, prevalece a Política de Privacidade.</p>

    <h3>1.3. Definições</h3>
    <ul>
        <li><strong>Aluno</strong> (ou <strong>Usuário</strong>): pessoa natural que usa a Plataforma
            para encontrar e contratar serviços, acompanhar treinos e comprar planos ou produtos.</li>
        <li><strong>Personal:</strong> profissional de educação física que oferece serviços pela
            Plataforma.</li>
        <li><strong>Academia</strong> e <strong>Studio:</strong> estabelecimentos que oferecem planos,
            aulas e espaço físico e gerenciam alunos e profissionais.</li>
        <li><strong>Loja:</strong> estabelecimento que anuncia e vende produtos.</li>
        <li><strong>Profissional</strong> ou <strong>Fornecedor:</strong> referência conjunta a
            Personal, Academia, Studio e Loja.</li>
        <li><strong>Preço do Serviço:</strong> o valor que o Profissional define e recebe pelo que
            presta ou vende.</li>
        <li><strong>Taxa de Intermediação:</strong> a remuneração da Plataforma, acrescida ao Preço do
            Serviço e paga pelo Aluno (cláusula 7).</li>
        <li><strong>Valor Final:</strong> o total efetivamente cobrado do Aluno.</li>
        <li><strong>Conteúdo:</strong> qualquer informação, texto, imagem, avaliação, ficha, medida ou
            dado inserido na Plataforma por um usuário.</li>
        <li><strong>Processador de Pagamentos:</strong> {{ $emp('processador_pagamentos') }}.</li>
        <li><strong>Dia útil:</strong> dia que não seja sábado, domingo ou feriado nacional.</li>
        <li><strong>Dia corrido:</strong> todo e qualquer dia, inclusive sábados, domingos e
            feriados.</li>
    </ul>

    <h3 id="s1-4">1.4. Como os prazos deste contrato são contados</h3>
    <div class="highlighted">
        <p>Esta cláusula vale para <strong>todos</strong> os prazos deste contrato, dos documentos de
            perfil e da Política de Privacidade. Onde um prazo aparecer, ele terá o número e a
            natureza — <em>corridos</em>, <em>úteis</em> ou <em>horas</em> — e as regras abaixo
            completam o resto, para não ser preciso repeti-las em cada cláusula.</p>
        <ol style="margin-bottom:0;">
            <li><strong>O dia inicial não conta; o dia final conta</strong> (art. 132 do Código
                Civil). Um prazo de 7 dias corridos iniciado numa segunda-feira termina na
                segunda-feira seguinte.</li>
            <li><strong>Prazo em dias corridos</strong> corre sem interrupção. Se o
                <strong>último</strong> dia cair em sábado, domingo ou feriado nacional, ele se
                prorroga para o <strong>primeiro dia útil seguinte</strong> — mas apenas o último
                dia: o prazo não "pausa" no fim de semana do meio.</li>
            <li><strong>Prazo em dias úteis</strong> não conta sábados, domingos e feriados
                nacionais em nenhuma posição.</li>
            <li><strong>Prazo em horas</strong> conta hora a hora, inclusive em fim de semana, do
                momento do evento.</li>
            <li><strong>Termo inicial.</strong> Onde a cláusula não disser outra coisa, o prazo
                começa: para <em>você</em>, da <strong>contratação</strong>, do
                <strong>recebimento</strong> do produto ou do <strong>recebimento da nossa
                    comunicação</strong>, conforme o caso; para <em>nós</em>, do
                <strong>recebimento da sua solicitação</strong> pelo canal indicado na cláusula 12.1.
                Solicitação recebida depois das 18h, em fim de semana ou em feriado conta como
                recebida no <strong>primeiro dia útil seguinte</strong>.</li>
            <li><strong>Fuso horário.</strong> Todos os prazos seguem o horário oficial de Brasília.</li>
            <li><strong>Se nós não cumprirmos um prazo</strong> deste contrato, a obrigação
                <strong>não desaparece</strong>: ela continua exigível, você pode cobrá-la pelo
                atendimento, pelos órgãos de defesa do consumidor, pela
                <a href="https://www.consumidor.gov.br" target="_blank" rel="noopener">consumidor.gov.br</a>
                e pelo Judiciário, e o atraso não prejudica nenhum outro direito seu — inclusive o de
                reparação do que o atraso tiver causado. Nenhum prazo aqui é condição para você
                reclamar: são compromissos nossos, não requisitos seus.</li>
            <li><strong>Se você não cumprir um prazo</strong>, a consequência é só a prevista
                expressamente na cláusula correspondente. <strong>Não há</strong> perda de direito
                por atraso que este contrato não tenha dito, com uma única ressalva: os prazos de
                <strong>origem legal</strong> (os 7 dias do arrependimento e os prazos do art. 26 do
                Código de Defesa do Consumidor) são da lei e a Plataforma não pode ampliá-los ou
                reduzi-los por contrato.</li>
        </ol>
    </div>
    <p class="muted">Esta cláusula foi acrescentada na versão 3.1. Antes, vários prazos apareciam
        sem dizer se eram corridos ou úteis e sem termo inicial — o que, num contrato de adesão,
        se interpreta em favor do aderente (art. 47 do Código de Defesa do Consumidor e art. 423 do
        Código Civil). Dizer o que o sistema realmente faz é melhor para as duas partes do que
        deixar a dúvida.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s2">2. O QUE A PLATAFORMA É — E O QUE ELA NÃO É</h2>

    <h3>2.1. O que é</h3>
    <p>A SNR FIT é um <strong>provedor de aplicação de internet</strong> (art. 5º, VII, da Lei nº
        12.965/2014 — Marco Civil da Internet) que opera um <strong>ambiente de intermediação</strong>
        conectando Alunos a Profissionais, e que fornece ferramentas de montagem e acompanhamento de
        treino, agendamento, comunicação, avaliação de serviços e integração com meio de pagamento.</p>
    <p>O serviço que a SNR FIT presta, e pelo qual é remunerada, é a <strong>tecnologia e a
            intermediação</strong> — não a atividade física, não a gestão do estabelecimento e não a
        venda do produto.</p>

    <h3>2.2. O que a Plataforma NÃO é</h3>
    <div class="highlighted warn">
        <ul style="margin:0;">
            <li><strong>Não é prestadora de serviços de educação física.</strong> A Plataforma não
                prescreve treino, não acompanha execução e não emprega os profissionais. A atividade
                privativa do profissional de educação física (Lei nº 9.696/1998) é exercida
                exclusivamente pelo Personal, Academia ou Studio, sob sua inteira responsabilidade
                técnica.</li>
            <li><strong>Não é empregadora.</strong> Não há vínculo empregatício, societário, de
                parceria, de agência, de representação comercial ou de subordinação entre a Plataforma
                e Profissionais ou Alunos. Cada Profissional organiza livremente seu trabalho, preços,
                horários e clientela.</li>
            <li><strong>Não é instituição de pagamento.</strong> A Plataforma não é autorizada a
                operar arranjo de pagamento e não exerce atividade privativa de instituição de
                pagamento. A liquidação financeira é realizada integralmente pelo Processador de
                Pagamentos, instituição autorizada e regulada, nos termos da respectiva regulamentação
                do Banco Central do Brasil.</li>
            <li><strong>Não é vendedora dos produtos anunciados</strong> pelas Lojas, nem
                fornecedora dos planos das Academias e Studios.</li>
            <li><strong>Não é operadora de plano de saúde, seguradora, instituição financeira,
                    corretora, nem oferece investimento</strong> de qualquer natureza.</li>
        </ul>
    </div>
    <p>A Plataforma <strong>não é parte</strong> do contrato celebrado entre o Aluno e o Profissional.
        A relação de consumo quanto ao serviço de treino, ao plano ou ao produto se estabelece entre
        esses dois, e a eles cabe cumpri-la. A Plataforma é fornecedora apenas do serviço que ela
        própria presta, e por esse responde (cláusula 12).</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s3">3. ELEGIBILIDADE, CADASTRO E CONTA</h2>

    <h3>3.1. Idade mínima</h3>
    <div class="highlighted">
        <p style="margin:0;">A Plataforma é destinada a <strong>pessoas maiores de idade</strong>:</p>
        <ul style="margin:8px 0 0;">
            <li>Aluno: idade mínima de <strong>{{ $idadeAluno }} anos</strong>.</li>
            <li>Personal: idade mínima de <strong>{{ $idadeProf }} anos</strong>, além da habilitação
                profissional.</li>
        </ul>
    </div>
    <p>A Plataforma <strong>não se destina a crianças e adolescentes</strong> e não coleta
        intencionalmente dados de menores de {{ $idadeAluno }} anos. O cadastro recusa a data de
        nascimento que não atenda ao limite acima, e identificado o uso por menor de idade a conta é
        encerrada e os dados eliminados, salvo os que a lei obrigue a conservar. Academia, Studio e
        Loja são pessoas jurídicas e, por isso, não se sujeitam a limite de idade — mas o seu
        representante deve ser maior e capaz.</p>

    <h3>3.2. Veracidade e atualização</h3>
    <ul>
        <li>O cadastro exige informações <strong>verdadeiras, exatas e atuais</strong>, e você é
            responsável por mantê-las assim.</li>
        <li>Informação falsa, inexata ou desatualizada é fundamento suficiente para recusa, suspensão
            ou encerramento da conta, e pode caracterizar os ilícitos civis e penais cabíveis.</li>
        <li>Podemos exigir verificação de identidade, comprovação de habilitação profissional ou de
            regularidade cadastral, inclusive como condição para liberar recebimentos.</li>
    </ul>

    <h3>3.3. Aprovação prévia</h3>
    <p>O cadastro de Personal, Academia, Studio e Loja está sujeito a <strong>análise e aprovação</strong>
        da Plataforma antes da liberação do acesso. A aprovação é uma conferência cadastral de
        plausibilidade e <strong>não constitui auditoria, certificação, endosso, recomendação ou
            garantia</strong> de habilitação, qualidade, idoneidade ou resultado.</p>
    <p>No caso do <strong>Personal</strong>, essa conferência inclui duas verificações sobre o
        registro profissional, descritas na
        <a href="{{ route('termos.personal') }}#p2">cláusula 2.1 dos Termos do Personal</a>: a
        <strong>validação automática do formato</strong> do CREF no momento do cadastro, que impede o
        prosseguimento de registro malformado, e a <strong>consulta do registro na página pública do
            Conselho Regional</strong> antes da liberação do acesso. Essas verificações retratam o que
        o Conselho informava <strong>na data da aprovação</strong> e
        <strong>não constituem monitoramento contínuo nem garantia</strong> de habilitação perante
        terceiros — a fiscalização da atividade profissional compete ao órgão de classe
        (cláusula 13).</p>

    <h3>3.4. Conta, senha e responsabilidade pelo acesso</h3>
    <ul>
        <li>A conta é <strong>pessoal e intransferível</strong>. É vedado cedê-la, vendê-la,
            compartilhá-la ou permitir seu uso por terceiro.</li>
        <li>Você responde por toda atividade praticada com as suas credenciais, salvo se comprovar
            falha de segurança atribuível à Plataforma.</li>
        <li>Comunique-nos imediatamente qualquer uso não autorizado, perda de credencial ou suspeita
            de incidente.</li>
        <li>As senhas são armazenadas com função de resumo criptográfico (hash) e <strong>não são
                conhecidas pela Plataforma</strong>; nenhum funcionário ou prestador pode informá-las a
            você, e qualquer contato pedindo sua senha deve ser tratado como fraude.</li>
    </ul>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s4">4. O QUE VOCÊ PODE FAZER</h2>
    <p>Concedemos a você uma licença <strong>limitada, pessoal, não exclusiva, não sublicenciável,
            revogável e não transferível</strong> para usar a Plataforma conforme este contrato. Dentro
        dela, você pode:</p>
    <ul>
        <li><strong>Buscar e comparar</strong> Profissionais por nome, especialidade, modalidade,
            avaliação e — se autorizar a localização — por proximidade.</li>
        <li><strong>Contratar</strong> serviços, planos e produtos, agendar sessões e aulas e pagar
            pela Plataforma, com o Valor Final informado antes da confirmação.</li>
        <li><strong>Registrar e acompanhar</strong> treinos, metas, medidas, fotos de progresso e
            avaliações físicas, e exportar os seus dados.</li>
        <li><strong>Comunicar-se</strong> com a outra parte pelo chat, para o que diga respeito ao
            serviço contratado.</li>
        <li><strong>Avaliar</strong> serviços que você efetivamente contratou e recebeu.</li>
        <li><strong>Participar</strong> do Programa de Indicação, nas condições da cláusula 9.</li>
        <li><strong>Divulgar</strong> o seu próprio perfil e o seu código de indicação pelos seus
            canais, de forma honesta e sem se apresentar como representante da SNR FIT.</li>
        <li><strong>Encerrar</strong> a sua conta e solicitar a eliminação dos seus dados, a qualquer
            momento, pelos canais da Plataforma.</li>
    </ul>
    <p>Profissionais podem, ainda, cadastrar sua oferta, definir livremente preços e políticas de
        cancelamento, gerenciar agenda e alunos e usar as ferramentas de gestão do seu perfil,
        observados os deveres do documento específico.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s5">5. O QUE VOCÊ NÃO PODE FAZER</h2>
    <p>É vedado, a qualquer usuário:</p>

    <h3>5.1. Quanto à identidade e à informação</h3>
    <ul>
        <li>Fornecer dado falso, usar documento de terceiro, falsificar identidade ou se passar por
            outra pessoa, profissional, empresa ou pela própria SNR FIT;</li>
        <li>Declarar qualificação, titulação ou registro profissional que não possua;</li>
        <li>Criar mais de uma conta para o mesmo titular com o fim de obter vantagem, burlar
            limitação, bloqueio ou condição do Programa de Indicação.</li>
    </ul>

    <h3>5.2. Quanto às outras pessoas</h3>
    <ul>
        <li>Assediar, ameaçar, constranger, coagir ou praticar violência, inclusive verbal;</li>
        <li>Discriminar por raça, cor, etnia, origem, religião, deficiência, idade, sexo, identidade
            de gênero, orientação sexual, condição de saúde ou corporal;</li>
        <li>Divulgar dado pessoal, imagem, conversa ou informação de saúde de outra pessoa sem
            autorização;</li>
        <li>Usar o chat ou qualquer canal para assédio, insistência indesejada, propaganda não
            solicitada ou aproximação de natureza sexual.</li>
    </ul>

    <h3>5.3. Quanto à Plataforma e à sua segurança</h3>
    <ul>
        <li>Burlar, desativar ou testar mecanismos de segurança, autenticação, limite de uso ou
            cobrança;</li>
        <li>Acessar área, conta, dado ou API sem autorização; explorar vulnerabilidade sem comunicá-la
            previamente ao nosso canal de segurança;</li>
        <li>Fazer engenharia reversa, descompilar, copiar ou criar obra derivada do software, salvo nos
            limites inafastáveis da lei;</li>
        <li>Coletar dados de forma automatizada (<em>scraping</em>, robôs, crawlers), sobrecarregar a
            infraestrutura ou praticar qualquer forma de negação de serviço;</li>
        <li>Inserir código malicioso, praticar <em>phishing</em>, enviar <em>spam</em> ou veicular
            publicidade de terceiro dentro da Plataforma sem autorização escrita.</li>
    </ul>

    <h3>5.4. Quanto ao dinheiro e à intermediação</h3>
    <ul>
        <li><strong>Contornar a intermediação</strong> — ou seja, deslocar para fora da Plataforma o
            pagamento de serviço cujo contato ou negociação se deu por meio dela, com o efeito de não
            recolher a Taxa de Intermediação devida (cláusula 7.7);</li>
        <li>Simular transação, pagamento, aluno ou cadastro, inclusive para atingir condição do
            Programa de Indicação;</li>
        <li>Usar a Plataforma para lavagem de dinheiro, ocultação de origem de recursos, fraude,
            estelionato ou qualquer finalidade ilícita;</li>
        <li>Promover <em>chargeback</em> indevido, isto é, contestar junto ao emissor do cartão uma
            cobrança legítima e efetivamente usufruída.</li>
    </ul>

    <h3>5.5. Quanto ao conteúdo</h3>
    <ul>
        <li>Publicar conteúdo ilícito, violento, discriminatório, sexualmente explícito, que exponha
            menores, que viole direito autoral, marca ou imagem de terceiro;</li>
        <li>Publicar avaliação falsa, de serviço não contratado, em troca de vantagem, ou com o fim de
            extorquir, chantagear ou prejudicar concorrente;</li>
        <li>Prometer resultado estético ou de saúde, divulgar método sem evidência, ou anunciar
            serviço privativo de outra profissão regulamentada — notadamente prescrição de
            medicamento, diagnóstico, tratamento de doença e prescrição dietética.</li>
    </ul>

    <div class="highlighted warn">
        <strong>Consequências.</strong> A violação desta cláusula autoriza a Plataforma a, conforme a
        gravidade e de forma isolada ou cumulativa: remover o conteúdo, advertir, restringir
        funcionalidades, retrair o perfil da vitrine, suspender ou encerrar a conta, retomar valores
        indevidamente obtidos, comunicar às autoridades e buscar a reparação integral dos danos
        (arts. 186 e 927 do Código Civil). As medidas observam a cláusula 16.
    </div>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s6">6. SAÚDE, RISCO E LIMITES DA ATIVIDADE FÍSICA</h2>
    <ul>
        <li>A prática de atividade física envolve <strong>risco inerente</strong>, inclusive de lesão.
            Recomendamos <strong>avaliação médica prévia</strong> e, para quem tem condição crônica,
            liberação médica expressa.</li>
        <li>O Aluno deve informar ao Profissional, com veracidade, condições de saúde, lesões,
            cirurgias, gestação, uso de medicamentos e restrições. <strong>A omissão dessas
                informações afasta a responsabilidade da Plataforma e do Profissional</strong> pelas
            consequências dela decorrentes.</li>
        <li>O Profissional deve analisar a anamnese antes de prescrever, adequar carga e volume, e
            encaminhar ao médico diante de qualquer sinal de risco.</li>
        <li>Nenhum Profissional da Plataforma presta orientação médica, diagnóstico, tratamento,
            prescrição de medicamento ou prescrição dietética. Questões clínicas devem ser levadas a
            médico e questões nutricionais a nutricionista.</li>
        <li>Conteúdos informativos exibidos na Plataforma (textos, vídeos de exercício, materiais de
            apoio) têm caráter <strong>educativo e geral</strong> e não substituem avaliação individual
            por profissional habilitado.</li>
    </ul>
    <div class="highlighted warn">
        <strong>Isenção quanto à execução.</strong> A Plataforma não conduz, não supervisiona e não
        fiscaliza a execução dos treinos, nem as condições do espaço físico onde ocorrem. Lesões,
        danos e acidentes decorrentes da execução do serviço são de responsabilidade do Profissional
        ou estabelecimento que o conduz e, no que couber, do próprio praticante.
    </div>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s7">7. PREÇO, TAXA DE INTERMEDIAÇÃO E PAGAMENTOS</h2>

    <h3>7.1. Quem define o preço</h3>
    <p>O <strong>Preço do Serviço é definido livremente pelo Profissional</strong>, assim como seus
        planos, promoções e política de cancelamento. A Plataforma não interfere nesse preço, não
        sugere valor mínimo ou máximo e não pratica tabelamento.</p>

    <h3>7.2. A Taxa de Intermediação é ACRÉSCIMO, não desconto</h3>
    <div class="highlighted">
        <p><strong>A Taxa de Intermediação da Plataforma é somada ao Preço do Serviço e cobrada do
                Aluno.</strong> Ela <strong>não é descontada</strong> do Profissional: o valor que ele
            definiu é repassado a ele <strong>integralmente</strong>.</p>
        <p style="margin-bottom:0;">Exemplo com a taxa de <strong>{{ $taxaLabel }}</strong> vigente
            nesta data, para um serviço de {{ $reais($exContaPix['base']) }} pago por PIX:</p>
        <ul style="margin-top:6px;">
            <li>Preço do Serviço (vai integralmente ao Profissional): <strong>{{ $reais($exContaPix['base']) }}</strong></li>
            <li>Taxa de Intermediação ({{ $taxaLabel }}): <strong>{{ $reais($exContaPix['comissao']) }}</strong></li>
            <li><strong>Valor Final pago pelo Aluno: {{ $reais($exContaPix['total']) }}</strong></li>
        </ul>
    </div>

    <h3>7.3. Transparência do Valor Final</h3>
    <p>O <strong>Valor Final</strong> é exibido ao Aluno <strong>antes da contratação</strong>, nas
        vitrines, nas páginas de detalhe e na tela de pagamento, com a <strong>composição
            discriminada</strong> (Preço do Serviço, Taxa de Intermediação e, quando houver, tarifa do
        meio de pagamento). O cumprimento desta cláusula atende aos arts. 6º, III, e 31 do Código de
        Defesa do Consumidor e ao art. 2º, V, do Decreto nº 7.962/2013, que exigem informação clara
        sobre o preço total, com as despesas acessórias discriminadas.</p>

    <h3>7.4. Tarifas do meio de pagamento</h3>
    <ul>
        <li>No <strong>PIX</strong>, a tarifa cobrada pelo Processador de Pagamentos é
            <strong>absorvida pela Plataforma</strong>, saindo da sua Taxa de Intermediação.</li>
        <li>No <strong>cartão</strong>, cujo custo de processamento é significativamente maior, a
            tarifa é <strong>acrescida ao Valor Final e paga pelo Aluno</strong>, que é quem escolhe
            esse meio. Nesse caso o Valor Final é calculado de modo a que, após a retenção da tarifa
            pelo Processador, remanesçam o Preço do Serviço integral e a Taxa de Intermediação.</li>
        <li>Dessa forma, <strong>o valor recebido pelo Profissional não varia</strong> conforme o meio
            de pagamento escolhido pelo Aluno. No exemplo da cláusula 7.2, no cartão o Valor Final
            seria {{ $reais($exContaCartao['total']) }}, dos quais
            {{ $reais($exContaCartao['gateway']) }} correspondem à tarifa do meio de pagamento.</li>
        <li>As tarifas praticadas são as do Processador de Pagamentos e podem ser alteradas por ele; a
            Plataforma as repassa conforme a configuração vigente, sempre discriminadas antes da
            confirmação.</li>
    </ul>

    <h3>7.5. Valor mínimo de cobrança</h3>
    <p>Por limitação técnica do repasse integral — abaixo de certo valor a Taxa de Intermediação não
        cobre a tarifa do meio de pagamento e o Processador rejeita a divisão —, há um
        <strong>valor mínimo de {{ $reais($minimo) }}</strong> por cobrança. Serviço precificado
        abaixo desse limite não pode ser cobrado pela Plataforma, e o sistema informa o Profissional.</p>

    <h3>7.6. Liquidação, repasse e emissão de documento fiscal</h3>
    <ul>
        <li>As cobranças são criadas junto ao <strong>Processador de Pagamentos</strong>, com
            <strong>divisão automática (split)</strong> configurada na origem: na liquidação, o Preço
            do Serviço é creditado na subconta do Profissional e a Taxa de Intermediação na conta da
            Plataforma.</li>
        <li>Para receber, o Profissional deve concluir o cadastro e a verificação junto ao Processador.
            <strong>Prazos de liquidação, antecipação, retenções e eventuais bloqueios seguem as
                regras do Processador</strong>, não da Plataforma.</li>
        <li>Enquanto o Profissional não tiver subconta habilitada, o valor é recebido na conta da
            Plataforma e repassado <strong>mediante solicitação de saque</strong> no painel do seu
            perfil, nos termos do documento específico. A Plataforma paga em até
            <strong>5 (cinco) dias úteis</strong> contados da solicitação regular — entende-se por
            regular a que tenha chave PIX válida de titularidade do próprio Profissional e cadastro
            sem pendência de verificação. Esse valor <strong>não se confunde com receita da
                Plataforma</strong> e é devido ao Profissional independentemente de qualquer outra
            pendência que não a prevista neste contrato.</li>
        <li><strong>Cada parte emite os documentos fiscais da sua própria operação:</strong> o
            Profissional, pelo serviço ou produto que presta ou vende ao Aluno; a Plataforma, pela
            Taxa de Intermediação. A Plataforma <strong>não</strong> emite documento fiscal pelo
            serviço do Profissional e não é substituta tributária dele.</li>
        <li>Cada parte é responsável pelos tributos incidentes sobre a sua própria receita e pelas
            respectivas obrigações acessórias.</li>
    </ul>

    <h3>7.7. Vedação a contornar a intermediação</h3>
    <div class="highlighted warn">
        <p style="margin:0;">Quando o contato ou a negociação ocorreu por meio da Plataforma, é vedado
            deslocar o pagamento para fora dela com o efeito de não recolher a Taxa de Intermediação.
            A prática caracteriza <strong>inadimplemento contratual</strong> e autoriza a Plataforma a
            suspender ou encerrar a conta e a <strong>cobrar as Taxas de Intermediação devidas</strong>
            pelas operações assim desviadas, apuradas pelos registros da Plataforma e pelos elementos
            de prova disponíveis, sem prejuízo da reparação de outros danos.</p>
        <p style="margin:8px 0 0;">Esta cláusula <strong>não impede</strong> o Profissional de atender
            clientela própria, conquistada fora da Plataforma, por qualquer meio e preço que
            escolher — a vedação alcança apenas o desvio de contato originado aqui.</p>
    </div>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s8">8. CANCELAMENTO, ARREPENDIMENTO E REEMBOLSO</h2>

    <h3>8.1. Direito de arrependimento (art. 49 do CDC)</h3>
    <div class="highlighted">
        <p style="margin:0;">Nas contratações feitas pela Plataforma — que ocorrem fora de
            estabelecimento comercial —, o Aluno pode <strong>desistir em até 7 (sete) dias
                corridos</strong>, contados da contratação ou do recebimento do produto, conforme o
            caso, <strong>sem necessidade de justificar o motivo e sem ônus</strong>.</p>
        <p style="margin:8px 0 0;"><strong>O reembolso é integral e abrange tudo o que o Aluno
                pagou</strong>, inclusive a Taxa de Intermediação e as tarifas do meio de pagamento
            repassadas. No exemplo da cláusula 7.2, quem pagou {{ $reais($exContaPix['total']) }}
            recebe {{ $reais($exContaPix['total']) }} de volta — a Plataforma devolve a sua própria
            taxa.</p>
    </div>
    <p>O prazo é de <strong>origem legal</strong> e conta-se na forma da
        <a href="#s1-4">cláusula 1.4</a>: o dia da contratação (ou do recebimento do produto) não
        entra, o último dia entra e, caindo em sábado, domingo ou feriado nacional, prorroga-se para
        o primeiro dia útil seguinte.</p>
    <p><strong>Como exercer.</strong> Por e-mail ao atendimento
        (<a href="mailto:{{ $emp('email_suporte') }}">{{ $emp('email_suporte') }}</a>) ou pelo chat
        com quem você contratou, informando o que foi contratado e a data. Não é exigida justificativa
        nem formulário. A Plataforma <strong>responde por escrito em até 5 (cinco) dias úteis</strong>
        do recebimento do pedido e <strong>ordena a devolução em até 10 (dez) dias corridos</strong>
        da confirmação; o crédito efetivo na sua conta ou no seu cartão depende do prazo do meio de
        pagamento, que é do Processador e não da Plataforma (cláusula 8.5). A comunicação à
        administradora do cartão, quando aplicável, é providenciada por nós, nos termos do art. 5º do
        Decreto nº 7.962/2013.</p>
    <p>Serviço <strong>já integralmente prestado</strong> a pedido do Aluno dentro do prazo (por
        exemplo, a sessão já realizada) pode ser descontado do valor a devolver, na proporção do
        efetivamente usufruído, vedada a retenção do todo.</p>
    <p class="muted">Descumprido o prazo acima, o direito <strong>não se perde nem se reduz</strong>:
        ver o item 7 da cláusula 1.4.</p>

    <h3>8.2. Cancelamento de planos e assinaturas</h3>
    <ul>
        <li>Planos recorrentes se renovam na periodicidade contratada <strong>até que o Aluno
                cancele</strong>.</li>
        <li>O cancelamento é feito <strong>pelo mesmo meio eletrônico da contratação</strong>, sem
            ligação, atendimento humano ou justificativa (art. 4º, V, do Decreto nº 7.962/2013):
            <strong>no app</strong>, em <em>Meus pagamentos → Minhas assinaturas → Cancelar
                assinatura</em>; <strong>no site</strong>, no painel do aluno, na própria
            assinatura. O efeito é <strong>imediato</strong>, executado pelo sistema no mesmo ato —
            não há prazo de análise nem fila.</li>
        <li>A Plataforma <strong>confirma o cancelamento por escrito</strong>, por e-mail e por
            aviso no app, e essa confirmação serve de <strong>comprovante</strong> — guarde-a. Se
            você cancelou e não recebeu a confirmação, trate como não cancelado e escreva ao
            atendimento: a falha é nossa.</li>
        <li>O cancelamento interrompe as <strong>cobranças futuras</strong>; o período
            <strong>já pago</strong> segue utilizável até o fim da vigência, salvo condição mais
            benéfica. Cancelar <strong>não é</strong> pedir reembolso — se você quer o dinheiro de
            volta, o caminho é o arrependimento (cláusula 8.1), dentro dos 7 dias corridos, ou a
            reclamação por vício (cláusula 8.4).</li>
    </ul>

    <h3>8.3. Política do Profissional e faltas</h3>
    <p>Cada Profissional pode ter <strong>política própria de cancelamento e reagendamento</strong>,
        com prazo mínimo de antecedência, obrigatoriamente informada antes da contratação. Essa
        política deve ser razoável e compatível com o Código de Defesa do Consumidor — cláusula que
        imponha perda total do valor por cancelamento com antecedência razoável é
        <strong>abusiva</strong> (art. 51 do CDC) e não será sustentada pela Plataforma. Falta sem
        aviso (<em>no-show</em>) pode não gerar reembolso, conforme a política informada.</p>

    <h3>8.4. Vício e defeito</h3>
    <p>Serviço mal prestado e produto com defeito seguem o Código de Defesa do Consumidor, inclusive
        os prazos de reclamação do art. 26 (30 dias para serviço e produto não duráveis; 90 dias para
        duráveis) e as alternativas do art. 20 (reexecução, restituição ou abatimento). A
        responsabilidade por vício e defeito é do Profissional que prestou ou vendeu; a Plataforma
        <strong>medeia</strong> a solução e pode, a seu critério, antecipar o reembolso ao Aluno e
        depois recompor-se junto ao Profissional.</p>

    <h3>8.5. Estorno, chargeback e recomposição</h3>
    <ul>
        <li>Reembolsos devidos retornam pelo <strong>meio de pagamento original</strong>. A
            Plataforma <strong>ordena</strong> a devolução em até <strong>10 (dez) dias
                corridos</strong> do reconhecimento do direito; o <strong>crédito efetivo</strong>
            obedece ao prazo do meio de pagamento — em geral poucos dias no PIX e
            <strong>até duas faturas</strong> no cartão de crédito, conforme o emissor. Esses prazos
            de crédito são do Processador e dos bancos, <strong>não da Plataforma</strong>, que não
            os controla (cláusula 13).</li>
        <li>A devolução <strong>não é automática no sistema</strong>, e isso é deliberado: o Preço do
            Serviço já pode ter sido creditado ao Profissional, e pedir a reversão ao Processador
            poderia ser recusada por saldo ou deixar a subconta dele negativa. Então todo reembolso
            devido entra numa <strong>fila conferida por pessoa</strong>, que executa a devolução e
            dá baixa. A fila é da Plataforma e o prazo acima é o nosso compromisso sobre ela.</li>
        <li>Quando o reembolso ocorre após o repasse, a Plataforma <strong>devolve a sua Taxa de
                Intermediação</strong> e <strong>recompõe-se do Preço do Serviço</strong> perante o
            Profissional, mediante compensação em repasses futuros ou cobrança direta, conforme o
            documento do perfil dele.</li>
        <li><em>Chargeback</em> reconhecido pelo emissor do cartão produz os mesmos efeitos.</li>
        <li><em>Chargeback</em> indevido — contestação de cobrança legítima e usufruída — autoriza a
            cobrança do valor e a suspensão da conta, sem prejuízo das medidas legais.</li>
    </ul>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s9">9. PROGRAMA DE INDICAÇÃO ("INDIQUE E GANHE")</h2>

    <h3>9.1. Natureza do Programa</h3>
    <p>O Programa de Indicação é <strong>campanha promocional concedida por mera liberalidade</strong>
        da Plataforma, de adesão facultativa e gratuita. Ele <strong>não integra a
            contraprestação</strong> de nenhum serviço, <strong>não altera</strong> qualquer valor
        cobrado de Alunos ou devido a Profissionais, e <strong>não constitui direito adquirido</strong>:
        pode ser alterado, suspenso ou encerrado a qualquer tempo, preservando-se os bônus já
        liberados até a data da alteração.</p>
    <div class="highlighted warn">
        <p style="margin:0;">O bônus <strong>não é salário, remuneração, honorário, comissão de
                representação comercial, participação em lucros ou resultados, nem contraprestação por
                trabalho</strong>. A participação <strong>não cria</strong> vínculo empregatício,
            societário, de parceria, de agência ou de representação comercial com a Plataforma (não se
            aplicando, por ausência de seus elementos, a Lei nº 4.886/1965).</p>
        <p style="margin:8px 0 0;">O Programa <strong>não é sorteio, concurso, vale-brinde nem
                distribuição gratuita de prêmios</strong> na acepção da Lei nº 5.768/1971 e do Decreto
            nº 70.951/1972: <strong>não há elemento de sorte ou álea</strong>. O bônus decorre
            exclusivamente de fato objetivo e verificável — a atividade econômica efetivamente
            realizada na Plataforma pela conta indicada —, razão pela qual o Programa não se sujeita a
            autorização prévia para essa modalidade.</p>
        <p style="margin:8px 0 0;">O Programa <strong>não é oferta de investimento, valor mobiliário,
                consórcio ou captação de poupança popular</strong>, e nenhuma comunicação sobre ele deve
            ser interpretada como promessa de rendimento.</p>
    </div>

    <h3>9.2. Como funciona</h3>
    <ul>
        <li>Cada conta recebe um <strong>código de indicação próprio</strong>, pessoal e
            intransferível.</li>
        <li>Quem se cadastra pode informar o código de quem o indicou <strong>exclusivamente no
                momento do próprio cadastro</strong>. Não há inclusão, troca ou retroação depois.</li>
        <li>Cada conta pode ser indicada <strong>uma única vez</strong>, de forma definitiva.</li>
        <li>O bônus corresponde a <strong>{{ $pctIndicLabel }} da Taxa de Intermediação auferida pela
                Plataforma</strong> nas transações da conta indicada durante a <strong>janela de
                apuração de {{ $janelaDias }} dias corridos</strong>, contada da <strong>aprovação do
                cadastro da conta indicada</strong> — não da indicação, não do cadastro e não do
                primeiro pagamento. A janela corre sem interrupção, inclusive em fins de semana e
                feriados, e <strong>não se prorroga</strong> em nenhuma hipótese: aqui o último dia
                não segue a regra de prorrogação da cláusula 1.4, porque não é prazo para alguém
                agir, é o período de apuração em si.</li>
        <li>O percentual e o prazo aplicáveis são os <strong>vigentes na data da aprovação da conta
                indicada</strong>. Alterações posteriores valem apenas para indicações futuras e
            <strong>não retroagem</strong>.</li>
        <li>Encerrada a janela, o valor apurado é <strong>definitivo</strong>: receita gerada depois
            não acresce o bônus.</li>
    </ul>

    <h3>9.3. Base de cálculo</h3>
    <div class="highlighted">
        <p><strong>O percentual incide exclusivamente sobre a Taxa de Intermediação</strong> — isto é,
            sobre a remuneração que a Plataforma efetivamente auferiu em cada pagamento confirmado da
            conta indicada, realizado por meio da Plataforma dentro da janela.</p>
        <p>Para afastar dúvida, a base de cálculo <strong>não é</strong>: (i) o Valor Final pago pelo
            Aluno; (ii) o faturamento da conta indicada; (iii) o Preço do Serviço repassado à conta
            indicada, do qual <strong>nada</strong> é deduzido para custear o bônus; (iv) qualquer
            receita obtida pela conta indicada <strong>fora</strong> da Plataforma, que em nenhuma
            hipótese compõe a base; (v) as tarifas do meio de pagamento, que não são receita da
            Plataforma.</p>
        <p>Por decorrência, o bônus é <strong>estruturalmente limitado à Taxa de Intermediação
                efetivamente auferida</strong> e não pode, em nenhuma hipótese, excedê-la.</p>
        <p style="margin-bottom:0;"><strong>Exemplo meramente ilustrativo</strong>, com o percentual de
            {{ $pctIndicLabel }} e a taxa de {{ $taxaLabel }} vigentes nesta data: se a conta indicada
            prestar, pela Plataforma e dentro da janela, {{ $reais($exBase) }} em serviços com
            pagamento confirmado, ela receberá esses {{ $reais($exBase) }} integralmente, os Alunos
            terão pago {{ $reais($exBase + $exComissao) }}, a Taxa de Intermediação da Plataforma será
            de {{ $reais($exComissao) }} e o bônus de quem indicou será de
            <strong>{{ $reais($exBonus) }}</strong>. O exemplo <strong>não</strong> constitui promessa,
            projeção, estimativa ou garantia de ganho (cláusula 9.8).</p>
    </div>
    <ul>
        <li>A apuração é feita <strong>exclusivamente com base nos registros da Plataforma</strong>,
            que prevalecem em caso de divergência, ressalvada prova inequívoca em sentido contrário.</li>
        <li>Compõem a base apenas os pagamentos <strong>efetivamente confirmados</strong> na janela.
            <strong>Não</strong> compõem: valores pendentes, recusados, cancelados, estornados, em
            disputa, nem vínculos ou cadastros criados manualmente sem pagamento correspondente.</li>
        <li>A Plataforma poderá <strong>recalcular, retificar ou cancelar</strong> bônus a qualquer
            tempo em caso de estorno, <em>chargeback</em>, cancelamento, reembolso, erro operacional,
            falha sistêmica ou fraude, ainda que posteriores à liberação.</li>
        <li>Nessas hipóteses poderá <strong>compensar</strong> o valor em bônus futuros ou em outros
            créditos do participante e, na insuficiência, <strong>exigir a devolução</strong>, que
            constitui restituição de <strong>enriquecimento sem causa</strong> (art. 884 do Código
            Civil).</li>
    </ul>

    <h3>9.4. Condições cumulativas para o saque</h3>
    <div class="highlighted">
        O bônus somente se torna <strong>exigível e disponível para saque</strong> quando as duas
        condições abaixo forem atendidas <strong>cumulativamente</strong>:
        <ol>
            <li>o <strong>encerramento integral</strong> da janela de apuração de {{ $janelaDias }}
                dias corridos; <strong>e</strong></li>
            <li>o atingimento, pela conta indicada, de <strong>{{ $metaAlunos }} alunos ou clientes
                    distintos com pagamento confirmado</strong> na Plataforma.</li>
        </ol>
    </div>
    <p>Enquanto ambas não forem atendidas, o valor exibido no painel é <strong>mera expectativa de
            direito</strong>, de caráter informativo, e <strong>não</strong> crédito líquido, certo e
        exigível. Atendidas as condições, o bônus liberado <strong>não é revertido</strong> por perda
        posterior de alunos pela conta indicada, ressalvadas as hipóteses das cláusulas 9.3 e 9.6.</p>

    <h3>9.5. Indicação de Aluno não gera bônus</h3>
    <p>A indicação de contas de <strong>Aluno</strong> é registrada apenas como histórico e
        <strong>não gera bônus</strong>, por não haver, nesse perfil, receita intermediada nem
        conquista de clientela. O Programa contempla exclusivamente a indicação de
        <strong>Profissionais e estabelecimentos</strong> que venham a transacionar na Plataforma.</p>

    <h3>9.6. Condutas vedadas e fraude no Programa</h3>
    <p>É expressamente vedado, entre outras condutas de má-fé:</p>
    <ul>
        <li><strong>Auto-indicação</strong>, direta ou indireta, inclusive por segundo cadastro,
            e-mail distinto, conta de mesma titularidade ou pessoa interposta;</li>
        <li>criar contas <strong>falsas</strong> ou de terceiros sem autorização;</li>
        <li><strong>simular</strong> transações, pagamentos ou alunos para atingir as condições da
            cláusula 9.4;</li>
        <li>organizar <strong>esquemas coordenados</strong>, circulares ou em cadeia;</li>
        <li>divulgar o código por <strong>spam</strong>, mensagem não solicitada em massa, anúncio pago
            sobre a marca SNR FIT ou qualquer <strong>publicidade enganosa</strong>, inclusive promessa
            de ganho a terceiros;</li>
        <li>apresentar-se como representante, preposto, franqueado ou parceiro comercial da SNR FIT,
            ou usar sua marca e identidade visual sem autorização escrita.</li>
    </ul>
    <div class="highlighted warn">
        Verificada qualquer dessas condutas, ou indício fundado dela, a Plataforma poderá, de forma
        isolada ou cumulativa: <strong>retê-lo</strong> durante a apuração, <strong>cancelar o
            bônus</strong> (inclusive já liberado), <strong>estornar</strong> valores pagos,
        <strong>invalidar</strong> as indicações envolvidas, <strong>suspender ou encerrar</strong> as
        contas relacionadas e adotar as <strong>medidas legais</strong> cabíveis, inclusive cobrança de
        perdas e danos. Em caso de retenção durante apuração, o participante será informado e poderá
        apresentar esclarecimentos (cláusula 16.2).
    </div>

    <h3>9.7. Saque, pagamento e tributos</h3>
    <ul>
        <li>O saque é solicitado pelo participante <strong>no painel do Programa, no site</strong>
            (<em>Indique e ganhe</em> → informar a chave PIX → Solicitar), observado o
            <strong>valor mínimo de {{ $reais($saqueMinimo) }}</strong>. O pedido de saque
            <strong>não existe no aplicativo</strong>, e isso é deliberado: ele move dinheiro real
            para fora da conta da Plataforma e as travas construídas para esse fluxo vivem no site —
            o app mostra o painel e o saldo, mas não solicita.</li>
        <li>É permitida <strong>uma solicitação em aberto por vez</strong> por conta.</li>
        <li>O valor é <strong>calculado pela Plataforma</strong> a partir dos bônus liberados; não é
            possível solicitar valor diferente do apurado.</li>
        <li>O pagamento é feito por <strong>PIX</strong>, na chave informada pelo participante, que é o
            <strong>único responsável</strong> pela exatidão e titularidade dessa chave.</li>
        <li>Solicitações até o limite publicado no painel podem ser processadas
            <strong>automaticamente</strong>, em minutos; as demais ficam sujeitas a
            <strong>conferência prévia</strong> por pessoa e são pagas em até
            <strong>5 (cinco) dias úteis</strong> contados da validação da solicitação —
            ressalvados os prazos do Processador e eventual pendência cadastral sua, caso em que o
            prazo só começa a correr quando a pendência for sanada e você será informado de qual é.
            Não cumprido o prazo, o valor <strong>continua devido</strong> e exigível
            (cláusula 1.4, item 7).</li>
        <li>A Plataforma poderá condicionar o pagamento à <strong>regularidade cadastral e à
                confirmação de identidade</strong>, inclusive para atender a deveres de prevenção à
            lavagem de dinheiro (Lei nº 9.613/1998).</li>
        <li>O participante é o <strong>único responsável pelos tributos</strong> incidentes sobre os
            valores recebidos e pelas declarações correspondentes. A Plataforma fará as retenções que
            lhe sejam legalmente exigíveis e poderá solicitar documentação fiscal como condição ao
            pagamento.</li>
    </ul>
    <div class="highlighted warn">
        <strong>Chave PIX incorreta:</strong> a transferência efetuada para chave informada
        erroneamente pelo participante é considerada <strong>regularmente cumprida</strong> e não gera
        obrigação de novo pagamento, cabendo ao participante buscar a devolução perante o recebedor e
        seu banco.
    </div>

    <h3>9.8. Ausência de garantia de ganho</h3>
    <div class="highlighted warn">
        O valor do bônus depende <strong>integralmente da atividade de terceiro</strong> — a conta
        indicada —, que a Plataforma <strong>não controla e não pode assegurar</strong>. A Plataforma
        <strong>não promete, não projeta e não garante</strong> qualquer valor, resultado, rendimento
        ou retorno.
    </div>

    <h3>9.9. Transparência entre indicador e indicado</h3>
    <ul>
        <li>São registrados <strong>data e endereço IP</strong> do uso do código, para prevenção à
            fraude, com fundamento no <strong>legítimo interesse</strong> (art. 7º, IX, da LGPD).</li>
        <li>Quem indicou visualiza, no seu painel, o <strong>nome da conta indicada</strong>, a
            situação da indicação, o prazo da janela e <strong>indicadores agregados de
                progresso</strong>, estritamente para acompanhar a apuração do próprio bônus.
            <strong>Não</strong> são exibidos dados de contato, documentos, detalhamento financeiro da
            conta indicada nem dados dos alunos dela.</li>
        <li>O participante <strong>não pode</strong> usar essas informações para outra finalidade,
            notadamente prospecção, oferta de serviço concorrente ou repasse a terceiro.</li>
        <li>Ao informar o código de quem o indicou, a conta indicada fica <strong>ciente</strong> de
            que seu nome e a situação da indicação serão exibidos a quem a indicou, para a apuração do
            bônus.</li>
    </ul>

    <h3>9.10. Encerramento da conta</h3>
    <p>O encerramento da conta por iniciativa do participante, ou sua suspensão/encerramento por
        violação deste contrato, implica a <strong>perda do bônus ainda não pago</strong>, inclusive do
        já liberado e não solicitado, sem direito a indenização.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s10">10. CONTEÚDO DE TERCEIROS, AVALIAÇÕES E MODERAÇÃO</h2>

    <h3>10.1. Responsabilidade pelo conteúdo</h3>
    <div class="highlighted">
        O Conteúdo inserido por usuários — perfis, descrições, fotos, mensagens, fichas e
        avaliações — é de <strong>responsabilidade exclusiva de quem o inseriu</strong>. Nos termos do
        <strong>art. 19 da Lei nº 12.965/2014</strong> (Marco Civil da Internet), a Plataforma, na
        condição de provedora de aplicação, somente pode ser responsabilizada civilmente por conteúdo
        de terceiro se, <strong>após ordem judicial específica</strong>, não tomar as providências
        para indisponibilizá-lo. Aplica-se o <strong>art. 21</strong> da mesma lei à divulgação não
        autorizada de cena de nudez ou de ato sexual de caráter privado, caso em que a remoção se dá
        mediante <strong>notificação do participante ou de seu representante</strong>.
    </div>

    <h3>10.2. Moderação é faculdade, não obrigação de vigilância</h3>
    <p>A Plataforma <strong>pode</strong>, a seu critério e sem aviso prévio, moderar, ocultar, limitar
        o alcance ou remover Conteúdo que viole este contrato ou a lei, bem como conservar cópia para
        fins de defesa e cumprimento de ordem de autoridade. O exercício dessa faculdade:</p>
    <ul>
        <li><strong>não</strong> constitui dever geral de monitoramento prévio de todo o Conteúdo;</li>
        <li><strong>não</strong> transforma a Plataforma em autora ou coautora do Conteúdo alheio;</li>
        <li><strong>não</strong> configura censura, e não gera direito a indenização;</li>
        <li><strong>não</strong> implica renúncia à faculdade de agir nos demais casos.</li>
    </ul>

    <h3>10.3. Avaliações</h3>
    <ul>
        <li>Somente quem efetivamente contratou e recebeu o serviço pode avaliá-lo.</li>
        <li>A avaliação deve ser honesta, respeitosa e baseada em experiência real. A Plataforma
            <strong>não edita</strong> a nota nem o conteúdo de avaliações legítimas, ainda que
            negativas.</li>
        <li>São removíveis as avaliações falsas, de serviço não contratado, ofensivas,
            discriminatórias, com dado pessoal de terceiro, com finalidade de extorsão ou obtidas
            mediante vantagem.</li>
        <li>É vedado ao Profissional condicionar atendimento, preço ou benefício a avaliação positiva,
            ou retaliar quem o avaliou.</li>
    </ul>

    <h3>10.4. Canal de notificação</h3>
    <p>Conteúdo que você considere ilícito ou violador de direito seu pode ser comunicado a
        <a href="mailto:{{ $emp('email_suporte') }}">{{ $emp('email_suporte') }}</a>, com a
        identificação clara do conteúdo, a localização (URL ou tela) e a fundamentação. Analisaremos a
        comunicação e responderemos, observados os limites do art. 19 da Lei nº 12.965/2014.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s11">11. PROPRIEDADE INTELECTUAL, MARCA E IMAGEM</h2>
    <ul>
        <li>A marca <strong>SNR FIT</strong>, o software, o código-fonte, o banco de dados, a interface,
            o design, os textos, os vídeos de exercício produzidos pela Plataforma e demais elementos
            são de titularidade da Plataforma ou a ela licenciados, protegidos pela Lei nº 9.279/1996,
            pela Lei nº 9.610/1998 e pela Lei nº 9.609/1998.</li>
        <li>É vedado copiar, reproduzir, modificar, distribuir, explorar comercialmente ou criar obra
            derivada desses elementos sem autorização escrita.</li>
        <li>O <strong>Conteúdo que você insere permanece seu</strong>. Você concede à Plataforma
            licença <strong>gratuita, não exclusiva e limitada à finalidade do serviço</strong>, para
            armazenar, reproduzir e exibir esse Conteúdo na medida necessária à prestação — inclusive
            exibir o seu perfil, suas fotos profissionais e suas avaliações nas vitrines e nos
            resultados de busca.</li>
        <li>Quanto a <strong>nome, imagem e voz</strong> (art. 20 do Código Civil), a licença acima
            abrange a exibição do seu perfil público dentro da Plataforma. O uso em
            <strong>peça publicitária</strong> da SNR FIT fora da Plataforma depende de
            <strong>autorização específica</strong>, e é revogável.</li>
        <li>A licença concedida por você <strong>cessa</strong> com a remoção do Conteúdo ou o
            encerramento da conta, ressalvadas as cópias que a Plataforma deva conservar por obrigação
            legal ou para defesa em processo, e o conteúdo já legitimamente reproduzido por terceiros.</li>
        <li>Fotos de progresso e dados de saúde <strong>nunca</strong> são usados em publicidade,
            independentemente de qualquer autorização genérica.</li>
    </ul>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s12">12. O QUE A SNR FIT FAZ E PELO QUE ELA RESPONDE</h2>
    <p>A Plataforma assume, como fornecedora do seu próprio serviço, as seguintes obrigações — e por
        elas responde nos termos do art. 14 do Código de Defesa do Consumidor:</p>
    <ul>
        <li><strong>Disponibilizar a Plataforma</strong> e manter esforços técnicos razoáveis para que
            funcione de modo adequado e seguro.</li>
        <li><strong>Informar o Valor Final antes da contratação</strong>, com a composição
            discriminada, e cobrar exatamente o valor informado.</li>
        <li><strong>Executar a divisão do pagamento conforme contratado</strong>, repassando ao
            Profissional o Preço do Serviço integral.</li>
        <li><strong>Processar os reembolsos</strong> devidos, inclusive a devolução da própria Taxa de
            Intermediação no arrependimento (cláusula 8.1).</li>
        <li><strong>Guardar as credenciais com segurança</strong> e tratar dados pessoais conforme a
            LGPD e a Política de Privacidade, adotando as medidas do art. 46 da LGPD e comunicando
            incidentes relevantes nos termos do art. 48.</li>
        <li><strong>Disponibilizar os direitos do titular de dados</strong> (acesso, correção,
            portabilidade, eliminação e revogação de consentimento) <strong>por meio próprio no
                produto</strong>, no app e no site, sem depender de atendimento humano — com o
            caminho de telas de cada um na <a href="#s12-1">cláusula 12.1</a>.</li>
        <li><strong>Confirmar por escrito</strong> a exclusão de conta, o cancelamento de assinatura
            e a revogação de consentimento, para que você tenha comprovante do que pediu e do que
            foi feito.</li>
        <li><strong>Manter canal de atendimento</strong> e responder às suas solicitações nos prazos
            da <a href="#s12-1">cláusula 12.1</a> — em regra, <strong>5 (cinco) dias úteis</strong>
            para o atendimento e <strong>15 (quinze) dias corridos</strong> para pedido de titular de
            dados.</li>
        <li><strong>Avisar previamente</strong> sobre alterações relevantes deste contrato e das
            condições comerciais (cláusula 19).</li>
        <li><strong>Agir sobre conteúdo e conduta ilícitos</strong> dos quais tome conhecimento, nos
            limites da cláusula 10.</li>
    </ul>
    <p>A responsabilidade da Plataforma por falha no <strong>seu</strong> serviço — por exemplo,
        cobrança em valor diverso do informado, falha na divisão do pagamento ou indisponibilidade
        relevante e prolongada — é <strong>integral e não é afastada</strong> por nenhuma cláusula
        deste contrato.</p>

    <h3 id="s12-1">12.1. Como exercer cada direito — canal, caminho, prazo e efeito</h3>
    <p>Esta tabela é o <strong>modo de operar da Plataforma</strong>, e não uma orientação genérica:
        o caminho de telas descrito é o que existe no produto, e o prazo é o que nos obriga. Os
        prazos seguem a contagem da <a href="#s1-4">cláusula 1.4</a>. Onde houver
        <strong>e-mail</strong>, o endereço é
        <a href="mailto:{{ $emp('email_suporte') }}">{{ $emp('email_suporte') }}</a> — e, para
        dados pessoais, o do Encarregado,
        <a href="mailto:{{ $emp('dpo_email') }}">{{ $emp('dpo_email') }}</a>.</p>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>O que você quer</th>
                    <th>Onde e como</th>
                    <th>Quem executa e em quanto tempo</th>
                    <th>Confirmação e efeito</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Encerrar a conta</strong> e eliminar seus dados</td>
                    <td><strong>App:</strong> Configurações → Privacidade e meus dados → Excluir
                        minha conta (ou direto em Configurações → Conta).<br>
                        <strong>Site:</strong> menu da conta → Privacidade e meus dados → Excluir
                        minha conta.<br>
                        Nos dois, confirmando a <strong>sua senha</strong>.</td>
                    <td>O <strong>sistema</strong>, na hora. <strong>Imediata</strong>, sem carência
                        e <strong>sem possibilidade de desfazer</strong> — não há período de
                        reativação.</td>
                    <td><strong>Confirmação por e-mail</strong>, enviada
                        antes da eliminação, porque depois dela o seu contato não existe mais na
                        nossa base. <strong>As suas assinaturas mensais são canceladas no mesmo
                            ato</strong> e a confirmação diz quantas foram — é o seu comprovante de
                        que a cobrança parou. O acesso cessa imediatamente, em todos os aparelhos.
                        Efeitos detalhados nas cláusulas 16.1 e 16.3 e na Política de Privacidade.</td>
                </tr>
                <tr>
                    <td><strong>Cancelar uma assinatura</strong> (plano ou pacote mensal)</td>
                    <td><strong>App:</strong> Meus pagamentos → Minhas assinaturas → Cancelar
                        assinatura.<br>
                        <strong>Site:</strong> painel do aluno → assinatura → Cancelar.<br>
                        Sem ligação, sem atendente e sem justificar.</td>
                    <td>O <strong>sistema</strong>, na hora.</td>
                    <td><strong>Confirmação por escrito</strong> (e-mail e aviso no app), que serve
                        de comprovante. Interrompe as cobranças futuras; o período
                        <strong>já pago</strong> segue utilizável até o fim da vigência
                        (cláusula 8.2).</td>
                </tr>
                <tr>
                    <td><strong>Desistir</strong> da contratação (arrependimento)</td>
                    <td>E-mail de atendimento, ou chat com quem você contratou, <strong>dentro de 7
                            dias corridos</strong> da contratação ou do recebimento do produto.
                        Informe o que contratou e a data.</td>
                    <td>A <strong>equipe</strong>. Resposta em até <strong>5 dias úteis</strong> do
                        recebimento do pedido; a devolução é ordenada em até
                        <strong>10 dias corridos</strong> da nossa confirmação, e o crédito efetivo
                        obedece ao prazo do meio de pagamento (cláusula 8.5).</td>
                    <td>Resposta por escrito. Reembolso <strong>integral</strong>, inclusive da
                        Taxa de Intermediação e da tarifa do meio de pagamento (cláusula 8.1).</td>
                </tr>
                <tr>
                    <td><strong>Reclamar</strong> de serviço mal prestado ou produto com defeito</td>
                    <td>Primeiro com quem prestou ou vendeu, pelo chat. Não resolvido, e-mail de
                        atendimento com a identificação da contratação.</td>
                    <td>A <strong>equipe</strong> medeia. Primeira resposta em até
                        <strong>5 dias úteis</strong>; os prazos de vício e de resposta ao
                        consumidor são os do Código de Defesa do Consumidor (cláusula 8.4).</td>
                    <td>Resposta por escrito com a solução proposta. A Plataforma pode antecipar o
                        reembolso e recompor-se depois junto ao Profissional.</td>
                </tr>
                <tr>
                    <td><strong>Acessar ou levar seus dados</strong> (portabilidade)</td>
                    <td><strong>App:</strong> Configurações → Privacidade e meus dados → Receber
                        meus dados por e-mail.<br>
                        <strong>Site:</strong> Privacidade e meus dados → Baixar meus dados.</td>
                    <td>O <strong>sistema</strong>, na hora. Por e-mail ao Encarregado, em até
                        <strong>15 dias corridos</strong> (art. 19, II, da LGPD).</td>
                    <td>Arquivo <strong>JSON</strong>, legível por máquina — baixado no site,
                        anexado ao e-mail no app. Inclui o histórico dos seus aceites e
                        consentimentos.</td>
                </tr>
                <tr>
                    <td><strong>Corrigir</strong> um dado errado</td>
                    <td>Você mesmo, em <em>Editar perfil</em>, no app e no site. O que a tela não
                        permitir editar (por exemplo, e-mail de login pelo app): e-mail ao
                        Encarregado.</td>
                    <td>O <strong>sistema</strong>, na hora, no que é autoatendimento. Pelo
                        Encarregado, em até <strong>15 dias corridos</strong>.</td>
                    <td>O dado corrigido passa a valer imediatamente para quem o acessa.</td>
                </tr>
                <tr>
                    <td><strong>Revogar o consentimento de dados de saúde</strong> (anamnese)</td>
                    <td><strong>App:</strong> Privacidade e meus dados → Dados de saúde → Revogar;
                        ou direto na tela da Anamnese.<br>
                        <strong>Site:</strong> Privacidade e meus dados → Dados de saúde → Revogar e
                        apagar minha anamnese.</td>
                    <td>O <strong>sistema</strong>, na hora.</td>
                    <td>A anamnese é <strong>apagada</strong> e a revogação fica registrada. A sua
                        conta <strong>continua ativa</strong> — você segue treinando e contratando.
                        Pode preencher de novo quando quiser.</td>
                </tr>
                <tr>
                    <td><strong>Mudar a escolha sobre cookies</strong> de medição</td>
                    <td><strong>Site:</strong> botão <em>Preferências de cookies</em> no rodapé de
                        qualquer documento legal, ou Privacidade e meus dados → Cookies e medição de
                        anúncios → Mudar minha escolha.</td>
                    <td>O <strong>sistema</strong>, na hora.</td>
                    <td>Recusando, nada é enviado à Meta — <strong>nem na renovação de
                            assinatura</strong>. Não afeta nenhuma função da Plataforma. No
                        <strong>app não há escolha a fazer</strong>: ele não tem Pixel nem SDK de
                        anúncios.</td>
                </tr>
                <tr>
                    <td><strong>Desativar as notificações</strong> do aplicativo</td>
                    <td>Nos <strong>Ajustes do seu aparelho</strong>, em notificações do SNR FIT. O
                        app explica para que servem antes de pedir a permissão, e recusar não
                        bloqueia nada.</td>
                    <td>O <strong>sistema operacional</strong>, na hora.</td>
                    <td>Você deixa de receber o aviso no aparelho; os mesmos avisos continuam
                        disponíveis em <em>Avisos</em>, dentro do app. Não existe envio promocional
                        — todos os avisos são operacionais (cláusula 20).</td>
                </tr>
                <tr>
                    <td><strong>Solicitar um saque</strong> do bônus de indicação</td>
                    <td><strong>Site:</strong> painel <em>Indique e ganhe</em> → informar a chave PIX
                        → Solicitar. Valor mínimo de {{ $reais($saqueMinimo) }}; uma solicitação em
                        aberto por vez. <strong>Não existe no app</strong>, de propósito
                        (cláusula 9.7).</td>
                    <td>Até o limite publicado no painel, o <strong>sistema</strong>, em minutos.
                        Acima dele, a <strong>equipe</strong>, em até <strong>5 dias úteis</strong>
                        da validação.</td>
                    <td>PIX na chave informada por você, com o histórico no próprio painel. O valor
                        é calculado pela Plataforma, nunca digitado por você.</td>
                </tr>
                <tr>
                    <td><strong>Receber o valor dos seus serviços</strong> (Profissional)</td>
                    <td>Automático, por divisão na origem, para quem tem subconta no Processador.
                        Sem subconta habilitada: solicitação de saque no painel do seu perfil.</td>
                    <td>Com subconta, o <strong>Processador de Pagamentos</strong>, nos prazos
                        <strong>dele</strong> — que a Plataforma não controla. Sem subconta, a
                        <strong>equipe</strong>, em até <strong>5 dias úteis</strong> da solicitação
                        regular (cláusula 7.6).</td>
                    <td>Crédito na sua conta no Processador ou na chave PIX informada. O valor é o
                        <strong>Preço do Serviço integral</strong>.</td>
                </tr>
                <tr>
                    <td><strong>Denunciar</strong> conteúdo ou conduta</td>
                    <td>E-mail de atendimento, identificando o conteúdo, a tela ou URL e o motivo
                        (cláusula 10.4).</td>
                    <td>A <strong>equipe</strong>. Primeira resposta em até <strong>5 dias
                            úteis</strong>; risco a pessoas é tratado de <strong>imediato</strong>.</td>
                    <td>Resposta por escrito, nos limites do art. 19 da Lei nº 12.965/2014.</td>
                </tr>
                <tr>
                    <td><strong>Defender-se</strong> de uma suspensão ou medida</td>
                    <td>Responder à comunicação que você recebeu, pelo mesmo canal, ou pelo e-mail
                        de atendimento.</td>
                    <td>A <strong>equipe</strong>. Você tem prazo <strong>não inferior a 5 dias
                            corridos</strong> do recebimento da comunicação para se manifestar antes
                        da medida definitiva (cláusula 16.2).</td>
                    <td>Decisão motivada por escrito. Valores já devidos por serviços prestados
                        permanecem devidos (cláusula 16.3).</td>
                </tr>
                <tr>
                    <td><strong>Não concordar</strong> com uma nova versão destes Termos</td>
                    <td>Encerrar a conta até a data de vigência da nova versão, pelo caminho da
                        primeira linha desta tabela.</td>
                    <td>O <strong>sistema</strong>, na hora.</td>
                    <td><strong>Sem penalidade</strong>, com reembolso proporcional de período já
                        pago e não usufruído (cláusula 19).</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="muted">Se um caminho de telas desta tabela não existir no produto como descrito,
        <strong>prevalece a tabela</strong> e o pedido pode ser feito por e-mail, com o mesmo prazo:
        a falha é nossa, e não pode virar obstáculo ao seu direito.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s13">13. PELO QUE A SNR FIT NÃO RESPONDE</h2>
    <p>Ressalvado o disposto na cláusula 12 e o que a lei não permita afastar, a Plataforma
        <strong>não responde</strong> por:</p>
    <ul>
        <li><strong>A qualidade, a técnica e o resultado do serviço</strong> prestado pelo Profissional,
            nem por resultado estético, de desempenho ou de saúde — que dependem de fatores
            individuais e não são prometidos por ninguém na Plataforma;</li>
        <li><strong>Lesões, acidentes e danos</strong> ocorridos durante a atividade física ou nas
            dependências de Academia, Studio ou de qualquer local de atendimento;</li>
        <li><strong>A habilitação profissional</strong> declarada pelo Profissional, inclusive registro
            em conselho, cuja veracidade é dele e cuja fiscalização compete ao órgão competente. A
            conferência do registro feita na aprovação (cláusula 3.3) retrata apenas o que o Conselho
            informava <strong>naquela data</strong> e não é monitoramento contínuo: suspensão,
            cancelamento ou inadimplência posteriores perante o Conselho
            <strong>não são comunicados automaticamente</strong> à Plataforma;</li>
        <li><strong>As condições do espaço físico</strong>, equipamentos, higiene, licenças, alvarás e
            seguros de estabelecimentos;</li>
        <li><strong>O produto vendido pela Loja</strong> — descrição, qualidade, validade,
            disponibilidade, prazo e entrega;</li>
        <li><strong>O descumprimento, pelo Profissional</strong>, de horário, política de cancelamento,
            obrigação fiscal, trabalhista ou previdenciária;</li>
        <li><strong>Conteúdo inserido por terceiros</strong>, nos limites do art. 19 da Lei nº
            12.965/2014 (cláusula 10.1);</li>
        <li><strong>Condutas praticadas fora da Plataforma</strong>, inclusive pagamentos combinados
            por fora e serviços contratados por outros meios;</li>
        <li><strong>Falhas, prazos, retenções ou bloqueios do Processador de Pagamentos</strong>, de
            bancos, do arranjo PIX ou de emissores de cartão;</li>
        <li><strong>Indisponibilidade decorrente de caso fortuito, força maior</strong>, falha de
            telecomunicações, energia, provedores de infraestrutura, ataque cibernético de terceiro ou
            determinação de autoridade;</li>
        <li><strong>Perda de dados ou conteúdo</strong> que o usuário tenha excluído, ou decorrente do
            encerramento da conta a seu pedido;</li>
        <li><strong>Uso da conta por terceiro</strong> em razão de credencial compartilhada, anotada ou
            mal guardada pelo usuário;</li>
        <li><strong>Expectativa de ganho</strong> de Profissionais quanto a volume de alunos, posição
            na vitrine ou receita — que a Plataforma não garante e não pode garantir.</li>
    </ul>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s14">14. LIMITAÇÃO DE RESPONSABILIDADE</h2>
    <p>A Plataforma é fornecida <strong>no estado em que se encontra</strong> e no estado em que está
        disponível. Não garantimos funcionamento ininterrupto, isento de erro ou adequado a finalidade
        específica não prevista neste contrato, podendo realizar manutenções e evoluções.</p>
    <div class="highlighted">
        <p><strong>Nas relações NÃO consumeristas</strong> — isto é, com Profissionais e
            estabelecimentos que usam a Plataforma como insumo da sua atividade econômica —, e na
            máxima extensão permitida pela lei: (i) a Plataforma não responde por danos indiretos,
            lucros cessantes, perda de chance, perda de dados ou dano reputacional; e (ii) a
            responsabilidade total agregada fica limitada ao valor das Taxas de Intermediação
            efetivamente recebidas daquele usuário nos <strong>12 (doze) meses</strong> anteriores ao
            fato gerador.</p>
        <p style="margin-bottom:0;"><strong>Nas relações de consumo, esta limitação não se
                aplica.</strong> É nula a cláusula que exonere ou atenue a responsabilidade do
            fornecedor por vício ou por dano ao consumidor (arts. 25 e 51, I, do Código de Defesa do
            Consumidor), e nada neste contrato pretende afastar direito que a lei não permita
            renunciar.</p>
    </div>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s15">15. INDENIZAÇÃO (DEFESA E ISENÇÃO)</h2>
    <p>Profissionais e estabelecimentos concordam em <strong>defender, isentar e indenizar</strong> a
        Plataforma, seus sócios, administradores e prestadores, por perdas, danos, custos, despesas
        processuais e honorários decorrentes de:</p>
    <ul>
        <li>demanda de Aluno, de outro usuário, de autoridade, de conselho profissional ou de terceiro
            relativa ao serviço que prestaram ou ao produto que venderam;</li>
        <li>violação deste contrato, da lei ou de direito de terceiro;</li>
        <li>tratamento irregular de dados pessoais sob sua responsabilidade (cláusula 17);</li>
        <li>declaração falsa de qualificação, habilitação ou regularidade;</li>
        <li>reclamação trabalhista ou previdenciária que tenha por fundamento a prestação de serviços
            realizada por seu intermédio.</li>
    </ul>
    <p>A Plataforma informará a parte sobre a demanda e poderá, a seu critério, conduzir a própria
        defesa, sem que isso afaste o dever de reembolso.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s16">16. SUSPENSÃO, ENCERRAMENTO E DEVIDO PROCESSO</h2>

    <h3>16.1. Encerramento pelo usuário</h3>
    <p>Você pode encerrar sua conta a qualquer momento, <strong>sem penalidade</strong>, pelo caminho
        descrito na primeira linha da <a href="#s12-1">cláusula 12.1</a> — no app e no site, em
        <em>Privacidade e meus dados</em>, confirmando a sua senha.</p>
    <div class="highlighted warn">
        <p style="margin:0;">O encerramento é <strong>imediato e definitivo</strong>: executado pelo
            sistema no mesmo ato, <strong>sem período de carência</strong> e
            <strong>sem possibilidade de reativação</strong>. Não existe "conta desativada" que volte
            depois, nem janela de arrependimento sobre a própria exclusão. Se você quer apenas parar
            de pagar, o que você procura é <strong>cancelar a assinatura</strong> (cláusula 8.2); se
            quer apenas remover os dados de saúde, é <strong>revogar aquele consentimento</strong>
            (cláusula 17.1) — nos dois casos a conta continua existindo.</p>
        <p style="margin:8px 0 0;"><strong>As suas assinaturas são canceladas junto.</strong> Ao
            encerrar a conta, a Plataforma encerra automaticamente as suas recorrências junto ao
            Processador de Pagamentos, de modo que <strong>não há cobrança futura</strong>. A
            confirmação por escrito informa quantas foram canceladas, e é o seu comprovante —
            guarde-a. O período já pago segue utilizável até o fim, mas o acesso à conta cessa no
            mesmo ato; se você quer usufruir o que pagou, cancele a assinatura e exclua a conta
            depois.</p>
        <p style="margin:8px 0 0;">Antes de eliminar os dados, enviamos a
            <strong>confirmação por escrito</strong> ao seu contato cadastrado, porque depois da
            eliminação ele não existe mais na nossa base.</p>
    </div>
    <p class="muted">Se você é <strong>Profissional ou estabelecimento</strong>, o encerramento
        <strong>não cancela</strong> as assinaturas dos seus alunos: aquele dinheiro é deles, e a
        decisão de parar ou migrar também. Nós os <strong>avisamos</strong> em até 2 dias úteis, com
        as saídas disponíveis (cláusula 16.3).</p>
    <p>Obrigações financeiras já constituídas, serviços já contratados por terceiros e deveres de
        sigilo <strong>permanecem exigíveis</strong> após o encerramento. O que é apagado, o que é
        anonimizado e o que a lei nos obriga a conservar está detalhado na
        <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a> e na cláusula 18.</p>

    <h3>16.2. Medidas pela Plataforma e direito de manifestação</h3>
    <p>A Plataforma poderá advertir, restringir funcionalidades, retrair o perfil, suspender ou
        encerrar a conta em caso de violação deste contrato ou da lei, inadimplência, fraude, risco a
        terceiros, reclamações graves ou reiteradas, ordem de autoridade ou inatividade prolongada.</p>
    <div class="highlighted">
        <p style="margin:0;">Salvo nas hipóteses de <strong>risco iminente a pessoas, fraude em curso,
                ordem de autoridade ou exigência legal</strong>, a Plataforma
            <strong>comunicará o motivo</strong> — pelo e-mail cadastrado e por aviso no produto — e
            abrirá prazo <strong>não inferior a 5 (cinco) dias corridos</strong>, contados do
            <strong>recebimento</strong> dessa comunicação, para que o usuário se manifeste e, quando
            cabível, corrija a situação, antes de aplicar medida definitiva.</p>
        <p style="margin:8px 0 0;">Havendo urgência, a comunicação e a oportunidade de manifestação se
            dão <strong>em seguida</strong> à medida — em até <strong>2 (dois) dias úteis</strong> da
            sua adoção —, e a medida é <strong>reavaliada</strong> à luz do que o usuário apresentar.
            A manifestação é respondida por escrito, com o motivo da decisão, em até
            <strong>5 (cinco) dias úteis</strong> do seu recebimento.</p>
        <p style="margin:8px 0 0;"><strong>Se a Plataforma não comunicar o motivo nem abrir o prazo</strong>
            nas hipóteses em que este contrato a obriga, a medida definitiva
            <strong>não se sustenta</strong>: o acesso é restabelecido mediante a sua reclamação,
            sem prejuízo da reparação do que a suspensão indevida tiver causado.</p>
    </div>

    <h3>16.3. Efeitos do encerramento</h3>
    <ul>
        <li>Valores <strong>já devidos ao Profissional</strong> por serviços efetivamente prestados
            permanecem devidos e são pagos, ressalvada a compensação de estornos, reembolsos e débitos
            previstos neste contrato;</li>
        <li>Alunos com plano vigente são informados <strong>em até 2 (dois) dias úteis</strong> do
            encerramento, e a Plataforma envidará esforços para a transição ou o reembolso
            proporcional do período não usufruído, ordenado no prazo da cláusula 8.5;</li>
        <li>Bônus de indicação não pago é perdido (cláusula 9.10);</li>
        <li>Os dados pessoais seguem o tratamento da Política de Privacidade e da cláusula 18.</li>
    </ul>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s17">17. PROTEÇÃO DE DADOS PESSOAIS (LGPD)</h2>
    <p>O tratamento de dados pessoais observa a Lei nº 13.709/2018 e está detalhado na
        <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, que integra este contrato.
        O <strong>como fazer</strong> de cada direito — acesso, correção, portabilidade, eliminação e
        revogação — está na <a href="#s12-1">cláusula 12.1</a>, com canal, caminho de telas e prazo.</p>

    <h3>17.1. Consentimentos: quais existem, como se dão e como se revogam</h3>
    <p>A Plataforma usa consentimento <strong>apenas</strong> onde ele é a base legal correta, e
        <strong>nunca</strong> como fundamento genérico: o que é necessário para executar o contrato
        não depende de autorização separada. Existem <strong>dois</strong>, independentes entre si —
        um não vale pelo outro, e cada ato de conceder ou revogar fica
        <strong>registrado com data, hora, endereço IP e origem</strong>, de forma incremental, do
        mesmo modo que o aceite destes Termos (cláusula 1.1):</p>
    <ul>
        <li><strong>Dados de saúde</strong> (anamnese) — art. 11, I, da LGPD. Dado sensível, de
            consentimento <strong>específico e destacado</strong>: é uma confirmação própria, marcada
            por você na tela da anamnese, no app e no site, sem a qual nada é gravado. O
            preenchimento é <strong>opcional</strong> e não condiciona nenhuma outra função.
            <strong>Revogar</strong> apaga a anamnese e a conta <strong>continua ativa</strong>
            (caminho na cláusula 12.1).
            <br>
            Quando a anamnese é preenchida pela <strong>Academia ou Studio</strong> no painel dela, o
            estabelecimento <strong>declara</strong> ter obtido a sua autorização, e essa declaração
            fica registrada com a identidade dele, a data e o endereço IP. Ninguém consente pelo
            outro: a base legal continua sendo o <strong>seu</strong> consentimento, o
            estabelecimento responde pela veracidade do que declarou, e
            <strong>você revoga pelo mesmo caminho</strong>, como se tivesse preenchido você
            mesmo.</li>
        <li><strong>Cookies de medição de anúncios</strong> — art. 7º, I. Dado no banner do site, e
            <strong>reversível pelo mesmo caminho</strong> a qualquer momento, pelo botão
            <em>Preferências de cookies</em>. Enquanto não houver aceite, <strong>nada</strong> é
            enviado à Meta — inclusive na <strong>renovação de assinatura</strong>, que também
            consulta esse registro antes de enviar qualquer evento. Recusar
            <strong>não afeta</strong> nenhuma função. O <strong>aplicativo não tem</strong> Pixel,
            SDK de anúncios nem rastreamento, de modo que ali não há nada a consentir.</li>
    </ul>
    <p>As <strong>notificações</strong> do aplicativo não entram nesta lista por uma razão de
        substância: todos os avisos que a Plataforma envia são <strong>operacionais</strong> — fazem
        parte do serviço contratado (ver "Comunicações", na cláusula 20) — e a permissão do sistema
        operacional é controlada por você nos Ajustes do aparelho. O app
        <strong>explica para que servem antes</strong> de pedir essa permissão, e recusá-la não
        bloqueia nada. Se algum dia a Plataforma passar a enviar comunicação
        <strong>promocional</strong>, ela dependerá de consentimento próprio e destacado, com
        descadastramento, e esta cláusula será atualizada antes.</p>

    <p>Em síntese, quanto aos <strong>papéis</strong>:</p>
    <ul>
        <li>A Plataforma é <strong>controladora</strong> dos dados que trata para operar o serviço,
            cadastrar usuários, processar pagamentos, prevenir fraude e cumprir obrigação legal.</li>
        <li><strong>Academia, Studio e Loja são controladores</strong> dos dados dos seus próprios
            alunos e clientes que tratam na gestão do seu negócio, atuando a Plataforma como
            <strong>operadora</strong> nessa parcela, nos termos do art. 39 da LGPD.</li>
        <li>O <strong>Personal</strong> é controlador dos dados de saúde a que acessa para prescrever e
            acompanhar o treino do seu aluno, e assume o dever de sigilo e de uso limitado àquela
            finalidade.</li>
    </ul>
    <p>Profissionais e estabelecimentos obrigam-se a: possuir <strong>base legal</strong> para o
        tratamento que realizam; obter <strong>consentimento específico e destacado</strong> para dados
        sensíveis de saúde (art. 11, I, da LGPD); usar os dados <strong>somente</strong> para a
        finalidade informada; manter <strong>sigilo</strong>; não transferir a terceiro sem base legal;
        atender requisições de titulares e da Autoridade Nacional de Proteção de Dados; e
        <strong>comunicar imediatamente</strong> à Plataforma qualquer incidente de segurança de que
        tenham conhecimento. O descumprimento gera responsabilidade própria perante o titular e a
        autoridade (arts. 42 a 45 da LGPD), além do dever de indenizar previsto na cláusula 15.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s18">18. GUARDA DE REGISTROS DE ACESSO</h2>
    <p>Na condição de provedora de aplicação de internet constituída com fins econômicos, a Plataforma
        <strong>mantém os registros de acesso à aplicação</strong> — data, hora e endereço IP de uso —
        pelo prazo de <strong>6 (seis) meses corridos</strong> contados de <strong>cada</strong>
        acesso, conforme o art. 15 da Lei nº 12.965/2014, em ambiente controlado e sigiloso. O prazo
        é o <strong>mínimo</strong> que a lei impõe, não um máximo discricionário. Esses registros:</p>
    <ul>
        <li>só são fornecidos a terceiros mediante <strong>ordem judicial</strong> (art. 10, §1º, do
            Marco Civil), ou a autoridade competente nos casos em que a lei o autorize;</li>
        <li>podem ser conservados por <strong>prazo maior</strong> por determinação de autoridade ou
            quando necessários à defesa em processo, inclusive após o encerramento da conta;</li>
        <li>são mantidos também quando o usuário exclui a conta, estritamente para cumprimento dessa
            obrigação legal (art. 16, I, da LGPD).</li>
    </ul>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s19">19. ALTERAÇÕES DESTES TERMOS</h2>
    <ul>
        <li>Podemos atualizar este contrato para refletir mudança legal, técnica ou de negócio. Toda
            versão tem <strong>número e data de vigência</strong>.</li>
        <li><strong>Alterações relevantes DESFAVORÁVEIS</strong> — as que afetem preço, taxa, forma
            de pagamento, responsabilidades ou tratamento de dados em prejuízo do usuário — serão
            comunicadas pelos canais da Plataforma com antecedência mínima de
            <strong>30 (trinta) dias corridos</strong> da data de vigência, contados do
            <strong>envio</strong> da comunicação ao contato que você cadastrou, acompanhadas de um
            <strong>resumo do que mudou</strong>, e dependerão de <strong>novo aceite</strong> para o
            uso continuado. <strong>Não cumprido o aviso prévio, a alteração não é oponível a
                você</strong> enquanto os 30 dias não se completarem: valem as condições anteriores
            no período, e nenhum valor novo pode ser cobrado nele.</li>
        <li>Se você <strong>não concordar</strong> com a nova versão, pode encerrar a conta
            <strong>sem qualquer penalidade</strong> até a data de vigência, com direito ao reembolso
            proporcional de período já pago e não usufruído, ordenado no prazo da cláusula 8.5.</li>
        <li><strong>Vigência imediata</strong>, com comunicação na primeira oportunidade, apenas em
            três hipóteses, todas restritas: alteração <strong>exigida por lei ou por
                autoridade</strong>; <strong>correção de erro material</strong> (inclusive de texto
            que descrevia procedimento diverso do que o sistema executa); e alteração que
            <strong>apenas amplia direitos</strong> do usuário ou esclarece procedimento,
            <strong>sem</strong> retirar direito, aumentar valor, reduzir prazo a seu favor ou criar
            obrigação nova. A terceira hipótese <strong>não</strong> serve de porta para mudança
            desfavorável: na dúvida sobre a natureza da alteração, aplica-se o aviso prévio de 30
            dias.</li>
        <li class="muted">A <strong>versão 3.1</strong>, em vigor desde
            {{ \Carbon\Carbon::parse(config('termos.vigente_desde'))->format('d/m/Y') }}, enquadra-se
            na segunda e na terceira hipóteses, e por isso teve vigência imediata: ela não alterou
            preço, taxa nem repasse — definiu a contagem dos prazos, descreveu os procedimentos que
            o produto executa, acrescentou no aplicativo a exportação de dados e o cancelamento de
            assinatura que já estavam prometidos, e deu revogação própria ao consentimento de dados
            de saúde.</li>
        <li>Nenhuma alteração retroage para prejudicar direito já constituído, nem altera as condições
            de contratos em curso enquanto vigentes.</li>
    </ul>
    <p class="muted">Esta cláusula observa a vedação do art. 51, XIII, do Código de Defesa do
        Consumidor à modificação unilateral do conteúdo do contrato.</p>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s20">20. DISPOSIÇÕES GERAIS</h2>
    <ul>
        <li><strong>Integralidade.</strong> Este contrato, somado ao documento do seu perfil e à
            Política de Privacidade, constitui o acordo integral entre as partes quanto ao uso da
            Plataforma.</li>
        <li><strong>Nulidade parcial.</strong> A invalidade de uma cláusula não contamina as demais,
            que permanecem em vigor, devendo a cláusula inválida ser substituída por outra de efeito
            equivalente e lícito.</li>
        <li><strong>Tolerância não é novação.</strong> A não exigência de qualquer obrigação não
            importa renúncia, perdão, novação ou alteração do contrato.</li>
        <li><strong>Cessão.</strong> Você não pode ceder sua posição contratual sem nossa anuência. A
            Plataforma pode ceder este contrato em caso de reorganização societária, fusão, aquisição
            ou transferência de operação, com comunicação aos usuários.</li>
        <li><strong>Comunicações.</strong> Serão feitas pelo e-mail cadastrado, por aviso no produto
            ou por notificação no aparelho, presumindo-se recebidas quando enviadas ao
            contato informado por você, que deve mantê-lo atualizado.
            <strong>Todas são operacionais</strong> — mensagem no chat, pagamento confirmado, aula
            marcada ou cancelada, ficha pronta, pedido na loja, lembrete da próxima aula, confirmação
            de cancelamento e de exclusão de conta: fazem parte do serviço que você contratou e
            <strong>não são publicidade</strong>. A Plataforma
            <strong>não envia comunicação promocional</strong> por esses canais; se passar a enviar,
            será com consentimento próprio e com descadastramento (cláusula 17.1). Você controla as
            notificações no aparelho pelos Ajustes do sistema operacional, e desativá-las
            <strong>não afeta</strong> nenhuma função — os mesmos avisos continuam na caixa de
            <em>Avisos</em>, dentro do produto.</li>
        <li><strong>Força maior.</strong> Nenhuma parte responde por inadimplemento decorrente de caso
            fortuito ou força maior (art. 393 do Código Civil).</li>
        <li><strong>Idioma.</strong> A versão em língua portuguesa prevalece sobre qualquer tradução.</li>
        <li><strong>Sobrevivência.</strong> As cláusulas de pagamento pendente, sigilo, propriedade
            intelectual, limitação de responsabilidade, indenização, proteção de dados e foro
            subsistem ao encerramento da conta.</li>
    </ul>

    {{-- ─────────────────────────────────────────────────────────────── --}}

    <h2 id="s21">21. LEI APLICÁVEL, FORO E ATENDIMENTO</h2>
    <p>Este contrato é regido pelas leis da <strong>República Federativa do Brasil</strong>, aplicando-se
        o art. 11 da Lei nº 12.965/2014 quanto à jurisdição brasileira sobre a coleta e o tratamento de
        dados realizados no território nacional.</p>
    <ul>
        <li><strong>Relações de consumo:</strong> fica assegurado ao consumidor o foro do
            <strong>seu domicílio</strong> (art. 101, I, do Código de Defesa do Consumidor), não sendo
            oponível a ele qualquer eleição de foro diversa.</li>
        {{--
            A comarca não é nomeada porque o endereço do titular não é divulgado
            (ver o bloco sobre endereço e telefone em `config/empresa.php`). Fica
            determinável pelo registro no CNPJ, que é público — um foro eleito que
            o aderente não consegue identificar não é eleição válida, e um
            placeholder `[COMARCA]` num contrato em vigor é pior ainda.
        --}}
        <li><strong>Relações não consumeristas</strong> (Profissionais e estabelecimentos no exercício
            da sua atividade econômica): fica eleito o foro da <strong>comarca da sede da SNR FIT</strong>,
            conforme consta do seu registro no CNPJ, com renúncia a qualquer outro.</li>
        <li><strong>Solução amigável:</strong> incentivamos o contato prévio pelo nosso atendimento e,
            para consumidores, o uso da plataforma pública
            <a href="https://www.consumidor.gov.br" target="_blank" rel="noopener">consumidor.gov.br</a>,
            sem que isso constitua condição para o acesso ao Judiciário.</li>
    </ul>
    <p><strong>Atendimento:</strong> <a href="mailto:{{ $emp('email_suporte') }}">{{ $emp('email_suporte') }}</a><br>
        <strong>Encarregado de Dados (DPO):</strong> {{ $emp('dpo_nome') }} —
        <a href="mailto:{{ $emp('dpo_email') }}">{{ $emp('dpo_email') }}</a></p>

    <div class="highlighted">
        <strong>Aceitação.</strong> Ao criar conta ou continuar usando a Plataforma após a vigência
        desta versão, você declara ter lido e aceito estes Termos, o documento específico do seu perfil
        e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>. O aceite é registrado
        com versão, data, IP e agente de usuário.
    </div>

@endsection
