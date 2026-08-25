@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Aluno')
@section('doc_subtitulo', 'Condições para alunos e usuários da plataforma')
@section('doc_perfil', 'Perfil: Aluno / Usuário')
@section('doc_versao', '2.0')
@section('nav_aluno', 'active')

@section('doc_conteudo')

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica a você que usa o SNR FIT como <strong>aluno</strong>.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#a1">1. Quem pode usar (elegibilidade)</a></li>
            <li><a href="#a2">2. O que você pode fazer</a></li>
            <li><a href="#a3">3. Anamnese e dados de saúde</a></li>
            <li><a href="#a4">4. Contratação, agenda e cancelamentos</a></li>
            <li><a href="#a5">5. Pagamentos e reembolsos</a></li>
            <li><a href="#a6">6. Compras na loja</a></li>
            <li><a href="#a7">7. Relação com o profissional/estabelecimento</a></li>
            <li><a href="#a8">8. Suas responsabilidades e condutas vedadas</a></li>
            <li><a href="#a9">9. Riscos, saúde e isenções</a></li>
            <li><a href="#a10">10. Seus dados (LGPD)</a></li>
            <li><a href="#a11">11. Contato</a></li>
        </ul>
    </div>

    <h2 id="a1">1. QUEM PODE USAR (ELEGIBILIDADE)</h2>
    <ul>
        <li>Idade mínima de <strong>14 anos</strong> para uso como aluno.</li>
        <li>Menores de <strong>18 anos</strong> devem ter <strong>consentimento e assistência dos pais ou responsáveis legais</strong>, especialmente para dados de saúde (anamnese), contratações e pagamentos.</li>
        <li>Fornecer dados verdadeiros e mantê-los atualizados.</li>
    </ul>

    <h2 id="a2">2. O QUE VOCÊ PODE FAZER</h2>
    <ul>
        <li>Buscar personais, academias, studios e lojas — por nome, avaliação e, se você autorizar a localização, por proximidade.</li>
        <li>Contratar serviços e planos, agendar sessões/aulas e acompanhar treinos, metas e evolução.</li>
        <li>Registrar anamnese e medidas corporais para orientar o seu treino.</li>
        <li>Comunicar-se com o profissional/estabelecimento pelo chat e avaliar os serviços recebidos.</li>
    </ul>

    <h2 id="a3">3. ANAMNESE E DADOS DE SAÚDE</h2>
    <p>A anamnese (histórico de saúde, lesões, doenças, medicamentos e restrições) é um <strong>dado pessoal sensível</strong> (art. 11 da LGPD), coletado <strong>mediante o seu consentimento</strong> e usado exclusivamente para que o profissional monte um treino seguro.</p>
    <ul>
        <li>O preenchimento é opcional; você pode deixá-la em branco ou removê-la a qualquer momento.</li>
        <li>Você é responsável pela <strong>veracidade e completude</strong> das informações de saúde que fornece.</li>
        <li>Omitir condições relevantes pode comprometer sua segurança e afasta a responsabilidade da Plataforma e do profissional por consequências decorrentes da omissão.</li>
    </ul>

    <h2 id="a4">4. CONTRATAÇÃO, AGENDA E CANCELAMENTOS</h2>
    <ul>
        <li>A execução do serviço é do <strong>profissional/estabelecimento</strong> contratado; a Plataforma apenas intermedeia e viabiliza o agendamento e o pagamento.</li>
        <li>Cada profissional/estabelecimento pode ter <strong>política própria de cancelamento e reagendamento</strong>, com prazos mínimos de antecedência, informada na contratação.</li>
        <li>Faltas (no-show) ou cancelamentos fora do prazo podem não gerar reembolso, conforme a política aplicável.</li>
    </ul>

    <h2 id="a5">5. PAGAMENTOS E REEMBOLSOS</h2>
    <ul>
        <li>Pagamentos são processados pela <strong>Asaas</strong> (cartão, PIX, boleto). A Plataforma não guarda os dados completos do seu cartão.</li>
        <li>Planos recorrentes/assinaturas são renovados na periodicidade contratada até que você cancele.</li>
        <li><strong>Direito de arrependimento (art. 49 do CDC):</strong> em contratações à distância, você pode desistir em até <strong>7 dias corridos</strong>, com devolução dos valores.</li>
        <li>Reembolsos devidos retornam pelo meio de pagamento original, nos prazos do processador (em geral 5 a 10 dias úteis).</li>
    </ul>

    <h2 id="a6">6. COMPRAS NA LOJA</h2>
    <ul>
        <li>Produtos são anunciados e vendidos por <strong>lojistas</strong>, responsáveis por descrição, disponibilidade, qualidade, prazo e entrega.</li>
        <li>Trocas e devoluções por defeito seguem o Código de Defesa do Consumidor.</li>
        <li>Dúvidas sobre um produto devem ser tratadas primeiro com o lojista; a Plataforma pode auxiliar na mediação.</li>
    </ul>

    <h2 id="a7">7. RELAÇÃO COM O PROFISSIONAL/ESTABELECIMENTO</h2>
    <p>A qualificação e a conduta dos profissionais são <strong>declaradas por eles próprios</strong>. A Plataforma pode solicitar comprovação, mas <strong>não garante</strong> a habilitação, competência ou resultado. Avalie o profissional antes de contratar e reporte condutas inadequadas.</p>

    <h2 id="a8">8. SUAS RESPONSABILIDADES E CONDUTAS VEDADAS</h2>
    <ul>
        <li>Manter a conta pessoal e a senha em sigilo.</li>
        <li>Tratar profissionais e estabelecimentos com respeito.</li>
        <li>Não publicar avaliações falsas ou ofensivas, nem usar a Plataforma para fins ilícitos.</li>
        <li>Não combinar pagamentos por fora para burlar a intermediação quando o contato ocorreu pela Plataforma.</li>
    </ul>

    <h2 id="a9">9. RISCOS, SAÚDE E ISENÇÕES</h2>
    <div class="highlighted warn">
        A atividade física tem riscos inerentes. Consulte um médico antes de iniciar. A Plataforma <strong>não</strong> responde por lesões, danos ou acidentes decorrentes da execução dos treinos, que são de responsabilidade do profissional/estabelecimento e do praticante. Personais não prestam orientação médica.
    </div>

    <h2 id="a10">10. SEUS DADOS (LGPD)</h2>
    <p>Tratamos seus dados conforme a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>. Você pode acessar, corrigir, exportar e excluir seus dados, e revogar consentimentos, pela página <strong>"Privacidade e meus dados"</strong> no app ou pelo contato abaixo.</p>

    <h2 id="a11">11. CONTATO</h2>
    <p>Suporte e questões de dados: <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir seu cadastro como aluno, você confirma que leu e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
