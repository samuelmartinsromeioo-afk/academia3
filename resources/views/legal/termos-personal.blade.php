@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Personal Trainer')
@section('doc_subtitulo', 'Condições para profissionais de educação física')
@section('doc_perfil', 'Perfil: Personal Trainer')
@section('doc_versao', config('termos.versao'))
@section('nav_personal', 'active')

@section('doc_conteudo')

    @php
        $pct = fn ($f) => rtrim(rtrim(number_format($f * 100, 2, ',', '.'), '0'), ',') . '%';
        $reais = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
        $taxaLabel = $pct(\App\Services\AsaasService::feeRate());
        $minimo = \App\Services\AsaasService::valorMinimo();
        $idadeProf = \App\Support\CadastroHelper::IDADE_MINIMA_PROFISSIONAL;

        $exPreco = 100.00;
        $exPix = \App\Services\AsaasService::detalhar($exPreco, 'PIX');
        $exCartao = \App\Services\AsaasService::detalhar($exPreco, 'CREDIT_CARD');
    @endphp

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a
        <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica a você que
        atua no SNR FIT como <strong>personal trainer</strong>. Em caso de divergência, prevalece este
        documento.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#p1">1. Elegibilidade e aprovação</a></li>
            <li><a href="#p2">2. Qualificação profissional e CREF</a></li>
            <li><a href="#p3">3. Natureza da relação (autonomia)</a></li>
            <li><a href="#p4">4. O que você pode fazer</a></li>
            <li><a href="#p5">5. Suas obrigações profissionais</a></li>
            <li><a href="#p6">6. Preço, taxa e repasse integral</a></li>
            <li><a href="#p7">7. Estornos, reembolsos e compensação</a></li>
            <li><a href="#p8">8. Notas fiscais e tributos</a></li>
            <li><a href="#p9">9. Agenda, cancelamentos e no-show</a></li>
            <li><a href="#p10">10. Dados de saúde dos alunos (LGPD)</a></li>
            <li><a href="#p11">11. Condutas vedadas</a></li>
            <li><a href="#p12">12. Responsabilidade e indenização</a></li>
            <li><a href="#p13">13. Suspensão e descredenciamento</a></li>
            <li><a href="#p14">14. Programa de indicação</a></li>
            <li><a href="#p15">15. Contato</a></li>
        </ul>
    </div>

    <h2 id="p1">1. ELEGIBILIDADE E APROVAÇÃO</h2>
    <ul>
        <li>Idade mínima de <strong>{{ $idadeProf }} anos</strong> e CPF ou CNPJ válido.</li>
        <li>O cadastro passa por <strong>análise e aprovação</strong> antes de liberar o acesso;
            podemos aprovar, recusar ou solicitar documentos complementares.</li>
        <li>A aprovação é <strong>conferência cadastral</strong>, não auditoria nem certificação da sua
            competência técnica — e não transfere à Plataforma responsabilidade pela sua atuação.</li>
        <li>As informações e documentos fornecidos devem ser verdadeiros e atuais.</li>
    </ul>

    <h2 id="p2">2. QUALIFICAÇÃO PROFISSIONAL E CREF</h2>
    <div class="highlighted warn">
        Você declara possuir a <strong>habilitação legal</strong> para atuar como profissional de
        educação física, nos termos da <strong>Lei nº 9.696/1998</strong>, incluindo formação e
        <strong>registro ativo no CREF</strong>. Você é o <strong>único responsável</strong> pela
        veracidade dessa declaração e por manter a sua regularidade perante o Conselho. A
        <strong>fiscalização da atividade profissional compete ao órgão de classe</strong>, não à
        Plataforma.
    </div>

    <h3>2.1. O que a Plataforma confere, e o que isso não significa</h3>
    <p>A Plataforma faz <strong>duas conferências</strong> sobre o seu registro, e é importante que
        você saiba exatamente o alcance de cada uma — nenhuma delas é auditoria, certificação ou
        garantia da sua habilitação:</p>
    <ul>
        <li><strong>No cadastro, o formato.</strong> O sistema valida automaticamente que o número
            informado tem a forma de um registro do CREF — matrícula e sigla do estado do conselho
            emissor, e a categoria quando você a informa. Registro malformado
            <strong>impede o cadastro</strong>. Isto confere a <strong>forma</strong>, não a
            existência: um número bem formado e inventado passa nesta etapa.</li>
        <li><strong>Na aprovação, a existência.</strong> Antes de liberar o seu acesso, a Plataforma
            <strong>consulta o seu registro na página pública do seu Conselho Regional</strong> e
            registra internamente que a conferência foi feita, por quem e o que o Conselho informava
            naquele momento. Registro que não seja localizado como <strong>ativo</strong> leva à
            recusa do cadastro ou ao pedido de esclarecimento.</li>
    </ul>
    <div class="highlighted">
        <p style="margin:0;"><strong>O que essa conferência NÃO é.</strong> Ela retrata o que o
            Conselho informava <strong>na data da aprovação</strong>. A Plataforma
            <strong>não monitora</strong> a sua regularidade de forma contínua, não é informada
            automaticamente de suspensão, cancelamento ou inadimplência perante o Conselho, e
            <strong>não garante</strong> a terceiros a sua habilitação (cláusulas 3.3 e 13 dos
            <a href="{{ route('termos') }}#s3">Termos gerais</a>).</p>
        <p style="margin:8px 0 0;">Por isso o dever continua inteiro do seu lado: você se obriga a
            <strong>manter o registro ativo</strong> durante todo o uso da Plataforma e a
            <strong>comunicar-nos imediatamente</strong> qualquer suspensão, cancelamento ou alteração
            de categoria. A Plataforma pode, a qualquer tempo, reconferir o registro e exigir
            comprovação de regularidade como condição para manter o perfil na vitrine ou liberar
            recebimentos.</p>
    </div>
    <p>Atuar sem registro ativo, além de infração à Lei nº 9.696/1998, é
        <strong>inadimplemento deste contrato</strong> e autoriza o descredenciamento imediato, sem
        prejuízo da sua responsabilidade perante o aluno, o Conselho e terceiros. Informar registro
        <strong>falso ou de terceiro</strong> caracteriza, além disso, os ilícitos civis e penais
        cabíveis.</p>

    <h2 id="p3">3. NATUREZA DA RELAÇÃO (AUTONOMIA)</h2>
    <p>Você atua como <strong>profissional autônomo e independente</strong>. Este contrato
        <strong>não cria</strong> vínculo empregatício, societário, de parceria, de agência, de
        representação comercial ou de subordinação com a Plataforma.</p>
    <div class="highlighted">
        São elementos dessa autonomia, que você declara e exerce de fato:
        <ul style="margin:8px 0 0;">
            <li>você define <strong>livremente seus preços</strong>, pacotes e promoções;</li>
            <li>você define <strong>seus horários</strong>, dias e locais de atendimento, e a sua
                própria agenda;</li>
            <li>você <strong>aceita ou recusa</strong> alunos e serviços, sem meta, cota ou exigência
                de exclusividade;</li>
            <li>você <strong>pode atuar em outras plataformas</strong> e atender clientela própria,
                sem qualquer restrição;</li>
            <li>você não recebe salário, não cumpre jornada, não se submete a controle de ponto nem a
                ordens sobre como executar seu trabalho técnico;</li>
            <li>você é responsável pelos seus próprios equipamentos, deslocamento e custos.</li>
        </ul>
    </div>
    <p>Você é responsável pelas suas obrigações fiscais, trabalhistas e previdenciárias, inclusive em
        relação a eventuais auxiliares que contrate.</p>

    <h2 id="p4">4. O QUE VOCÊ PODE FAZER</h2>
    <ul>
        <li><strong>Publicar seu perfil</strong> com foto, especialidades, modalidade de atendimento e
            apresentação, e aparecer nas vitrines e buscas.</li>
        <li><strong>Definir seus preços</strong> — aula avulsa, pacotes mensais, ficha personalizada e
            avaliação física — e alterá-los quando quiser, respeitado o valor mínimo da cláusula 6.4.</li>
        <li><strong>Gerenciar agenda</strong>, horários livres, aulas e alunos.</li>
        <li><strong>Montar fichas de treino</strong>, mesociclos, avaliações físicas e acompanhar
            progresso, usando as ferramentas da Plataforma.</li>
        <li><strong>Conversar</strong> com seus alunos pelo chat.</li>
        <li><strong>Receber</strong> pela Plataforma, com repasse integral do seu preço, e
            <strong>solicitar saque</strong> conforme as regras do processador.</li>
        <li><strong>Vincular-se</strong> a academias parceiras, quando houver convite ou aprovação.</li>
        <li><strong>Participar</strong> do Programa de Indicação (cláusula 14).</li>
        <li><strong>Sair quando quiser</strong>, encerrando a conta sem multa e sem aviso prévio, no
            app (Configurações → Privacidade e meus dados) ou no site — com efeito
            <strong>imediato e definitivo</strong>, confirmação por escrito e sem possibilidade de
            reativação. Valores já devidos por serviços prestados continuam devidos
            (cláusula 13).</li>
        <li><strong>Exercer os seus direitos de titular</strong> — exportar os dados, corrigir e
            excluir — na página <em>Privacidade e meus dados</em>, no app e no site, pelos caminhos
            da <a href="{{ route('termos') }}#s12-1">cláusula 12.1 dos Termos gerais</a>.</li>
    </ul>

    <h2 id="p5">5. SUAS OBRIGAÇÕES PROFISSIONAIS</h2>
    <ul>
        <li>Prestar os serviços com <strong>diligência, técnica e segurança</strong>, respeitando os
            limites e a saúde do aluno.</li>
        <li><strong>Analisar a anamnese antes de prescrever</strong> e adequar carga, volume e
            exercício ao aluno.</li>
        <li><strong>Não</strong> prestar orientação médica, diagnóstico, prescrição de medicamento,
            prescrição dietética ou qualquer ato privativo de outra profissão regulamentada.</li>
        <li><strong>Encaminhar ao médico</strong> diante de qualquer sinal de risco, e interromper a
            atividade quando necessário.</li>
        <li><strong>Honrar os agendamentos</strong> confirmados e comunicar com antecedência qualquer
            impossibilidade.</li>
        <li>Informar com clareza, antes da contratação, o que está incluído no serviço e a sua
            política de cancelamento.</li>
        <li><strong>Não prometer resultado</strong> estético, de desempenho ou de saúde.</li>
        <li>Emitir os <strong>documentos fiscais</strong> devidos (cláusula 8).</li>
    </ul>

    <h2 id="p6">6. PREÇO, TAXA E REPASSE INTEGRAL</h2>

    <h3>6.1. Você recebe o preço que definiu, por inteiro</h3>
    <div class="highlighted">
        <p><strong>A taxa de intermediação da Plataforma é somada ao seu preço e cobrada do
                aluno</strong> — ela <strong>não é descontada de você</strong>.</p>
        <p style="margin-bottom:0;">Exemplo com a taxa de <strong>{{ $taxaLabel }}</strong> vigente
            nesta data, numa aula que você precificou em {{ $reais($exPix['base']) }} e o aluno pagou
            por PIX:</p>
        <ul style="margin-top:6px;">
            <li>Você recebe: <strong>{{ $reais($exPix['base']) }}</strong> (100% do seu preço)</li>
            <li>O aluno paga: <strong>{{ $reais($exPix['total']) }}</strong></li>
            <li>A Plataforma fica com: {{ $reais($exPix['comissao']) }}</li>
        </ul>
    </div>
    <p class="muted">Modelo adotado a partir da versão 3.0 destes Termos. Na redação anterior a taxa
        era descontada do seu valor (de R$ 100 você recebia R$ 90); essa forma foi substituída e não
        se aplica a contratações feitas na vigência desta versão.</p>

    <h3>6.2. O que o aluno vê</h3>
    <p>As vitrines, as páginas de detalhe e o checkout exibem ao aluno o <strong>valor final</strong>
        (seu preço + taxa), com a composição discriminada. Isso é exigência de transparência ao
        consumidor (arts. 6º, III, e 31 do CDC) e protege você: o aluno sabe que o acréscimo é da
        Plataforma, não um aumento seu.</p>

    <h3>6.3. Cartão e PIX</h3>
    <ul>
        <li>No <strong>cartão</strong>, a tarifa da operadora também é acrescida ao valor do aluno. No
            exemplo acima, o aluno pagaria {{ $reais($exCartao['total']) }}, sendo
            {{ $reais($exCartao['gateway']) }} de tarifa.</li>
        <li><strong>O seu repasse não muda</strong> conforme o meio que o aluno escolher: nos dois
            casos você recebe {{ $reais($exPix['base']) }}.</li>
    </ul>

    <h3>6.4. Valor mínimo</h3>
    <p>Há um <strong>valor mínimo de {{ $reais($minimo) }}</strong> por cobrança — abaixo dele a taxa
        não cobre a tarifa do meio de pagamento e o processador rejeita a divisão. Serviço precificado
        abaixo desse limite não pode ser cobrado pela Plataforma, e o sistema avisa você.</p>

    <h3>6.5. Recebimento</h3>
    <ul>
        <li>Para receber é necessário <strong>concluir o cadastro e a verificação</strong> junto à
            {{ \App\Support\Empresa::get('processador_pagamentos') }}, que cria a sua subconta.</li>
        <li><strong>Prazos de liquidação, antecipação, retenções e bloqueios seguem as regras do
                processador</strong>, não da Plataforma, que não os controla.</li>
        <li>Enquanto a sua subconta não estiver habilitada, o valor fica na conta da Plataforma e é
            repassado <strong>mediante solicitação de saque</strong>, no painel do seu perfil. A
            Plataforma paga em até <strong>5 (cinco) dias úteis</strong> contados da
            <strong>solicitação regular</strong> — a que tenha chave PIX válida de titularidade sua e
            cadastro sem pendência de verificação. Havendo pendência, o prazo só começa a correr
            quando ela for sanada, e informamos qual é. <strong>Esse valor é seu</strong>, não é
            receita da Plataforma, e é devido independentemente de qualquer pendência que não as
            previstas na cláusula 7. Descumprido o prazo, o valor
            <strong>continua devido</strong> e exigível
            (<a href="{{ route('termos') }}#s1-4">cláusula 1.4 dos Termos gerais</a>, item 7).</li>
    </ul>

    <h2 id="p7">7. ESTORNOS, REEMBOLSOS E COMPENSAÇÃO</h2>
    <p>Quando o aluno exerce o <strong>direito de arrependimento</strong> (7 dias
        <strong>corridos</strong>, art. 49 do CDC), obtém reembolso por vício do serviço, ou quando
        ocorre <em>chargeback</em> reconhecido pelo emissor do cartão:</p>
    <div class="highlighted">
        <ul style="margin:0;">
            <li>o aluno recebe de volta <strong>tudo o que pagou</strong>, e a Plataforma
                <strong>devolve a própria taxa de intermediação</strong> — ela não fica com nada
                nessa operação;</li>
            <li>o <strong>preço do serviço</strong> que já tiver sido repassado a você é
                <strong>recomposto</strong>, por <strong>compensação em repasses futuros</strong> ou,
                não havendo saldo suficiente, por cobrança direta;</li>
            <li>se o serviço <strong>já tinha sido prestado</strong> a pedido do aluno dentro do prazo,
                a parcela proporcional ao efetivamente executado é <strong>preservada em seu
                    favor</strong> — você não perde o que já trabalhou.</li>
        </ul>
    </div>
    <p>A Plataforma informará você sobre o pedido de reembolso <strong>em até 2 (dois) dias
            úteis</strong> do seu recebimento e, quando houver controvérsia sobre a execução do
        serviço, abrirá prazo <strong>não inferior a 5 (cinco) dias corridos</strong>, contados do
        recebimento dessa comunicação, para a sua manifestação antes de decidir a recomposição. A
        decisão é comunicada por escrito, com o motivo. Esse prazo <strong>não se aplica</strong>
        quando a devolução for imposta por lei, por decisão do emissor do cartão ou por autoridade —
        nesses casos a devolução ocorre primeiro, e você é informado em seguida.</p>

    <h2 id="p8">8. NOTAS FISCAIS E TRIBUTOS</h2>
    <ul>
        <li><strong>Você emite o documento fiscal do seu serviço</strong> ao aluno, pelo valor do
            <strong>seu preço</strong> — não pelo valor final pago por ele, pois a taxa de
            intermediação não é sua receita.</li>
        <li>A <strong>Plataforma emite o documento fiscal da taxa de intermediação</strong>, que é
            receita dela.</li>
        <li>A Plataforma <strong>não é substituta tributária</strong> sua e não emite nota pelo seu
            serviço.</li>
        <li>Cada parte responde pelos tributos da sua própria receita e pelas obrigações acessórias
            correspondentes.</li>
        <li>A Plataforma poderá solicitar documentação fiscal ou cadastral como condição para liberar
            recebimentos, inclusive para atender a deveres legais de prevenção à lavagem de dinheiro
            (Lei nº 9.613/1998).</li>
    </ul>

    <h2 id="p9">9. AGENDA, CANCELAMENTOS E NO-SHOW</h2>
    <ul>
        <li>Você é responsável por manter agenda e horários atualizados e por honrar os agendamentos
            confirmados.</li>
        <li>Defina com clareza sua política de cancelamento e reagendamento. Ela deve ser
            <strong>razoável e compatível com o CDC</strong>: cláusula que imponha perda total do valor
            por cancelamento feito com antecedência razoável é <strong>abusiva</strong> (art. 51 do
            CDC) e não será sustentada pela Plataforma.</li>
        <li>Cancelamentos reiterados por sua iniciativa, especialmente em cima da hora, podem reduzir
            sua posição na vitrine e, se recorrentes, levar às medidas da cláusula 13.</li>
    </ul>

    <h2 id="p10">10. DADOS DE SAÚDE DOS ALUNOS (LGPD)</h2>
    <div class="highlighted">
        Ao acessar anamneses, avaliações físicas, medidas e fotos de progresso dos seus alunos, você
        trata <strong>dados pessoais sensíveis</strong> (art. 11 da Lei nº 13.709/2018) e assume, como
        <strong>controlador</strong> dessa parcela, o dever de:
        <ul style="margin:8px 0 0;">
            <li>usá-los <strong>somente</strong> para prescrever e acompanhar o treino daquele aluno;</li>
            <li>mantê-los em <strong>sigilo</strong> e não exibi-los a terceiros, inclusive em redes
                sociais, ainda que sem o nome;</li>
            <li><strong>não</strong> transferi-los, vendê-los ou usá-los para prospecção, publicidade
                ou qualquer outra finalidade;</li>
            <li>atender às solicitações do titular e da <strong>ANPD</strong>;</li>
            <li><strong>comunicar imediatamente</strong> à Plataforma qualquer incidente de segurança
                de que tome conhecimento (art. 48 da LGPD).</li>
        </ul>
    </div>
    <p>O descumprimento gera a sua responsabilização perante o aluno e a autoridade (arts. 42 a 45 da
        LGPD), além do dever de indenizar previsto na cláusula 12. Ao encerrar o atendimento, você
        deve <strong>cessar o uso</strong> desses dados.</p>

    <h2 id="p11">11. CONDUTAS VEDADAS</h2>
    <ul>
        <li>Declarar qualificação falsa, registro inexistente ou atuar sem habilitação ativa;</li>
        <li><strong>Aliciar alunos para pagar por fora</strong> e burlar a intermediação quando o
            contato ocorreu pela Plataforma (ver a consequência na cláusula 7.7 dos
            <a href="{{ route('termos') }}#s7">Termos gerais</a>: cobrança das taxas devidas pelas
            operações desviadas);</li>
        <li>Manipular avaliações, pedir avaliação em troca de vantagem, criar contas falsas ou difamar
            concorrentes;</li>
        <li>Assediar alunos, tratá-los de forma discriminatória ou manter aproximação de natureza
            sexual;</li>
        <li>Usar dados de alunos para finalidade não autorizada;</li>
        <li>Prometer resultado, divulgar método sem evidência ou invadir atribuição de outra
            profissão;</li>
        <li>Apresentar-se como <strong>representante, preposto, franqueado ou funcionário</strong> da
            SNR FIT, ou usar sua marca sem autorização escrita.</li>
    </ul>
    <p>Atender clientela <strong>própria</strong>, conquistada fora da Plataforma, por qualquer meio e
        preço, <strong>não</strong> é conduta vedada — a vedação alcança somente o desvio de contato
        originado aqui.</p>

    <h2 id="p12">12. RESPONSABILIDADE E INDENIZAÇÃO</h2>
    <p>Você é <strong>integralmente responsável pelos serviços que presta</strong>, inclusive por
        lesões, danos, reclamações e demandas deles decorrentes. Você concorda em
        <strong>defender, isentar e indenizar</strong> a Plataforma por perdas, custos, despesas
        processuais e honorários resultantes de: demanda de aluno, de autoridade, do Conselho
        profissional ou de terceiro relativa ao seu serviço; descumprimento destes Termos; declaração
        falsa de habilitação; tratamento irregular de dados sob sua responsabilidade; e reclamação
        trabalhista ou previdenciária fundada na prestação de serviços realizada por meio da
        Plataforma.</p>

    <h2 id="p13">13. SUSPENSÃO E DESCREDENCIAMENTO</h2>
    <p>Podemos advertir, restringir funcionalidades, retrair seu perfil da vitrine, suspender ou
        descredenciar seu cadastro em caso de violação destes Termos, qualificação irregular,
        reclamações graves ou reiteradas, fraude ou risco a alunos.</p>
    <div class="highlighted">
        <p style="margin:0;">Salvo <strong>risco iminente a pessoas, fraude em curso, ordem de
                autoridade ou exigência legal</strong>, informaremos o motivo — pelo e-mail cadastrado
            e por aviso no produto — e abriremos prazo <strong>não inferior a 5 (cinco) dias
                corridos</strong>, contados do <strong>recebimento</strong> dessa comunicação, para a
            sua manifestação antes da medida definitiva. Respondemos por escrito, com o motivo da
            decisão, em até <strong>5 (cinco) dias úteis</strong>. Havendo urgência, a comunicação e a
            oportunidade de manifestação se dão em seguida à medida, em até
            <strong>2 (dois) dias úteis</strong>, e a medida é reavaliada à luz do que você
            apresentar.</p>
        <p style="margin:8px 0 0;">Em qualquer caso, <strong>valores já devidos por serviços
                efetivamente prestados permanecem devidos</strong> e são pagos, ressalvada a
            compensação da cláusula 7.</p>
    </div>

    <h2 id="p14">14. PROGRAMA DE INDICAÇÃO</h2>
    <p>Você recebe um código próprio e pode ganhar um <strong>bônus percentual sobre a taxa de
            intermediação</strong> que a Plataforma auferir nas transações de cada profissional ou
        estabelecimento indicado por você, durante a janela de apuração. O bônus <strong>não</strong>
        incide sobre o valor pago pelo aluno nem sobre o faturamento do indicado, e
        <strong>nada</strong> é descontado do que o indicado recebe. As regras completas — base de
        cálculo, condições de saque, vedações e tributos — estão na
        <a href="{{ route('termos') }}#s9">cláusula 9 dos Termos gerais</a>, que você declara ter
        lido.</p>
    <div class="highlighted warn">
        <strong>O bônus só vira sacável com duas condições cumulativas:</strong> o encerramento da
        janela de apuração <strong>e</strong> o atingimento, pela conta indicada, do número mínimo de
        alunos com pagamento confirmado. Antes disso o valor exibido é <strong>mera
            expectativa</strong>, não crédito exigível. O bônus é concedido por
        <strong>liberalidade</strong>, não é remuneração, honorário ou comissão de representação, e
        <strong>não cria vínculo</strong> de qualquer natureza com a Plataforma.
    </div>
    <p>Auto-indicação, contas falsas, simulação de alunos ou pagamentos e esquemas coordenados
        implicam <strong>cancelamento do bônus, estorno de valores pagos e suspensão das contas
            envolvidas</strong>, sem prejuízo das medidas legais cabíveis.</p>

    <h2 id="p15">15. CONTATO</h2>
    <ul>
        <li><strong>Atendimento:</strong>
            <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}">{{ \App\Support\Empresa::get('email_suporte') }}</a></li>
        <li><strong>Dados pessoais (DPO):</strong> {{ \App\Support\Empresa::get('dpo_nome') }} —
            <a href="mailto:{{ \App\Support\Empresa::get('dpo_email') }}">{{ \App\Support\Empresa::get('dpo_email') }}</a></li>
    </ul>

    <div class="highlighted">
        <strong>Aceitação:</strong> ao concluir seu cadastro como personal, você declara ser
        profissional habilitado, atuar com autonomia e concordar com estes Termos, com os
        <a href="{{ route('termos') }}">Termos gerais</a> e com a
        <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
