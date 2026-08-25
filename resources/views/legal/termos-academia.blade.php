@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Academia')
@section('doc_subtitulo', 'Condições para academias parceiras')
@section('doc_perfil', 'Perfil: Academia')
@section('doc_versao', '2.0')
@section('nav_academia', 'active')

@section('doc_conteudo')

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica ao estabelecimento cadastrado como <strong>academia</strong>.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#ac1">1. Cadastro, aprovação e representação</a></li>
            <li><a href="#ac2">2. Modelo comercial e mensalidade da plataforma</a></li>
            <li><a href="#ac3">3. Gestão de alunos, planos e filiais</a></li>
            <li><a href="#ac4">4. Professores e profissionais vinculados</a></li>
            <li><a href="#ac5">5. Pagamentos, split e repasses</a></li>
            <li><a href="#ac6">6. Academia como controladora de dados (LGPD)</a></li>
            <li><a href="#ac7">7. Responsabilidades pelo estabelecimento</a></li>
            <li><a href="#ac8">8. Condutas vedadas</a></li>
            <li><a href="#ac9">9. Indenização e limitação</a></li>
            <li><a href="#ac10">10. Suspensão e encerramento</a></li>
            <li><a href="#ac11">11. Contato</a></li>
        </ul>
    </div>

    <h2 id="ac1">1. CADASTRO, APROVAÇÃO E REPRESENTAÇÃO</h2>
    <ul>
        <li>O cadastro exige dados válidos do estabelecimento (incluindo <strong>CNPJ</strong>) e passa por <strong>análise e aprovação</strong> da Plataforma antes da liberação do acesso.</li>
        <li>Quem realiza o cadastro declara ter <strong>poderes para representar</strong> a academia e assumir estas obrigações em nome dela.</li>
        <li>As informações do perfil (endereço, estrutura, aulas, planos) devem ser verídicas e mantidas atualizadas.</li>
    </ul>

    <h2 id="ac2">2. MODELO COMERCIAL E MENSALIDADE DA PLATAFORMA</h2>
    <ul>
        <li>O acesso da academia pode estar sujeito a um <strong>valor mensal</strong> e/ou a uma <strong>taxa de intermediação</strong> sobre as transações, conforme condições alinhadas na contratação/aprovação.</li>
        <li>Valores, forma de cobrança e vigência são informados antes da ativação e podem ser reajustados mediante aviso prévio.</li>
    </ul>

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
        <li>Pagamentos de alunos são processados pela <strong>Asaas</strong>, com possível <strong>split</strong>: a Plataforma retém sua taxa e o restante é repassado à subconta da academia.</li>
        <li>Para receber, é necessário concluir o cadastro/verificação na Asaas. Estornos e reembolsos podem ser descontados dos valores a receber.</li>
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

    <h2 id="ac11">11. CONTATO</h2>
    <p>Suporte e questões de dados: <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir o cadastro da academia, o representante declara ter poderes para tanto e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
