@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso — Loja')
@section('doc_subtitulo', 'Condições para lojistas e vendedores')
@section('doc_perfil', 'Perfil: Loja')
@section('doc_versao', config('termos.versao'))
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
            <li><a href="#lo10">10. Programa de indicação</a></li>
            <li><a href="#lo11">11. Contato</a></li>
        </ul>
    </div>

    <h2 id="lo1">1. CADASTRO, APROVAÇÃO E REPRESENTAÇÃO</h2>
    <ul>
        <li>O cadastro exige dados válidos do estabelecimento e passa por <strong>análise e aprovação</strong> da Plataforma antes da liberação. A aprovação é <strong>conferência cadastral</strong>, não auditoria nem certificação de qualidade, e não transfere à Plataforma responsabilidade pela operação do estabelecimento.</li>
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
        <li><strong>Direito de arrependimento (art. 49 do CDC):</strong> o consumidor pode desistir em
            até <strong>7 (sete) dias corridos</strong> contados do <strong>recebimento do
                produto</strong> — não da compra —, com devolução <strong>integral</strong> dos
            valores. O dia do recebimento não conta, o último dia conta e, caindo em sábado, domingo
            ou feriado nacional, prorroga-se para o primeiro dia útil seguinte
            (<a href="{{ route('termos') }}#s1-4">cláusula 1.4 dos Termos gerais</a>). O
            <strong>custo da devolução é da loja</strong> (art. 49, parágrafo único).</li>
        <li>Trocas e devoluções por vício ou defeito seguem o CDC e são de responsabilidade da loja,
            nos prazos de reclamação do art. 26 (<strong>30 dias corridos</strong> para produto não
            durável e <strong>90 dias corridos</strong> para durável, contados da entrega efetiva ou,
            em vício oculto, de quando o defeito ficar evidente).</li>
    </ul>

    <h2 id="lo5">5. PAGAMENTOS, SPLIT E REPASSES</h2>
    <ul>
        <li>Pagamentos são processados pela <strong>Asaas</strong> com <strong>split</strong>: <strong>o valor dos produtos é repassado integralmente</strong> à subconta da loja, e a taxa de intermediação da Plataforma é <strong>somada ao valor pago pelo comprador</strong>, não descontada da loja. Nas transações com cartão, as tarifas do processador também são acrescidas ao valor do comprador.</li>
        <li>Para receber, é necessário concluir o cadastro/verificação na Asaas. <strong>Prazos de liquidação, retenções e bloqueios seguem as regras do processador</strong>, não da Plataforma.</li>
        {{--
            Este bloco era cópia do texto de academia/studio e falava de "aluno"
            e de "serviço já prestado" — o que, numa venda de produto, não
            descreve nada: não há serviço a prestar pela metade, há mercadoria
            entregue ou não, e quem compra é o comprador. A regra material é
            diferente e precisava do seu próprio texto.
        --}}
        <li><strong>Reembolso ao comprador.</strong> Quando o comprador exerce o direito de
            arrependimento (7 dias <strong>corridos</strong>, art. 49 do CDC), obtém reembolso por
            vício ou defeito do produto, ou quando há <em>chargeback</em> reconhecido pelo emissor do
            cartão: o comprador recebe de volta <strong>tudo o que pagou</strong>, a Plataforma
            <strong>devolve a própria taxa de intermediação</strong> e o valor já repassado é
            <strong>recomposto por compensação em repasses futuros</strong> ou, não havendo saldo, por
            cobrança direta.</li>
        <li><strong>Devolução da mercadoria e frete.</strong> No arrependimento, o produto retorna à
            loja e <strong>o custo da devolução é da loja</strong>, não do consumidor (art. 49,
            parágrafo único, do CDC): o valor devolvido é integral, sem desconto de frete de ida ou
            de volta. A loja informa ao comprador como devolver em até
            <strong>2 (dois) dias úteis</strong> do aviso. <strong>Não se aplica</strong> aqui a
            regra de "parcela já prestada" dos documentos de academia e studio — produto não se
            consome pela metade; o que existe é produto devolvido ou não devolvido, e a recomposição
            segue o estado em que a mercadoria retornar.</li>
        <li><strong>Notas fiscais.</strong> A loja emite o documento fiscal do produto vendido ao
            comprador, <strong>pelo valor do seu próprio preço</strong> — não pelo valor final pago
            por ele, já que a taxa de intermediação não é receita da loja. A Plataforma emite o
            documento fiscal da taxa de intermediação e <strong>não é substituta tributária</strong>
            da loja.</li>
        <li><strong>Repasse sem subconta.</strong> Enquanto a loja não tiver subconta habilitada no
            processador, o valor fica na conta da Plataforma e é repassado mediante solicitação de
            saque no painel, paga em até <strong>5 (cinco) dias úteis</strong> da solicitação
            regular (chave PIX válida de titularidade da loja e cadastro sem pendência).</li>
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
    <div class="highlighted">
        <strong>Devido processo.</strong> Salvo risco iminente a pessoas, fraude em curso, ordem de
        autoridade ou exigência legal, comunicamos o motivo e abrimos prazo
        <strong>não inferior a 5 (cinco) dias corridos</strong>, contados do recebimento da
        comunicação, para a loja se manifestar antes da medida definitiva; respondemos por escrito em
        até <strong>5 (cinco) dias úteis</strong>. Havendo urgência, comunicação e manifestação se dão
        em seguida à medida, em até <strong>2 (dois) dias úteis</strong>.
        <strong>Risco ao consumidor</strong> — produto proibido, sem registro obrigatório ou com
        indício de falsificação — autoriza a <strong>remoção imediata do anúncio</strong>, com a
        comunicação em seguida. Nas duas situações, <strong>valores já devidos por vendas
            concluídas permanecem devidos</strong> e são pagos, e pedidos pagos e não entregues são
        tratados com o comprador. Ver a
        <a href="{{ route('termos') }}#s16">cláusula 16 dos Termos gerais</a>.
    </div>
    <h2 id="lo9-1">9.1. Encerramento pela loja</h2>
    <p>A loja pode encerrar a conta a qualquer momento, sem multa e sem aviso prévio, em
        <em>Privacidade e meus dados</em> (no app e no site), confirmando a senha. O efeito é
        <strong>imediato e definitivo</strong> — sem carência e sem reativação —, com confirmação por
        escrito enviada antes da eliminação dos dados. Pedidos pagos e pendentes de entrega
        <strong>continuam exigíveis</strong> da loja após o encerramento. Pelo mesmo caminho a loja
        exporta e corrige os seus dados
        (<a href="{{ route('termos') }}#s12-1">cláusula 12.1</a>).</p>

    <h2 id="lo10">10. PROGRAMA DE INDICAÇÃO</h2>
    <p>Você recebe um código de indicação próprio e pode ganhar um <strong>bônus percentual sobre a comissão da Plataforma</strong> nas transações de cada estabelecimento indicado por você, durante a janela de apuração. O bônus <strong>não</strong> incide sobre o valor pago pelo aluno nem sobre o faturamento do indicado, e <strong>nada</strong> é descontado do que o indicado recebe. As regras completas, a definição exata da base de cálculo, as condições de saque e as vedações estão na <a href="{{ route('termos') }}#s9">cláusula 9 dos Termos gerais</a>, que você declara ter lido.</p>
    <div class="highlighted warn">
        <strong>O bônus só vira sacável com duas condições cumulativas:</strong> o encerramento da janela de apuração <strong>e</strong> o atingimento, pela conta indicada, do número mínimo de alunos com pagamento confirmado. Antes disso o valor exibido é <strong>mera expectativa</strong>, não crédito exigível. O bônus é concedido por <strong>liberalidade</strong>, não é remuneração e <strong>não cria vínculo</strong> de qualquer natureza com a Plataforma.
    </div>
    <p>Auto-indicação, contas falsas, simulação de alunos ou pagamentos e esquemas coordenados para atingir a meta implicam <strong>cancelamento do bônus, estorno de valores pagos e suspensão das contas envolvidas</strong>, sem prejuízo das medidas legais cabíveis.</p>

    <h2 id="lo11">11. CONTATO</h2>
    <ul>
        <li><strong>Atendimento:</strong> <a href="mailto:{{ \App\Support\Empresa::get('email_suporte') }}">{{ \App\Support\Empresa::get('email_suporte') }}</a></li>
        <li><strong>Dados pessoais (DPO):</strong> {{ \App\Support\Empresa::get('dpo_nome') }} — <a href="mailto:{{ \App\Support\Empresa::get('dpo_email') }}">{{ \App\Support\Empresa::get('dpo_email') }}</a></li>
    </ul>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao concluir o cadastro da loja, o representante declara ter poderes para tanto e concorda com estes Termos, com os <a href="{{ route('termos') }}">Termos gerais</a> e com a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>.
    </div>

@endsection
