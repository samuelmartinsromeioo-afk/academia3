@extends('legal.layout')

@section('doc_titulo', 'Termos de Uso')
@section('doc_subtitulo', 'Condições gerais da plataforma SNR FIT')
@section('doc_versao', '2.1')
@section('nav_index', 'active')

@section('doc_conteudo')

    <div class="toc">
        <h3><i class="ph ph-list"></i> Índice</h3>
        <ul>
            <li><a href="#s1">1. Aceitação e definições</a></li>
            <li><a href="#s2">2. O que é a plataforma</a></li>
            <li><a href="#s3">3. Termos específicos por perfil</a></li>
            <li><a href="#s4">4. Conta, acesso e segurança</a></li>
            <li><a href="#s5">5. Regras de conduta</a></li>
            <li><a href="#s6">6. Saúde e segurança na prática de exercícios</a></li>
            <li><a href="#s7">7. Pagamentos, intermediação e Asaas</a></li>
            <li><a href="#s8">8. Programa de indicação ("Indique e ganhe")</a></li>
            <li><a href="#s9">9. Avaliações e reputação</a></li>
            <li><a href="#s10">10. Propriedade intelectual</a></li>
            <li><a href="#s11">11. Limitação de responsabilidade</a></li>
            <li><a href="#s12">12. Suspensão e encerramento</a></li>
            <li><a href="#s13">13. Proteção de dados (LGPD)</a></li>
            <li><a href="#s14">14. Alterações destes Termos</a></li>
            <li><a href="#s15">15. Lei aplicável, foro e contato</a></li>
        </ul>
    </div>

    <h2 id="s1">1. ACEITAÇÃO E DEFINIÇÕES</h2>
    <p>Estes Termos de Uso ("Termos") regem o acesso e a utilização da plataforma <strong>SNR FIT</strong> ("Plataforma", "nós"), disponibilizada por meio de site e aplicativo. Ao criar uma conta, acessar ou utilizar a Plataforma, você declara ter lido, compreendido e aceito integralmente estes Termos e a <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>. Caso não concorde, não utilize a Plataforma.</p>
    <p>Para os fins destes Termos:</p>
    <ul>
        <li><strong>Aluno/Usuário:</strong> pessoa física que utiliza a Plataforma para encontrar e contratar serviços de treino, acompanhar sua evolução e comprar planos ou produtos.</li>
        <li><strong>Personal:</strong> profissional de educação física que oferece serviços por meio da Plataforma.</li>
        <li><strong>Academia</strong> e <strong>Studio:</strong> estabelecimentos que gerenciam alunos, planos, aulas e profissionais.</li>
        <li><strong>Loja:</strong> estabelecimento que anuncia e vende produtos na Plataforma.</li>
        <li><strong>Conteúdo:</strong> qualquer informação, texto, imagem, avaliação, ficha ou dado inserido na Plataforma.</li>
    </ul>

    <h2 id="s2">2. O QUE É A PLATAFORMA</h2>
    <p>O SNR FIT é um <strong>ambiente de intermediação</strong> que conecta alunos a personais, academias, studios e lojas, oferecendo ferramentas de montagem e acompanhamento de treinos, agendamento, comunicação (chat), avaliações e um meio de pagamento integrado.</p>
    <div class="highlighted">
        <strong>Papel de intermediária:</strong> a Plataforma <strong>não presta serviços de educação física</strong>, não emprega os profissionais e não vende diretamente os produtos anunciados. A execução dos serviços e a entrega dos produtos são de responsabilidade exclusiva do respectivo personal, academia, studio ou loja. A Plataforma não é parte da relação de consumo firmada entre o aluno e o profissional/estabelecimento, salvo quanto aos serviços que ela própria presta (a tecnologia).
    </div>

    <h2 id="s3">3. TERMOS ESPECÍFICOS POR PERFIL</h2>
    <p>Além destas condições gerais, cada perfil possui um documento próprio, que integra e complementa estes Termos e deve ser aceito no cadastro correspondente:</p>
    <ul>
        <li><a href="{{ route('termos.aluno') }}"><strong>Termos do Aluno/Usuário</strong></a></li>
        <li><a href="{{ route('termos.personal') }}"><strong>Termos do Personal Trainer</strong></a></li>
        <li><a href="{{ route('termos.academia') }}"><strong>Termos da Academia</strong></a></li>
        <li><a href="{{ route('termos.studio') }}"><strong>Termos do Studio</strong></a></li>
        <li><a href="{{ route('termos.loja') }}"><strong>Termos da Loja</strong></a></li>
    </ul>
    <p>Em caso de conflito entre estas condições gerais e o documento específico do seu perfil, prevalece o documento específico naquilo que for mais restritivo ou detalhado.</p>

    <h2 id="s4">4. CONTA, ACESSO E SEGURANÇA</h2>
    <ul>
        <li>O cadastro exige informações <strong>verdadeiras, precisas e atualizadas</strong>. Você é responsável por mantê-las corretas.</li>
        <li>A conta é <strong>pessoal e intransferível</strong>. Você é responsável por toda atividade realizada com suas credenciais.</li>
        <li>Mantenha sua senha em sigilo e comunique-nos imediatamente qualquer uso não autorizado.</li>
        <li>Podemos exigir verificação de identidade e recusar, suspender ou encerrar cadastros que violem estes Termos ou a lei.</li>
    </ul>

    <h2 id="s5">5. REGRAS DE CONDUTA</h2>
    <p>Ao usar a Plataforma, você concorda em <strong>não</strong>:</p>
    <ul>
        <li>Fornecer informações falsas, falsificar identidade ou se passar por terceiros;</li>
        <li>Usar linguagem abusiva, discriminatória, difamatória ou ameaçadora;</li>
        <li>Assediar, coagir ou praticar violência contra outros usuários;</li>
        <li>Violar a privacidade, a imagem ou a propriedade intelectual de terceiros;</li>
        <li>Contornar a Plataforma para deixar de pagar taxas devidas (ver seção 7);</li>
        <li>Utilizar a Plataforma para fins ilícitos, fraudulentos, spam, phishing ou disseminação de malware;</li>
        <li>Interferir, sobrecarregar ou tentar burlar o funcionamento técnico ou a segurança da Plataforma;</li>
        <li>Extrair dados de terceiros (scraping) ou coletar dados pessoais sem base legal.</li>
    </ul>

    <h2 id="s6">6. SAÚDE E SEGURANÇA NA PRÁTICA DE EXERCÍCIOS</h2>
    <ul>
        <li>A prática de atividade física envolve <strong>riscos inerentes</strong>. Recomenda-se avaliação médica antes de iniciar qualquer programa de exercícios.</li>
        <li>O aluno deve informar ao profissional condições de saúde, lesões, gestação, uso de medicamentos e restrições.</li>
        <li>Personais e estabelecimentos <strong>não</strong> prestam orientação médica, diagnóstico ou tratamento; questões clínicas devem ser levadas a um médico.</li>
    </ul>
    <div class="highlighted warn">
        <strong>Isenção:</strong> a Plataforma não se responsabiliza por lesões, danos ou acidentes decorrentes da execução dos treinos, que são de responsabilidade do profissional ou estabelecimento que os conduz e do próprio praticante.
    </div>

    <h2 id="s7">7. PAGAMENTOS, INTERMEDIAÇÃO E ASAAS</h2>
    <ul>
        <li>Os pagamentos são processados pelo parceiro <strong>Asaas</strong> (cartão, PIX e boleto). A Plataforma <strong>não armazena os dados completos do cartão</strong>.</li>
        <li>A Plataforma pode reter uma <strong>taxa de intermediação</strong> sobre os valores transacionados, informada antes da contratação, repassando o restante ao profissional/estabelecimento (split de pagamento).</li>
        <li>Preços, planos, promoções e políticas de cancelamento são definidos por cada profissional/estabelecimento e informados antes da confirmação.</li>
        <li><strong>Direito de arrependimento (art. 49 do CDC):</strong> em compras realizadas fora do estabelecimento físico, o consumidor pode desistir em até <strong>7 dias corridos</strong> do recebimento, com devolução dos valores.</li>
        <li>Reembolsos devidos são processados pelo meio de pagamento original, nos prazos operacionais do processador.</li>
    </ul>
    <div class="highlighted warn">
        <strong>Não burlar a intermediação:</strong> combinar pagamentos por fora para evitar a taxa da Plataforma, quando o contato se deu por meio dela, é vedado e pode levar à suspensão da conta.
    </div>

    <h2 id="s8">8. PROGRAMA DE INDICAÇÃO ("INDIQUE E GANHE")</h2>

    <h3>8.1. Natureza do programa</h3>
    <p>O Programa de Indicação é uma <strong>campanha promocional concedida por mera liberalidade</strong> da Plataforma, de adesão facultativa e gratuita. Ele <strong>não integra a contraprestação</strong> de nenhum serviço contratado, <strong>não altera</strong> qualquer valor cobrado de alunos, profissionais ou estabelecimentos, e <strong>não constitui direito adquirido</strong>: pode ser alterado, suspenso ou encerrado a qualquer tempo, a critério da Plataforma, preservando-se os bônus já liberados até a data da alteração.</p>
    <div class="highlighted warn">
        O bônus de indicação <strong>não é salário, remuneração, comissão de representação comercial, participação em lucros ou resultados, nem contraprestação por trabalho</strong>. A participação no Programa <strong>não cria</strong> vínculo empregatício, societário, de parceria, de agência ou de representação entre o participante e a Plataforma.
    </div>

    <h3>8.2. Como funciona</h3>
    <ul>
        <li>Cada conta recebe um <strong>código de indicação próprio</strong>, pessoal e intransferível.</li>
        <li>Quem está se cadastrando pode informar o código de quem o indicou <strong>exclusivamente no momento do seu cadastro</strong>. Não há inclusão, troca ou retroação de código depois do cadastro concluído.</li>
        <li>Cada conta pode ser indicada <strong>uma única vez</strong>, de forma definitiva.</li>
        <li>O bônus corresponde a um <strong>percentual do valor bruto</strong> efetivamente transacionado pela conta indicada na Plataforma durante uma <strong>janela de apuração</strong> contada a partir da <strong>aprovação do cadastro da conta indicada</strong>.</li>
        <li>O percentual e o prazo da janela são os <strong>vigentes e informados na Plataforma na data da aprovação da conta indicada</strong>. Alterações posteriores aplicam-se apenas a indicações futuras e <strong>não retroagem</strong>.</li>
        <li>Encerrada a janela, o valor apurado é <strong>definitivo</strong>: receita gerada pela conta indicada após esse prazo não acresce o bônus.</li>
    </ul>

    <h3>8.3. Condições cumulativas para o saque</h3>
    <div class="highlighted">
        O bônus somente se torna <strong>exigível e disponível para saque</strong> quando <strong>as duas condições abaixo forem atendidas, cumulativamente</strong>:
        <ol>
            <li>o <strong>encerramento integral</strong> da janela de apuração da indicação; <strong>e</strong></li>
            <li>o atingimento, pela conta indicada, do <strong>número mínimo de alunos/clientes distintos com pagamento confirmado na Plataforma</strong>, conforme informado no painel do Programa.</li>
        </ol>
    </div>
    <p>Enquanto ambas as condições não forem atendidas, o valor exibido no painel constitui <strong>mera expectativa de direito</strong>, de caráter informativo, e <strong>não</strong> crédito líquido, certo e exigível. Atendidas as condições, o bônus liberado <strong>não é revertido</strong> em razão de perda posterior de alunos pela conta indicada, ressalvadas as hipóteses da cláusula 8.5 e 8.7.</p>

    <h3>8.4. Indicação de aluno não gera bônus</h3>
    <p>A indicação de contas de <strong>Aluno/Usuário</strong> é registrada apenas como histórico e <strong>não gera bônus algum</strong>, por não haver, nesse perfil, receita intermediada nem conquista de alunos. O Programa remunera exclusivamente a indicação de <strong>profissionais e estabelecimentos</strong> que venham a transacionar na Plataforma.</p>

    <h3>8.5. Base de cálculo e ajustes</h3>
    <ul>
        <li>A apuração é feita <strong>exclusivamente com base nos registros da Plataforma</strong>, que prevalecem para todos os efeitos em caso de divergência.</li>
        <li>Compõem a base apenas os pagamentos <strong>efetivamente confirmados</strong> dentro da janela. <strong>Não</strong> compõem: valores pendentes, recusados, cancelados, estornados, em disputa, nem vínculos ou cadastros criados manualmente sem pagamento correspondente.</li>
        <li>A Plataforma poderá <strong>recalcular, ajustar, retificar ou cancelar</strong> bônus apurados, a qualquer tempo, em caso de <strong>estorno, chargeback, cancelamento, reembolso, erro operacional, falha sistêmica ou fraude</strong>, ainda que posteriores à liberação.</li>
        <li>Nessas hipóteses, a Plataforma poderá <strong>compensar</strong> o valor em bônus futuros ou em outros valores devidos ao participante e, não havendo saldo suficiente, <strong>exigir a devolução</strong> do valor indevidamente pago, que será desde já considerado <strong>enriquecimento sem causa</strong>.</li>
    </ul>

    <h3>8.6. Saque e pagamento</h3>
    <ul>
        <li>O saque é solicitado pelo próprio participante, no painel do Programa, observado o <strong>valor mínimo</strong> informado na Plataforma.</li>
        <li>É permitida <strong>uma solicitação em aberto por vez</strong> por conta.</li>
        <li>O valor da solicitação é <strong>calculado pela Plataforma</strong> a partir dos bônus liberados, não sendo possível solicitar valor diferente do apurado.</li>
        <li>O pagamento é feito por <strong>Pix</strong>, na chave informada pelo participante no ato da solicitação. O participante é o <strong>único responsável</strong> pela exatidão e titularidade dessa chave.</li>
        <li>Solicitações até o limite definido pela Plataforma podem ser processadas <strong>automaticamente</strong>; as demais ficam sujeitas a <strong>conferência prévia</strong> e são pagas em até <strong>5 (cinco) dias úteis</strong> após a validação, ressalvados prazos do processador de pagamentos e eventuais pendências cadastrais.</li>
        <li>A Plataforma poderá condicionar o pagamento à <strong>regularidade cadastral</strong> e à confirmação de identidade do participante.</li>
    </ul>
    <div class="highlighted warn">
        <strong>Chave Pix incorreta:</strong> transferência efetuada para chave informada erroneamente pelo participante é considerada <strong>regularmente cumprida</strong> e não gera obrigação de novo pagamento, cabendo ao participante buscar a devolução perante o recebedor e seu banco.
    </div>

    <h3>8.7. Condutas vedadas e fraude</h3>
    <p>É expressamente vedado, entre outras condutas de má-fé:</p>
    <ul>
        <li><strong>Auto-indicação</strong>, direta ou indireta, inclusive por meio de segundo cadastro, de e-mail distinto, de conta de mesma titularidade ou de pessoa interposta;</li>
        <li>criar contas <strong>falsas</strong>, fictícias ou de terceiros sem autorização, ou cadastrar dados de terceiros sem consentimento;</li>
        <li><strong>simular</strong> transações, pagamentos ou alunos com o fim de atingir as condições da cláusula 8.3;</li>
        <li>organizar <strong>esquemas coordenados</strong>, circulares ou em cadeia com a finalidade de obter bônus;</li>
        <li>divulgar o código por <strong>spam</strong>, mensagens não solicitadas em massa, anúncios pagos sobre a marca SNR FIT ou qualquer <strong>publicidade enganosa</strong>, inclusive promessa de ganhos a terceiros;</li>
        <li>utilizar a marca, o nome ou a identidade visual da Plataforma sem autorização escrita.</li>
    </ul>
    <div class="highlighted warn">
        Verificada qualquer dessas condutas, ou indício fundado delas, a Plataforma poderá, isolada ou cumulativamente e sem aviso prévio: <strong>cancelar o bônus</strong> (inclusive já liberado), <strong>retê-lo</strong> durante a apuração, <strong>estornar</strong> valores pagos, <strong>invalidar</strong> as indicações envolvidas, <strong>suspender ou encerrar</strong> as contas relacionadas e adotar as <strong>medidas legais</strong> cabíveis, inclusive cobrança de perdas e danos.
    </div>

    <h3>8.8. Registros, transparência e dados</h3>
    <ul>
        <li>São registrados a <strong>data e o endereço IP</strong> do uso do código, para prevenção à fraude, com fundamento no <strong>legítimo interesse</strong> (art. 7º, IX, da LGPD).</li>
        <li>Quem indicou visualiza, no seu painel, o <strong>nome da conta indicada</strong>, a situação da indicação, o prazo da janela e <strong>indicadores agregados de progresso</strong>, estritamente para acompanhar a apuração do próprio bônus. <strong>Não</strong> são exibidos dados de contato, documentos, detalhamento financeiro da conta indicada nem dados de alunos dela.</li>
        <li>O participante <strong>não pode</strong> utilizar essas informações para qualquer outra finalidade, notadamente prospecção, oferta de serviços concorrentes ou repasse a terceiros.</li>
        <li>Ao informar o código de quem o indicou, a conta indicada <strong>está ciente</strong> de que seu nome e a situação da indicação serão exibidos a quem a indicou, para fins de apuração do bônus.</li>
    </ul>

    <h3>8.9. Tributos</h3>
    <p>O participante é o <strong>único responsável</strong> pelos tributos incidentes sobre os valores que receber, bem como pelas respectivas declarações e obrigações acessórias. A Plataforma efetuará as retenções que lhe sejam legalmente exigíveis e poderá solicitar documentação fiscal como condição ao pagamento.</p>

    <h3>8.10. Ausência de garantia de ganho</h3>
    <div class="highlighted warn">
        O valor do bônus depende <strong>integralmente da atividade de terceiro</strong> (a conta indicada), que a Plataforma <strong>não controla e não pode assegurar</strong>. A Plataforma <strong>não promete, não projeta e não garante</strong> qualquer valor, resultado, rendimento ou retorno. Nenhuma comunicação sobre o Programa deve ser interpretada como oferta de investimento, promessa de lucro ou garantia de ganho.
    </div>

    <h3>8.11. Encerramento da conta</h3>
    <p>O encerramento da conta por iniciativa do participante, ou a sua suspensão/encerramento por violação destes Termos, implica a <strong>perda do bônus ainda não pago</strong>, inclusive do já liberado e não solicitado, sem direito a indenização.</p>

    <h2 id="s9">9. AVALIAÇÕES E REPUTAÇÃO</h2>
    <ul>
        <li>Avaliações devem ser honestas, respeitosas e baseadas em experiência real.</li>
        <li>São proibidas avaliações falsas, ofensivas, discriminatórias ou com fins de extorsão.</li>
        <li>Podemos moderar, ocultar ou remover conteúdo que viole estes Termos, sem que isso configure censura ou responsabilidade.</li>
    </ul>

    <h2 id="s10">10. PROPRIEDADE INTELECTUAL</h2>
    <p>A marca SNR FIT, o software, o design, os textos e demais elementos da Plataforma são de nossa titularidade ou licenciados, e não podem ser copiados, modificados ou explorados sem autorização. Concedemos a você uma licença <strong>limitada, pessoal, não exclusiva e revogável</strong> para uso da Plataforma conforme estes Termos.</p>
    <p>O Conteúdo que você insere permanece seu, mas você concede à Plataforma uma licença para hospedá-lo e exibi-lo na medida necessária à prestação do serviço.</p>

    <h2 id="s11">11. LIMITAÇÃO DE RESPONSABILIDADE</h2>
    <p>A Plataforma é fornecida "no estado em que se encontra". Não garantimos funcionamento ininterrupto ou livre de erros. Na máxima extensão permitida pela lei, a Plataforma não responde por danos indiretos, lucros cessantes ou por atos e omissões de profissionais, estabelecimentos ou outros usuários.</p>
    <div class="highlighted">
        A responsabilidade total da Plataforma, quando cabível, fica limitada ao valor das taxas por ela efetivamente recebidas do usuário nos <strong>12 meses</strong> anteriores ao fato gerador. Nada nestes Termos exclui responsabilidades que não possam ser afastadas por lei (como as do CDC nos serviços que a Plataforma efetivamente presta).
    </div>

    <h2 id="s12">12. SUSPENSÃO E ENCERRAMENTO</h2>
    <p>Você pode encerrar sua conta a qualquer momento. Podemos suspender ou encerrar contas que violem estes Termos ou a lei, que apresentem risco a terceiros ou fraude, ou por inatividade prolongada. Obrigações financeiras pendentes permanecem exigíveis após o encerramento.</p>

    <h2 id="s13">13. PROTEÇÃO DE DADOS (LGPD)</h2>
    <p>O tratamento de dados pessoais segue a nossa <a href="{{ route('lgpd.politica') }}">Política de Privacidade</a>, em conformidade com a Lei nº 13.709/2018 (LGPD). Academias, studios e lojas, ao tratarem dados de seus próprios alunos/clientes, atuam como <strong>controladores</strong> desses dados e devem cumprir a LGPD.</p>

    <h2 id="s14">14. ALTERAÇÕES DESTES TERMOS</h2>
    <p>Podemos atualizar estes Termos para refletir mudanças legais, técnicas ou de negócio. Alterações relevantes serão comunicadas pelos canais da Plataforma. O uso continuado após a atualização representa concordância com a nova versão.</p>

    <h2 id="s15">15. LEI APLICÁVEL, FORO E CONTATO</h2>
    <p>Estes Termos são regidos pelas leis da República Federativa do Brasil. Fica eleito o foro do domicílio do consumidor para dirimir controvérsias, quando aplicável a legislação consumerista.</p>
    <ul>
        <li><strong>Contato e suporte:</strong> <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a></li>
        <li><strong>Encarregado de Dados (DPO):</strong> <a href="mailto:suporte@snrfittech.com">suporte@snrfittech.com</a></li>
    </ul>

    <div class="highlighted">
        <strong>✅ Aceitação:</strong> ao usar a Plataforma, você confirma que leu e concorda com estes Termos e com o documento específico do seu perfil.
    </div>

@endsection
