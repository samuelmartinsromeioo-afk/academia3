@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Studio')
@section('doc_subtitulo', 'Condições para studios parceiros')
@section('doc_perfil', 'Perfil: Studio')
@section('doc_versao', '2.0')
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
            <li><a href="#st10">10. Contato</a></li>
        </ul>
    </div>

    <h2 id="st1">1. CADASTRO, APROVAÇÃO E REPRESENTAÇÃO</h2>
    <ul>
        <li>O cadastro exige dados válidos do estabelecimento e passa por <strong>análise e aprovação</strong> da Plataforma antes da liberação.</li>
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
        <li>Pagamentos são processados pela <strong>Asaas</strong>, com possível <strong>split</strong>: a Plataforma retém sua taxa e o restante é repassado à subconta do studio.</li>
        <li>Para receber, é necessário concluir o cadastro/verificação na Asaas. Estornos e reembolsos podem ser descontados dos valores a receber.</li>
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

    <h2 id="st10">10. CONTATO</h2>
    <p>Suporte e questões de dados: <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir o cadastro do studio, o representante declara ter poderes para tanto e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
