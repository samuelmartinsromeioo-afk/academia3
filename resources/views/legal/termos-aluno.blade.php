@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Aluno')
@section('doc_subtitulo', 'Condições para alunos e usuários da plataforma')
@section('doc_perfil', 'Perfil: Aluno / Usuário')
@section('doc_versao', config('termos.versao'))
@section('nav_aluno', 'active')

@section('doc_conteudo')

    @php
        $pct = fn ($f) => rtrim(rtrim(number_format($f * 100, 2, ',', '.'), '0'), ',') . '%';
        $reais = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
        $taxaLabel = $pct(\App\Services\AsaasService::feeRate());
        $idadeAluno = \App\Support\CadastroHelper::IDADE_MINIMA_ALUNO;

        $exPreco = 100.00;
        $exPix = \App\Services\AsaasService::detalhar($exPreco, 'PIX');
        $exCartao = \App\Services\AsaasService::detalhar($exPreco, 'CREDIT_CARD');
    @endphp

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a
        <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica a você que usa
        o SNR FIT como <strong>aluno</strong>. Em caso de divergência, prevalece este documento.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#a1">1. Quem pode usar</a></li>
            <li><a href="#a2">2. O que você pode fazer</a></li>
            <li><a href="#a3">3. O que você não pode fazer</a></li>
            <li><a href="#a4">4. Anamnese e dados de saúde</a></li>
            <li><a href="#a5">5. Quanto você paga e por quê</a></li>
            <li><a href="#a6">6. Contratação, agenda e cancelamentos</a></li>
            <li><a href="#a7">7. Arrependimento e reembolso</a></li>
            <li><a href="#a8">8. Compras na loja</a></li>
            <li><a href="#a9">9. Com quem você está contratando</a></li>
            <li><a href="#a10">10. Riscos, saúde e isenções</a></li>
            <li><a href="#a11">11. Seus dados (LGPD)</a></li>
            <li><a href="#a12">12. Programa de indicação</a></li>
            <li><a href="#a13">13. Contato e reclamações</a></li>
        </ul>
    </div>

    <h2 id="a1">1. QUEM PODE USAR</h2>
    <ul>
        <li>Idade mínima de <strong>{{ $idadeAluno }} anos</strong> — a Plataforma é destinada a
            <strong>maiores de idade</strong> e o cadastro valida a sua data de nascimento.</li>
        <li>Fornecer dados verdadeiros e mantê-los atualizados, inclusive o e-mail e o telefone, que
            são os canais pelos quais te avisamos.</li>
        <li>Uma conta por pessoa, pessoal e intransferível.</li>
    </ul>

    <h2 id="a2">2. O QUE VOCÊ PODE FAZER</h2>
    <ul>
        <li><strong>Buscar e comparar</strong> personais, academias, studios e lojas — por nome,
            especialidade, modalidade, avaliação e, se você autorizar a localização, por proximidade.</li>
        <li><strong>Ver o preço final antes de contratar</strong>, com a composição discriminada.</li>
        <li><strong>Contratar</strong> aulas, pacotes, planos, fichas e avaliações físicas, e
            <strong>pagar</strong> por PIX ou cartão.</li>
        <li><strong>Agendar, reagendar e cancelar</strong> conforme a política informada pelo
            profissional.</li>
        <li><strong>Registrar e acompanhar</strong> treinos, metas, medidas e fotos de progresso, e
            <strong>exportar</strong> tudo isso.</li>
        <li><strong>Conversar</strong> pelo chat com quem te atende.</li>
        <li><strong>Avaliar</strong> serviços que você efetivamente contratou — inclusive de forma
            negativa; não editamos avaliação legítima.</li>
        <li><strong>Encerrar a conta</strong> e pedir a exclusão dos seus dados, a qualquer momento,
            por conta própria, no app e no site (seção 11).</li>
    </ul>

    <h2 id="a3">3. O QUE VOCÊ NÃO PODE FAZER</h2>
    <ul>
        <li>Publicar <strong>avaliação falsa</strong>, de serviço que não contratou, ofensiva, ou usada
            para extorquir vantagem de quem te atendeu;</li>
        <li>Usar o chat para assédio, insistência indesejada, aproximação de natureza sexual ou
            propaganda;</li>
        <li>Divulgar conversa, dado pessoal, imagem ou informação do profissional sem autorização;</li>
        <li><strong>Combinar pagamento por fora</strong> para burlar a intermediação, quando o contato
            se deu pela Plataforma;</li>
        <li>Contestar no cartão (<em>chargeback</em>) uma cobrança legítima que você usufruiu;</li>
        <li>Criar segunda conta para obter vantagem, burlar bloqueio ou condição do Programa de
            Indicação;</li>
        <li>Prestar informação falsa sobre sua saúde, idade ou identidade.</li>
    </ul>

    <h2 id="a4">4. ANAMNESE E DADOS DE SAÚDE</h2>
    <p>A anamnese (histórico de saúde, lesões, cirurgias, doenças, medicamentos, gestação e restrições)
        é <strong>dado pessoal sensível</strong> (art. 11 da Lei nº 13.709/2018), coletado
        <strong>mediante o seu consentimento específico</strong> e usado exclusivamente para que o
        profissional monte um treino seguro.</p>
    <ul>
        <li>O preenchimento é <strong>opcional</strong>: você pode deixar a anamnese em branco e usar
            a Plataforma normalmente.</li>
        <li>O consentimento é uma <strong>confirmação própria</strong>, que você marca na tela da
            anamnese — no app e no site — e sem a qual nada é gravado. Fica registrado com data,
            hora e IP.</li>
        <li>Você pode <strong>revogar</strong> quando quiser, sem justificar, em
            <strong>Privacidade e meus dados</strong> (no app e no site) ou na própria tela da
            anamnese. A revogação <strong>apaga a anamnese na hora</strong>, você recebe a
            confirmação por escrito e <strong>a sua conta continua ativa</strong> — você segue
            treinando e contratando. <strong>Não precisa excluir a conta</strong> para remover um
            dado de saúde.</li>
        <li>Quem os vê é apenas o profissional que te atende (e a equipe técnica da academia/studio, se
            for o caso). <strong>Nunca</strong> são usados para publicidade nem enviados a
            ferramentas de marketing.</li>
        <li>Você é responsável pela <strong>veracidade e completude</strong> do que informa.</li>
    </ul>
    <div class="highlighted warn">
        <strong>Omitir condição de saúde relevante</strong> compromete a sua segurança e
        <strong>afasta a responsabilidade</strong> da Plataforma e do profissional pelas consequências
        decorrentes da omissão.
    </div>

    <h2 id="a5">5. QUANTO VOCÊ PAGA E POR QUÊ</h2>
    <div class="highlighted">
        <p>O valor que você paga tem <strong>duas partes</strong>, e as duas aparecem na tela antes de
            você confirmar:</p>
        <ul style="margin:8px 0;">
            <li>o <strong>preço do serviço</strong>, definido pelo profissional, que vai
                <strong>integralmente</strong> para ele;</li>
            <li>a <strong>taxa de serviço da SNR FIT</strong> ({{ $taxaLabel }}), que é a nossa
                remuneração por manter a plataforma, a busca, a agenda, o pagamento e o suporte.</li>
        </ul>
        <p style="margin-bottom:0;">Exemplo com os valores vigentes nesta data, num serviço de
            {{ $reais($exPix['base']) }} pago por <strong>PIX</strong>: você paga
            <strong>{{ $reais($exPix['total']) }}</strong> ({{ $reais($exPix['base']) }} +
            {{ $reais($exPix['comissao']) }} de taxa).</p>
    </div>
    <ul>
        <li>No <strong>cartão</strong>, a tarifa cobrada pela operadora é somada ao total, porque é um
            meio de pagamento mais caro — e aparece discriminada antes da confirmação. No mesmo
            exemplo, no cartão o total seria {{ $reais($exCartao['total']) }}, dos quais
            {{ $reais($exCartao['gateway']) }} são a tarifa do cartão. <strong>Pagando por PIX você
                não paga essa tarifa.</strong></li>
        <li>A taxa da SNR FIT <strong>não é descontada do profissional</strong>: ele recebe o preço
            que anunciou, por inteiro.</li>
        <li>Pagamentos são processados pela <strong>{{ \App\Support\Empresa::get('processador_pagamentos') }}</strong>.
            A Plataforma <strong>não armazena os dados completos do seu cartão</strong>.</li>
        <li>Planos e pacotes recorrentes se renovam na periodicidade contratada
            <strong>até que você cancele</strong>.</li>
    </ul>

    <h2 id="a6">6. CONTRATAÇÃO, AGENDA E CANCELAMENTOS</h2>
    <ul>
        <li>A execução do serviço é do <strong>profissional ou estabelecimento</strong> contratado; a
            Plataforma intermedeia, agenda e viabiliza o pagamento.</li>
        <li>Cada profissional pode ter <strong>política própria de cancelamento e
                reagendamento</strong>, com prazo mínimo de antecedência, informada antes da
            contratação.</li>
        <li>Falta sem aviso (<em>no-show</em>) pode não gerar reembolso, conforme a política
            informada.</li>
        <li><strong>Cancelamento de assinatura:</strong> pelo mesmo meio eletrônico em que você
            contratou, sem ligar, justificar ou falar com atendente. <strong>No app:</strong>
            <em>Meus pagamentos → Minhas assinaturas → Cancelar assinatura</em>.
            <strong>No site:</strong> no painel do aluno, na própria assinatura. O efeito é
            <strong>imediato</strong>, e você recebe a <strong>confirmação por escrito</strong> —
            guarde-a como comprovante. Se cancelou e não recebeu a confirmação, trate como não
            cancelado e fale conosco.</li>
        <li><strong>Cancelar não é pedir dinheiro de volta.</strong> Cancelar interrompe as
            cobranças futuras e o período já pago continua valendo até o fim. Se você quer o valor
            devolvido, o caminho é o <strong>arrependimento</strong> (seção 7), nos primeiros 7 dias
            corridos, ou a reclamação por serviço mal prestado.</li>
    </ul>
    <div class="highlighted">
        Política que imponha <strong>perda total do valor</strong> por cancelamento feito com
        antecedência razoável é <strong>abusiva</strong> (art. 51 do Código de Defesa do Consumidor) e
        não será sustentada pela Plataforma. Se encontrar uma, fale conosco.
    </div>

    <h2 id="a7">7. ARREPENDIMENTO E REEMBOLSO</h2>
    <div class="highlighted">
        <p style="margin:0;"><strong>Direito de arrependimento (art. 49 do CDC).</strong> Como a
            contratação é feita à distância, você pode <strong>desistir em até 7 (sete) dias
                corridos</strong>, contados da contratação ou do recebimento do produto,
            <strong>sem justificar e sem multa</strong>.</p>
        <p style="margin:8px 0 0;"><strong>O reembolso é de tudo o que você pagou</strong> — inclusive
            a taxa de serviço da SNR FIT e a tarifa do cartão que você arcou. No exemplo acima, quem
            pagou {{ $reais($exPix['total']) }} recebe {{ $reais($exPix['total']) }} de volta.</p>
    </div>
    <ul>
        <li><strong>Como exercer:</strong> escreva para
            <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}">{{ \App\Support\Empresa::get('email_suporte') }}</a>
            ou use o chat com quem você contratou, <strong>dentro dos 7 dias corridos</strong>,
            dizendo o que contratou e a data. Não precisa de justificativa nem formulário. Nós
            respondemos por escrito em até <strong>5 dias úteis</strong> e ordenamos a devolução em
            até <strong>10 dias corridos</strong> da confirmação.</li>
        <li><strong>Como os 7 dias contam:</strong> em dias <strong>corridos</strong>, incluindo fim
            de semana e feriado; o dia da contratação (ou do recebimento do produto) não conta e o
            último dia conta. Se o último dia cair em sábado, domingo ou feriado nacional, você tem
            até o <strong>primeiro dia útil seguinte</strong>
            (<a href="{{ route('termos') }}#s1-4">cláusula 1.4 dos Termos gerais</a>).</li>
        <li>Serviço <strong>já prestado</strong> a seu pedido dentro dos 7 dias (por exemplo, uma
            sessão que você já fez) pode ser descontado na proporção do que foi usufruído — nunca o
            valor todo.</li>
        <li>Reembolsos voltam pelo <strong>mesmo meio de pagamento</strong>. O
            <strong>crédito efetivo</strong> depende do meio: poucos dias no PIX e
            <strong>até duas faturas</strong> no cartão de crédito, conforme o emissor. Esse prazo
            final é do processador e dos bancos, não nosso.</li>
        <li><strong>Serviço mal prestado ou produto com defeito</strong> seguem o CDC: você pode exigir
            reexecução, restituição ou abatimento (art. 20), nos prazos do art. 26. A
            responsabilidade é de quem prestou ou vendeu; a Plataforma medeia e pode antecipar a
            solução.</li>
    </ul>

    <h2 id="a8">8. COMPRAS NA LOJA</h2>
    <ul>
        <li>Produtos são anunciados e vendidos por <strong>lojistas</strong>, responsáveis por
            descrição, qualidade, validade, disponibilidade, prazo e entrega.</li>
        <li>No carrinho, a taxa de serviço aparece como <strong>linha própria</strong>, somada ao total
            dos produtos.</li>
        <li>Trocas e devoluções por vício ou defeito seguem o Código de Defesa do Consumidor.</li>
        <li>Trate primeiro com o lojista; a Plataforma pode auxiliar na mediação.</li>
        <li><strong>Suplemento alimentar não é medicamento.</strong> Nenhum produto anunciado promete
            cura, tratamento ou resultado, e seu uso deve ser orientado por profissional habilitado.</li>
    </ul>

    <h2 id="a9">9. COM QUEM VOCÊ ESTÁ CONTRATANDO</h2>
    <p>A qualificação e a conduta dos profissionais são <strong>declaradas por eles próprios</strong>.
        A Plataforma confere o cadastro e pode exigir documentos, mas essa aprovação
        <strong>não é auditoria, certificação ou endosso</strong> e <strong>não garante</strong>
        habilitação, competência ou resultado. Avalie o perfil, as avaliações e converse antes de
        contratar; se algo parecer irregular, denuncie pelo nosso canal.</p>
    <p>A Plataforma <strong>não é parte</strong> do contrato entre você e o profissional quanto ao
        serviço prestado — ela responde pelo serviço que ela mesma presta (a tecnologia, a cobrança
        correta e o reembolso), conforme a cláusula 12 dos
        <a href="{{ route('termos') }}#s12">Termos gerais</a>.</p>

    <h2 id="a10">10. RISCOS, SAÚDE E ISENÇÕES</h2>
    <div class="highlighted warn">
        A atividade física tem <strong>risco inerente</strong>, inclusive de lesão.
        <strong>Consulte um médico antes de começar.</strong> A Plataforma não conduz, não supervisiona
        e não fiscaliza a execução dos treinos nem as condições do espaço físico, e
        <strong>não responde</strong> por lesões, danos ou acidentes decorrentes da execução, que são
        de responsabilidade do profissional ou estabelecimento e, no que couber, do praticante.
        Nenhum profissional da Plataforma presta orientação médica, diagnóstico, prescrição de
        medicamento ou prescrição dietética.
    </div>

    <h2 id="a11">11. SEUS DADOS (LGPD)</h2>
    <p>Tratamos seus dados conforme a <a href="{{ route('lgpd.politica') }}">Política de
            Privacidade</a>, que detalha finalidade, base legal e prazo de guarda de cada dado.</p>
    <p>Você exerce os seus direitos <strong>por conta própria</strong>, na página
        <strong>"Privacidade e meus dados"</strong> — no app, em
        <em>Configurações → Privacidade e meus dados</em>; no site, pelo menu da sua conta:</p>
    <ul>
        <li><strong>Exportar:</strong> cópia completa em JSON — baixada na hora no site, enviada por
            e-mail em anexo no app;</li>
        <li><strong>Revogar</strong> o consentimento de dados de saúde (apaga a anamnese, mantém a
            conta) e a escolha sobre cookies de medição;</li>
        <li><strong>Excluir a conta</strong>, com confirmação por escrito;</li>
        <li><strong>Corrigir</strong> um dado, em <em>Editar perfil</em>.</li>
    </ul>
    <p>Qualquer outro pedido vai ao Encarregado
        (<a href="mailto:{{ \App\Support\Empresa::get('dpo_email') }}">{{ \App\Support\Empresa::get('dpo_email') }}</a>),
        respondido em até <strong>15 dias corridos</strong> do recebimento. O caminho e o prazo de
        cada direito estão na
        <a href="{{ route('termos') }}#s12-1">cláusula 12.1 dos Termos gerais</a>. Você pode também
        peticionar à <strong>ANPD</strong>.</p>

    <h2 id="a12">12. PROGRAMA DE INDICAÇÃO</h2>
    <p>Você recebe um código de indicação e pode informar o código de quem o indicou
        <strong>no momento do seu cadastro</strong>. As regras completas estão na
        <a href="{{ route('termos') }}#s9">cláusula 9 dos Termos gerais</a>.</p>
    <div class="highlighted warn">
        <strong>Atenção:</strong> a indicação de <strong>outro aluno</strong> fica no histórico, mas
        <strong>não gera bônus</strong>. O Programa remunera apenas a indicação de
        <strong>profissionais e estabelecimentos</strong> que venham a transacionar na Plataforma, e o
        bônus pertence a quem indicou — não a você, por ter se cadastrado com um código.
    </div>
    <p>Informar o código de alguém <strong>não altera nenhum valor que você paga</strong> e não lhe dá
        desconto, crédito ou benefício financeiro. Ao usar um código, você fica ciente de que
        <strong>seu nome e a situação da indicação</strong> serão exibidos a quem o indicou,
        exclusivamente para a apuração do bônus dessa pessoa — <strong>sem</strong> seus dados de
        contato, documentos ou qualquer informação financeira ou de saúde.</p>

    <h2 id="a13">13. CONTATO E RECLAMAÇÕES</h2>
    <ul>
        <li><strong>Atendimento:</strong>
            <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}">{{ \App\Support\Empresa::get('email_suporte') }}</a></li>
        <li><strong>Dados pessoais (DPO):</strong> {{ \App\Support\Empresa::get('dpo_nome') }} —
            <a href="mailto:{{ \App\Support\Empresa::get('dpo_email') }}">{{ \App\Support\Empresa::get('dpo_email') }}</a></li>
        <li>Você pode recorrer a <strong>órgãos de defesa do consumidor</strong> e à plataforma
            <a href="https://www.consumidor.gov.br" target="_blank" rel="noopener">consumidor.gov.br</a>.
            Como consumidor, o <strong>foro do seu domicílio</strong> é assegurado (art. 101, I, do
            CDC).</li>
    </ul>

    <div class="highlighted">
        <strong>Aceitação:</strong> ao concluir seu cadastro como aluno, você confirma que leu e
        concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a
        <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
