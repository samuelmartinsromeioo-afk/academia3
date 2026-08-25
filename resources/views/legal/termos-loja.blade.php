@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Loja')
@section('doc_subtitulo', 'Condições para lojistas e vendedores')
@section('doc_perfil', 'Perfil: Loja')
@section('doc_versao', '2.0')
@section('nav_loja', 'active')

@section('doc_conteudo')

    <div class="highlighted">
        Este documento complementa os <a href="{{ route('termos') }}">Termos de Uso gerais</a> e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, e se aplica ao estabelecimento cadastrado como <strong>loja</strong>.
    </div>

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#lo1">1. Cadastro, aprovação e representação</a></li>
            <li><a href="#lo2">2. Anúncio de produtos</a></li>
            <li><a href="#lo3">3. Loja como fornecedora (CDC)</a></li>
            <li><a href="#lo4">4. Entrega, trocas e devoluções</a></li>
            <li><a href="#lo5">5. Pagamentos, split e repasses</a></li>
            <li><a href="#lo6">6. Produtos proibidos e restritos</a></li>
            <li><a href="#lo7">7. Dados de clientes (LGPD)</a></li>
            <li><a href="#lo8">8. Condutas vedadas e indenização</a></li>
            <li><a href="#lo9">9. Suspensão e encerramento</a></li>
            <li><a href="#lo10">10. Contato</a></li>
        </ul>
    </div>

    <h2 id="lo1">1. CADASTRO, APROVAÇÃO E REPRESENTAÇÃO</h2>
    <ul>
        <li>O cadastro exige dados válidos do estabelecimento e passa por <strong>análise e aprovação</strong> da Plataforma antes da liberação.</li>
        <li>Quem cadastra declara ter <strong>poderes para representar</strong> a loja e assumir estas obrigações.</li>
    </ul>

    <h2 id="lo2">2. ANÚNCIO DE PRODUTOS</h2>
    <ul>
        <li>A loja é <strong>integralmente responsável</strong> pela descrição, imagens, preço, disponibilidade, qualidade, garantia e conformidade legal dos produtos que anuncia.</li>
        <li>As informações devem ser verdadeiras, claras e não enganosas.</li>
        <li>A loja garante possuir os direitos necessários sobre as imagens e marcas utilizadas nos anúncios.</li>
    </ul>

    <h2 id="lo3">3. LOJA COMO FORNECEDORA (CDC)</h2>
    <div class="highlighted">
        Nas vendas realizadas pela Plataforma, a loja é a <strong>fornecedora</strong> perante o consumidor, nos termos do Código de Defesa do Consumidor. A Plataforma atua apenas como <strong>vitrine e meio de intermediação</strong>, não sendo a vendedora dos produtos.
    </div>

    <h2 id="lo4">4. ENTREGA, TROCAS E DEVOLUÇÕES</h2>
    <ul>
        <li>Prazos e custos de entrega, quando aplicáveis, devem ser informados antes da finalização da compra e cumpridos pela loja.</li>
        <li><strong>Direito de arrependimento (art. 49 do CDC):</strong> o consumidor pode desistir em até <strong>7 dias corridos</strong> do recebimento, com devolução dos valores.</li>
        <li>Trocas e devoluções por vício ou defeito seguem o CDC e são de responsabilidade da loja.</li>
    </ul>

    <h2 id="lo5">5. PAGAMENTOS, SPLIT E REPASSES</h2>
    <ul>
        <li>Pagamentos são processados pela <strong>Asaas</strong>, com possível <strong>split</strong>: a Plataforma retém sua taxa e o restante é repassado à subconta da loja.</li>
        <li>Estornos, chargebacks e reembolsos ao consumidor podem ser descontados dos valores a receber.</li>
    </ul>

    <h2 id="lo6">6. PRODUTOS PROIBIDOS E RESTRITOS</h2>
    <p>É vedado anunciar produtos ilícitos, falsificados, sem registro obrigatório ou que exijam prescrição/autorização que a loja não possua — incluindo <strong>medicamentos, anabolizantes e substâncias controladas</strong>. Suplementos e alimentos devem cumprir a regulamentação sanitária aplicável (ex.: ANVISA).</p>

    <h2 id="lo7">7. DADOS DE CLIENTES (LGPD)</h2>
    <div class="highlighted">
        Ao tratar dados dos compradores para faturamento e entrega, a loja atua como <strong>controladora</strong> desses dados e deve usá-los apenas para essa finalidade, mantê-los em sigilo e cumprir a LGPD. É vedado usar tais dados para marketing sem base legal ou repassá-los indevidamente.
    </div>

    <h2 id="lo8">8. CONDUTAS VEDADAS E INDENIZAÇÃO</h2>
    <p>São vedados anúncios enganosos, produtos proibidos, burla da intermediação e uso indevido de dados. A loja concorda em <strong>defender, isentar e indenizar</strong> a Plataforma por demandas de consumidores, autoridades ou terceiros decorrentes de seus produtos, anúncios ou do descumprimento destes Termos. Aplica-se a limitação dos <a href="{{ route('termos') }}">Termos gerais</a>.</p>

    <h2 id="lo9">9. SUSPENSÃO E ENCERRAMENTO</h2>
    <p>Podemos remover anúncios e suspender ou encerrar o perfil por violação destes Termos, produtos irregulares, reclamações graves, fraude ou risco ao consumidor.</p>

    <h2 id="lo10">10. CONTATO</h2>
    <p>Suporte e questões de dados: <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a>.</p>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir o cadastro da loja, o representante declara ter poderes para tanto e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
