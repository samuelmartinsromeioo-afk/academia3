@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Studio')
@section('doc_subtitulo', 'Condições para studios parceiros')
@section('doc_perfil', 'Perfil: Studio')
@section('doc_versao', config('termos.versao'))
@section('nav_studio', 'active')

@section('doc_conteudo')

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica ao estabelecimento cadastrado como <strong>studio</strong>.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#st1">1. Cadastro, aprovação e representação</a></li>
            <li><a href="#st2">2. Planos, aulas e agenda de horários</a></li>
            <li><a href="#st3">3. Reservas, lotação e cancelamentos</a></li>
            <li><a href="#st4">4. Profissionais vinculados</a></li>
            <li><a href="#st5">5. Pagamentos, split e repasses</a></li>
            <li><a href="#st6">6. Studio como controlador de dados (LGPD)</a></li>
            <li><a href="#st7">7. Responsabilidades pelo estabelecimento</a></li>
            <li><a href="#st8">8. Condutas vedadas e indenização</a></li>
            <li><a href="#st9">9. Suspensão e encerramento</a></li>
            <li><a href="#st10">10. Programa de indicação</a></li>
            <li><a href="#st11">11. Contato</a></li>
        </ul>
    </div>

    <h2 id="st1">1. CADASTRO, APROVAÇÃO E REPRESENTAÇÃO</h2>
    <ul>
        <li>O cadastro exige dados válidos do estabelecimento e passa por <strong>análise e aprovação</strong> da Plataforma antes da liberação. A aprovação é <strong>conferência cadastral</strong>, não auditoria nem certificação de qualidade, e não transfere à Plataforma responsabilidade pela operação do estabelecimento.</li>
        <li>Quem cadastra declara ter <strong>poderes para representar</strong> o studio e assumir estas obrigações.</li>
        <li>Informações de perfil, estrutura, aulas e planos devem ser verídicas e atualizadas.</li>
    </ul>

    <h2 id="st2">2. PLANOS, AULAS E AGENDA DE HORÁRIOS</h2>
    <ul>
        <li>O studio define planos, aulas e a <strong>grade de horários</strong> disponibilizada aos alunos.</li>
        <li>Valores e condições exibidos devem ser claros e cumpridos (boa-fé e CDC).</li>
        <li>O studio é responsável por manter os horários e a disponibilidade atualizados, evitando overbooking.</li>
    </ul>

    <h2 id="st3">3. RESERVAS, LOTAÇÃO E CANCELAMENTOS</h2>
    <ul>
        <li>As reservas respeitam a lotação e as regras de cada aula/horário definidas pelo studio.</li>
        <li>A política de cancelamento/reagendamento deve ser informada ao aluno e ser compatível com o CDC.</li>
        <li>Bloqueios de horário e indisponibilidades devem ser mantidos atualizados pelo studio.</li>
    </ul>

    <h2 id="st4">4. PROFISSIONAIS VINCULADOS</h2>
    <p>O studio é responsável por verificar a <strong>habilitação</strong> (inclusive CREF, quando exigido) dos profissionais que atuam em seu espaço e por sua conduta. A vinculação na Plataforma não transfere essa responsabilidade a ela.</p>

    <h2 id="st5">5. PAGAMENTOS, SPLIT E REPASSES</h2>
    <ul>
        <li>Pagamentos são processados pela <strong>Asaas</strong> com <strong>split</strong>: <strong>o preço definido pelo studio é repassado integralmente</strong> à sua subconta, e a taxa de intermediação da Plataforma é <strong>somada ao valor pago pelo aluno</strong>, não descontada do studio. Nas transações com cartão, as tarifas do processador também são acrescidas ao valor do aluno.</li>
        <li>Para receber, é necessário concluir o cadastro/verificação na Asaas. <strong>Prazos de liquidação, retenções e bloqueios seguem as regras do processador</strong>, não da Plataforma.</li>
        <li><strong>Reembolso ao aluno.</strong> Quando o aluno exerce o direito de arrependimento
            (7 dias <strong>corridos</strong>, art. 49 do CDC), obtém reembolso por vício do serviço,
            ou quando há <em>chargeback</em> reconhecido pelo emissor do cartão: o aluno recebe de
            volta tudo o que pagou, a Plataforma <strong>devolve a própria taxa de
                intermediação</strong> e o valor já repassado é <strong>recomposto por compensação em
                repasses futuros</strong> ou, não havendo saldo, por cobrança direta. A parcela do
            serviço <strong>já efetivamente prestada</strong> a pedido do aluno é preservada em favor
            do estabelecimento.<br>
            A Plataforma informa o studio <strong>em até 2 (dois) dias úteis</strong> do pedido e,
            havendo controvérsia sobre a execução, abre prazo <strong>não inferior a 5 (cinco) dias
                corridos</strong> do recebimento da comunicação para a sua manifestação antes de
            decidir a recomposição — salvo quando a devolução for imposta por lei, pelo emissor do
            cartão ou por autoridade.</li>
        <li><strong>Repasse sem subconta.</strong> Enquanto o studio não tiver subconta habilitada no
            processador, o valor fica na conta da Plataforma e é repassado mediante solicitação de
            saque no painel, paga em até <strong>5 (cinco) dias úteis</strong> da solicitação
            regular (chave PIX válida de titularidade do studio e cadastro sem pendência).</li>
        <li><strong>Notas fiscais.</strong> O estabelecimento emite o documento fiscal do seu serviço
            ou produto ao aluno, <strong>pelo valor do seu próprio preço</strong> — não pelo valor
            final pago por ele, já que a taxa de intermediação não é receita do estabelecimento. A
            Plataforma emite o documento fiscal da taxa de intermediação e <strong>não é substituta
            tributária</strong> do estabelecimento.</li>
    </ul>

    <h2 id="st6">6. STUDIO COMO CONTROLADOR DE DADOS (LGPD)</h2>
    <div class="highlighted">
        Ao tratar dados dos seus alunos (cadastro, reservas, anamnese/avaliações quando aplicável), o studio atua como <strong>controlador</strong> e a Plataforma como <strong>operadora</strong> nessa parte. O studio obriga-se a possuir base legal, coletar <strong>consentimento</strong> para dados sensíveis, usar os dados apenas para as finalidades informadas, mantê-los em sigilo e atender aos titulares e à ANPD.
    </div>

    <h2 id="st7">7. RESPONSABILIDADES PELO ESTABELECIMENTO</h2>
    <ul>
        <li>Manter alvarás, licenças, seguros e condições de segurança e higiene do espaço e equipamentos.</li>
        <li>Responder por acidentes, lesões e danos ocorridos em suas dependências ou na execução das aulas.</li>
        <li>Cumprir a legislação sanitária, trabalhista, tributária e de acessibilidade aplicável.</li>
    </ul>

    <h2 id="st8">8. CONDUTAS VEDADAS E INDENIZAÇÃO</h2>
    <p>São vedados: informações falsas sobre estrutura/planos, vinculação de profissionais sem habilitação, burla da intermediação e uso indevido de dados de alunos. O studio concorda em <strong>defender, isentar e indenizar</strong> a Plataforma por demandas decorrentes da sua operação, do tratamento de dados sob sua responsabilidade ou da violação destes Termos. Aplica-se a limitação dos <a href="{{ route('termos') }}">Termos gerais</a>.</p>

    <h2 id="st9">9. SUSPENSÃO E ENCERRAMENTO</h2>
    <p>Podemos suspender ou encerrar o perfil por violação destes Termos, inadimplência, reclamações graves, fraude ou risco a alunos, sem prejuízo de valores devidos.</p>
    <div class="highlighted">
        <strong>Devido processo.</strong> Salvo risco iminente a pessoas, fraude em curso, ordem de
        autoridade ou exigência legal, comunicamos o motivo e abrimos prazo
        <strong>não inferior a 5 (cinco) dias corridos</strong>, contados do recebimento da
        comunicação, para o studio se manifestar antes da medida definitiva; respondemos por escrito
        em até <strong>5 (cinco) dias úteis</strong>. Havendo urgência, comunicação e manifestação se
        dão em seguida à medida, em até <strong>2 (dois) dias úteis</strong>. Nas duas situações,
        <strong>valores já devidos por serviços prestados permanecem devidos</strong> e são pagos, e
        alunos com plano vigente são informados em até 2 dias úteis. Ver a
        <a href="{{ route('termos') }}#s16">cláusula 16 dos Termos gerais</a>.
    </div>
    <h2 id="st9-1">9.1. Encerramento pelo studio</h2>
    <p>O studio pode encerrar a conta a qualquer momento, sem multa e sem aviso prévio, em
        <em>Privacidade e meus dados</em> (no app e no site), confirmando a senha. O efeito é
        <strong>imediato e definitivo</strong> — sem carência e sem reativação —, com confirmação por
        escrito enviada antes da eliminação dos dados. Pelo mesmo caminho o studio exporta e corrige
        os seus dados (<a href="{{ route('termos') }}#s12-1">cláusula 12.1</a>).</p>

    <h2 id="st10">10. PROGRAMA DE INDICAÇÃO</h2>
    <p>Você recebe um código de indicação próprio e pode ganhar um <strong>bônus percentual sobre a comissão da Plataforma</strong> nas transações de cada estabelecimento indicado por você, durante a janela de apuração. O bônus <strong>não</strong> incide sobre o valor pago pelo aluno nem sobre o faturamento do indicado, e <strong>nada</strong> é descontado do que o indicado recebe. As regras completas, a definição exata da base de cálculo, as condições de saque e as vedações estão na <a href="{{ route('termos') }}#s9">cláusula 9 dos Termos gerais</a>, que você declara ter lido.</p>
    <div class="highlighted warn">
        <strong>O bônus só vira sacável com duas condições cumulativas:</strong> o encerramento da janela de apuração <strong>e</strong> o atingimento, pela conta indicada, do número mínimo de alunos com pagamento confirmado. Antes disso o valor exibido é <strong>mera expectativa</strong>, não crédito exigível. O bônus é concedido por <strong>liberalidade</strong>, não é remuneração e <strong>não cria vínculo</strong> de qualquer natureza com a Plataforma.
    </div>
    <p>Auto-indicação, contas falsas, simulação de alunos ou pagamentos e esquemas coordenados para atingir a meta implicam <strong>cancelamento do bônus, estorno de valores pagos e suspensão das contas envolvidas</strong>, sem prejuízo das medidas legais cabíveis.</p>

    <h2 id="st11">11. CONTATO</h2>
    <ul>
        <li><strong>Atendimento:</strong> <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}">{{ \App\Support\Empresa::get('email_suporte') }}</a></li>
        <li><strong>Dados pessoais (DPO):</strong> {{ \App\Support\Empresa::get('dpo_nome') }} — <a href="mailto:{{ \App\Support\Empresa::get('dpo_email') }}">{{ \App\Support\Empresa::get('dpo_email') }}</a></li>
    </ul>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir o cadastro do studio, o representante declara ter poderes para tanto e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
