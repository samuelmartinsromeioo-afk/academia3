@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Academia')
@section('doc_subtitulo', 'Condições para academias parceiras')
@section('doc_perfil', 'Perfil: Academia')
@section('doc_versao', config('termos.versao'))
@section('nav_academia', 'active')

@section('doc_conteudo')

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica ao estabelecimento cadastrado como <strong>academia</strong>.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#ac1">1. Cadastro, aprovação e representação</a></li>
            <li><a href="#ac2">2. Modelo comercial — não há mensalidade</a></li>
            <li><a href="#ac3">3. Gestão de alunos, planos e filiais</a></li>
            <li><a href="#ac4">4. Professores e profissionais vinculados</a></li>
            <li><a href="#ac5">5. Pagamentos, split e repasses</a></li>
            <li><a href="#ac6">6. Academia como controladora de dados (LGPD)</a></li>
            <li><a href="#ac7">7. Responsabilidades pelo estabelecimento</a></li>
            <li><a href="#ac8">8. Condutas vedadas</a></li>
            <li><a href="#ac9">9. Indenização e limitação</a></li>
            <li><a href="#ac10">10. Suspensão e encerramento</a></li>
            <li><a href="#ac11">11. Programa de indicação</a></li>
            <li><a href="#ac12">12. Contato</a></li>
        </ul>
    </div>

    <h2 id="ac1">1. CADASTRO, APROVAÇÃO E REPRESENTAÇÃO</h2>
    <ul>
        <li>O cadastro exige dados válidos do estabelecimento (incluindo <strong>CNPJ</strong>) e passa por <strong>análise e aprovação</strong> da Plataforma antes da liberação do acesso. A aprovação é <strong>conferência cadastral</strong>, não auditoria nem certificação de qualidade, e não transfere à Plataforma responsabilidade pela operação do estabelecimento.</li>
        <li>Quem realiza o cadastro declara ter <strong>poderes para representar</strong> a academia e assumir estas obrigações em nome dela.</li>
        <li>As informações do perfil (endereço, estrutura, aulas, planos) devem ser verídicas e mantidas atualizadas.</li>
    </ul>

    <h2 id="ac2">2. MODELO COMERCIAL — NÃO HÁ MENSALIDADE</h2>
    @php
        $pct = fn ($f) => rtrim(rtrim(number_format($f * 100, 2, ',', '.'), '0'), ',') . '%';
        $taxaLabel = $pct(\App\Services\AsaasService::feeRate());
    @endphp
    <div class="highlighted">
        <strong>A academia não paga mensalidade, taxa de adesão, aluguel de software ou qualquer
            valor fixo à Plataforma.</strong> A única remuneração da SNR FIT é a
        <strong>taxa de intermediação de {{ $taxaLabel }}</strong>, que é
        <strong>somada ao valor pago pelo aluno</strong> — e não descontada da academia (cláusula 5).
        Não havendo transação, não há nada a pagar.
    </div>
    <ul>
        <li>A taxa vigente é informada na Plataforma e aplicada de forma igual a todos os perfis do
            mesmo tipo; <strong>não há tabela negociada individualmente</strong>.</li>
        <li>Alteração da taxa segue a cláusula 19 dos <a href="{{ route('termos') }}#s19">Termos
                gerais</a>: aviso prévio de <strong>30 (trinta) dias corridos</strong> contados do
            envio da comunicação, com direito de encerrar a conta sem penalidade até a data de
            vigência se a academia não concordar. <strong>Sem o aviso prévio cumprido, a taxa nova
                não é exigível</strong> da academia no período.</li>
    </ul>
    <p class="muted">A redação anterior desta cláusula mencionava a possibilidade de "valor mensal"
        cobrado da academia. Isso nunca existiu na operação e foi removido — uma cláusula que autoriza
        cobrança inexistente só serve para gerar insegurança e discussão.</p>

    <h2 id="ac3">3. GESTÃO DE ALUNOS, PLANOS E FILIAIS</h2>
    <ul>
        <li>A academia é responsável pelas informações de <strong>planos, aulas, horários e filiais</strong> que cadastra e divulga.</li>
        <li>Ao cadastrar ou gerenciar dados de seus próprios alunos, a academia declara ter <strong>base legal</strong> para isso e responde por essa relação.</li>
        <li>Ofertas, valores e condições exibidos aos alunos devem ser claros e cumpridos (boa-fé e CDC).</li>
    </ul>

    <h2 id="ac4">4. PROFESSORES E PROFISSIONAIS VINCULADOS</h2>
    <ul>
        <li>A academia é responsável por conferir a <strong>habilitação</strong> (inclusive CREF, quando exigido) dos profissionais que vincula e por sua atuação dentro do estabelecimento.</li>
        <li>A vinculação de um profissional na Plataforma não transfere à Plataforma qualquer responsabilidade sobre a relação entre a academia e esse profissional.</li>
    </ul>

    <h2 id="ac5">5. PAGAMENTOS, SPLIT E REPASSES</h2>
    <ul>
        <li>Pagamentos de alunos são processados pela <strong>Asaas</strong> com <strong>split</strong>: <strong>a mensalidade ou o plano definido pela academia é repassado integralmente</strong> à sua subconta, e a taxa de intermediação da Plataforma é <strong>somada ao valor pago pelo aluno</strong>, não descontada da academia. Nas transações com cartão, as tarifas do processador também são acrescidas ao valor do aluno.</li>
        <li>Para receber, é necessário concluir o cadastro/verificação na Asaas. <strong>Prazos de liquidação, retenções e bloqueios seguem as regras do processador</strong>, não da Plataforma.</li>
        <li><strong>Reembolso ao aluno.</strong> Quando o aluno exerce o direito de arrependimento
            (7 dias <strong>corridos</strong>, art. 49 do CDC), obtém reembolso por vício do serviço,
            ou quando há <em>chargeback</em> reconhecido pelo emissor do cartão: o aluno recebe de
            volta tudo o que pagou, a Plataforma <strong>devolve a própria taxa de
                intermediação</strong> e o valor já repassado é <strong>recomposto por compensação em
                repasses futuros</strong> ou, não havendo saldo, por cobrança direta. A parcela do
            serviço <strong>já efetivamente prestada</strong> a pedido do aluno é preservada em favor
            do estabelecimento.<br>
            A Plataforma informa a academia <strong>em até 2 (dois) dias úteis</strong> do pedido e,
            havendo controvérsia sobre a execução, abre prazo <strong>não inferior a 5 (cinco) dias
                corridos</strong> do recebimento da comunicação para a sua manifestação antes de
            decidir a recomposição — salvo quando a devolução for imposta por lei, pelo emissor do
            cartão ou por autoridade.</li>
        <li><strong>Repasse sem subconta.</strong> Enquanto a academia não tiver subconta habilitada
            no processador, o valor fica na conta da Plataforma e é repassado mediante solicitação de
            saque no painel, paga em até <strong>5 (cinco) dias úteis</strong> da solicitação
            regular (chave PIX válida de titularidade da academia e cadastro sem pendência).</li>
        <li><strong>Notas fiscais.</strong> O estabelecimento emite o documento fiscal do seu serviço
            ou produto ao aluno, <strong>pelo valor do seu próprio preço</strong> — não pelo valor
            final pago por ele, já que a taxa de intermediação não é receita do estabelecimento. A
            Plataforma emite o documento fiscal da taxa de intermediação e <strong>não é substituta
            tributária</strong> do estabelecimento.</li>
    </ul>

    <h2 id="ac6">6. ACADEMIA COMO CONTROLADORA DE DADOS (LGPD)</h2>
    <div class="highlighted">
        Ao tratar dados dos seus próprios alunos (cadastro, anamnese, avaliações físicas, frequência), a academia atua como <strong>controladora</strong> nos termos da LGPD e a Plataforma como <strong>operadora</strong> nessa parte. A academia obriga-se a: possuir base legal e coletar <strong>consentimento</strong> para dados sensíveis de saúde; usar os dados apenas para as finalidades informadas; mantê-los em sigilo; e atender às requisições dos titulares e da ANPD. A academia é responsável perante seus alunos por esse tratamento.
    </div>

    <h2 id="ac7">7. RESPONSABILIDADES PELO ESTABELECIMENTO</h2>
    <ul>
        <li>Manter alvarás, licenças, seguros e condições de segurança e higiene do espaço físico e dos equipamentos.</li>
        <li>Responder por acidentes, lesões e danos ocorridos em suas dependências ou na execução dos serviços.</li>
        <li>Cumprir a legislação sanitária, trabalhista, tributária e de acessibilidade aplicável.</li>
    </ul>

    <h2 id="ac8">8. CONDUTAS VEDADAS</h2>
    <ul>
        <li>Divulgar informações falsas sobre estrutura, planos ou preços;</li>
        <li>Vincular profissionais sem habilitação;</li>
        <li>Burlar a intermediação/split para deixar de pagar taxas devidas;</li>
        <li>Usar dados de alunos para finalidades não autorizadas ou repassá-los indevidamente.</li>
    </ul>

    <h2 id="ac9">9. INDENIZAÇÃO E LIMITAÇÃO</h2>
    <p>A academia concorda em <strong>defender, isentar e indenizar</strong> a Plataforma por demandas de alunos, profissionais, autoridades ou terceiros decorrentes da sua operação, do tratamento de dados sob sua responsabilidade ou da violação destes Termos. Aplica-se a limitação de responsabilidade dos <a href="{{ route('termos') }}">Termos gerais</a>.</p>

    <h2 id="ac10">10. SUSPENSÃO E ENCERRAMENTO</h2>
    <p>Podemos suspender ou encerrar o perfil por violação destes Termos, inadimplência, reclamações graves, fraude ou risco a alunos, sem prejuízo de valores devidos e das apurações cabíveis.</p>
    <div class="highlighted">
        <strong>Devido processo.</strong> Salvo risco iminente a pessoas, fraude em curso, ordem de
        autoridade ou exigência legal, comunicamos o motivo e abrimos prazo
        <strong>não inferior a 5 (cinco) dias corridos</strong>, contados do recebimento da
        comunicação, para a academia se manifestar antes da medida definitiva; respondemos por
        escrito em até <strong>5 (cinco) dias úteis</strong>. Havendo urgência, comunicação e
        manifestação se dão em seguida à medida, em até <strong>2 (dois) dias úteis</strong>. Nas
        duas situações, <strong>valores já devidos por serviços prestados permanecem devidos</strong>
        e são pagos, e alunos com plano vigente são informados em até 2 dias úteis. Ver a
        <a href="{{ route('termos') }}#s16">cláusula 16 dos Termos gerais</a>.
    </div>
    <h2 id="ac10-1">10.1. Encerramento pela academia</h2>
    <p>A academia pode encerrar a conta a qualquer momento, sem multa e sem aviso prévio, em
        <em>Privacidade e meus dados</em> (no app e no site), confirmando a senha. O efeito é
        <strong>imediato e definitivo</strong> — sem carência e sem reativação —, com confirmação por
        escrito enviada antes da eliminação dos dados. Pelo mesmo caminho a academia exporta e
        corrige os seus dados (<a href="{{ route('termos') }}#s12-1">cláusula 12.1</a>).</p>

    <h2 id="ac11">11. PROGRAMA DE INDICAÇÃO</h2>
    <p>Você recebe um código de indicação próprio e pode ganhar um <strong>bônus percentual sobre a comissão da Plataforma</strong> nas transações de cada estabelecimento indicado por você, durante a janela de apuração. O bônus <strong>não</strong> incide sobre o valor pago pelo aluno nem sobre o faturamento do indicado, e <strong>nada</strong> é descontado do que o indicado recebe. As regras completas, a definição exata da base de cálculo, as condições de saque e as vedações estão na <a href="{{ route('termos') }}#s9">cláusula 9 dos Termos gerais</a>, que você declara ter lido.</p>
    <div class="highlighted warn">
        <strong>O bônus só vira sacável com duas condições cumulativas:</strong> o encerramento da janela de apuração <strong>e</strong> o atingimento, pela conta indicada, do número mínimo de alunos com pagamento confirmado. Antes disso o valor exibido é <strong>mera expectativa</strong>, não crédito exigível. O bônus é concedido por <strong>liberalidade</strong>, não é remuneração e <strong>não cria vínculo</strong> de qualquer natureza com a Plataforma.
    </div>
    <p>Auto-indicação, contas falsas, simulação de alunos ou pagamentos e esquemas coordenados para atingir a meta implicam <strong>cancelamento do bônus, estorno de valores pagos e suspensão das contas envolvidas</strong>, sem prejuízo das medidas legais cabíveis.</p>

    <h2 id="ac12">12. CONTATO</h2>
    <ul>
        <li><strong>Atendimento:</strong> <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}">{{ \App\Support\Empresa::get('email_suporte') }}</a></li>
        <li><strong>Dados pessoais (DPO):</strong> {{ \App\Support\Empresa::get('dpo_nome') }} — <a href="mailto:{{ \App\Support\Empresa::get('dpo_email') }}">{{ \App\Support\Empresa::get('dpo_email') }}</a></li>
    </ul>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir o cadastro da academia, o representante declara ter poderes para tanto e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
