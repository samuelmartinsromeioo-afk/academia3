@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Personal Trainer')
@section('doc_subtitulo', 'Condições para profissionais de educação física')
@section('doc_perfil', 'Perfil: Personal Trainer')
@section('doc_versao', '2.0')
@section('nav_personal', 'active')

@section('doc_conteudo')

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica a você que atua no SNR FIT como <strong>personal trainer</strong>.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#p1">1. Elegibilidade e aprovação</a></li>
            <li><a href="#p2">2. Qualificação profissional e CREF</a></li>
            <li><a href="#p3">3. Natureza da relação (autonomia)</a></li>
            <li><a href="#p4">4. Suas obrigações profissionais</a></li>
            <li><a href="#p5">5. Preços, split e repasses</a></li>
            <li><a href="#p6">6. Agenda, cancelamentos e no-show</a></li>
            <li><a href="#p7">7. Dados de saúde dos alunos (LGPD)</a></li>
            <li><a href="#p8">8. Condutas vedadas</a></li>
            <li><a href="#p9">9. Responsabilidade e indenização</a></li>
            <li><a href="#p10">10. Suspensão e descredenciamento</a></li>
            <li><a href="#p11">11. Contato</a></li>
        </ul>
    </div>

    <h2 id="p1">1. ELEGIBILIDADE E APROVAÇÃO</h2>
    <ul>
        <li>Idade mínima de <strong>18 anos</strong> e CPF ou CNPJ válido.</li>
        <li>O cadastro passa por <strong>análise e aprovação</strong> da Plataforma antes de liberar o acesso; podemos aprovar, recusar ou solicitar documentos complementares a nosso critério.</li>
        <li>As informações e documentos fornecidos devem ser verdadeiros e atuais.</li>
    </ul>

    <h2 id="p2">2. QUALIFICAÇÃO PROFISSIONAL E CREF</h2>
    <div class="highlighted warn">
        Você declara possuir a <strong>habilitação legal</strong> para atuar como profissional de educação física, incluindo, quando exigido, formação e <strong>registro ativo no CREF</strong>. Você é o <strong>único responsável</strong> pela veracidade dessas informações e por manter sua regularidade profissional. A Plataforma não fiscaliza nem se responsabiliza pela sua habilitação.
    </div>

    <h2 id="p3">3. NATUREZA DA RELAÇÃO (AUTONOMIA)</h2>
    <p>Você atua como <strong>profissional autônomo/independente</strong>. Estes Termos <strong>não criam</strong> vínculo empregatício, societário, de representação ou de subordinação com a Plataforma. Você organiza seu próprio trabalho, horários e preços, e é responsável por suas obrigações fiscais, trabalhistas e previdenciárias.</p>

    <h2 id="p4">4. SUAS OBRIGAÇÕES PROFISSIONAIS</h2>
    <ul>
        <li>Prestar os serviços com diligência, técnica e segurança, respeitando os limites e a saúde do aluno.</li>
        <li>Analisar a anamnese antes de prescrever treinos e adequar a carga ao aluno.</li>
        <li><strong>Não</strong> prestar orientação médica, diagnóstico, prescrição de medicamentos ou dietas privativas de outras profissões.</li>
        <li>Encaminhar o aluno a um médico diante de qualquer sinal de risco.</li>
        <li>Emitir os documentos fiscais devidos pelos serviços que prestar.</li>
    </ul>

    <h2 id="p5">5. PREÇOS, SPLIT E REPASSES</h2>
    <ul>
        <li>Você define seus preços e planos, informados ao aluno antes da contratação.</li>
        <li>Os pagamentos são processados pela <strong>Asaas</strong>, com <strong>split automático</strong>: a Plataforma retém sua <strong>taxa de intermediação</strong> e o restante é repassado à sua subconta.</li>
        <li>Para receber, é necessário concluir o cadastro/verificação na Asaas. Repasses, prazos e eventuais retenções seguem as regras do processador.</li>
        <li>Estornos, chargebacks e reembolsos ao aluno podem ser descontados dos seus valores a receber.</li>
    </ul>

    <h2 id="p6">6. AGENDA, CANCELAMENTOS E NO-SHOW</h2>
    <ul>
        <li>Você é responsável por manter sua agenda e horários atualizados e por honrar os agendamentos confirmados.</li>
        <li>Defina com clareza sua política de cancelamento/reagendamento; ela deve ser razoável e compatível com o CDC.</li>
    </ul>

    <h2 id="p7">7. DADOS DE SAÚDE DOS ALUNOS (LGPD)</h2>
    <div class="highlighted">
        Ao acessar anamneses e medidas dos seus alunos, você trata <strong>dados pessoais sensíveis</strong> e assume o dever de: usá-los <strong>somente</strong> para prescrever e acompanhar o treino; mantê-los em <strong>sigilo</strong>; não compartilhá-los com terceiros; e não utilizá-los para outras finalidades. O descumprimento pode gerar responsabilização sua perante o aluno e a autoridade (ANPD).
    </div>

    <h2 id="p8">8. CONDUTAS VEDADAS</h2>
    <ul>
        <li>Declarar qualificação falsa ou atuar sem habilitação;</li>
        <li>Aliciar alunos para pagar por fora e burlar a intermediação quando o contato ocorreu pela Plataforma;</li>
        <li>Manipular avaliações, criar contas falsas ou difamar concorrentes;</li>
        <li>Assediar alunos ou tratá-los de forma discriminatória;</li>
        <li>Usar dados de alunos para fins não autorizados.</li>
    </ul>

    <h2 id="p9">9. RESPONSABILIDADE E INDENIZAÇÃO</h2>
    <p>Você é integralmente responsável pelos serviços que presta, inclusive por eventuais lesões, danos ou reclamações deles decorrentes. Você concorda em <strong>defender, isentar e indenizar</strong> a Plataforma por perdas, custos e demandas de terceiros resultantes da sua atuação, do descumprimento destes Termos ou da violação de direitos e da legislação.</p>

    <h2 id="p10">10. SUSPENSÃO E DESCREDENCIAMENTO</h2>
    <p>Podemos suspender ou descredenciar o seu perfil em caso de violação destes Termos, qualificação irregular, reclamações graves ou reiteradas, fraude ou risco a alunos, sem prejuízo dos valores já devidos e das apurações cabíveis.</p>

    <h2 id="p11">11. CONTATO</h2>
    <p>Suporte e questões de dados: <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir seu cadastro como personal, você declara ser profissional habilitado e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
