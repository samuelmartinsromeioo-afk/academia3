# Memorando para análise jurídica — Termos de Uso e Política de Privacidade

**Plataforma:** SNR FIT
**Documentos revisados:** Termos de Uso (gerais + 5 por perfil) e Política de Privacidade
**Versão proposta:** 3.0 — em vigor desde 13/10/2026
**Status:** **MINUTA.** Não publicar antes de preencher as lacunas da seção 7 e da análise do advogado.

> **Aviso necessário.** Este memorando e os documentos que ele acompanha foram redigidos por quem
> desenvolve o sistema, **não por advogado**, e não constituem parecer ou aconselhamento jurídico.
> O objetivo é entregar ao advogado um texto que já descreve o sistema com exatidão, com a
> fundamentação pesquisada e com as decisões de negócio tomadas — para que a análise dele comece do
> conteúdo, e não da coleta de informação. Toda conclusão jurídica aqui é **hipótese a validar**.

---

## 1. Como a plataforma funciona (o que o contrato precisa descrever)

A SNR FIT é um **marketplace de serviços fitness**. Ela conecta:

| Perfil | O que faz na plataforma |
|---|---|
| **Aluno** | pessoa natural que busca, contrata e paga serviços e produtos |
| **Personal** | profissional autônomo de educação física que vende aulas, pacotes, fichas e avaliações |
| **Academia** | estabelecimento que vende mensalidade e planos, e vincula professores |
| **Studio** | estabelecimento que vende planos e aulas com grade de horários e lotação |
| **Loja** | estabelecimento que vende produtos (suplementos e correlatos) |

A plataforma fornece: cadastro e autenticação, vitrine com busca e geolocalização, agenda,
montagem/acompanhamento de treino, avaliação física, chat, avaliações de reputação, notificações e
**integração de pagamento com split**.

**A plataforma não presta educação física, não emprega ninguém, não vende os produtos anunciados e
não é instituição de pagamento.** O serviço que ela presta e pelo qual é remunerada é tecnologia +
intermediação.

### 1.1. Como o dinheiro circula (importante para o parecer)

1. O cliente paga **uma única cobrança**, criada junto ao **Asaas** (instituição de pagamento
   autorizada) **na conta da SNR FIT**, com um `split` configurado na origem.
2. Na liquidação, o Asaas credita **automaticamente**: o preço do profissional na subconta dele e a
   taxa de intermediação na conta da SNR FIT.
3. A SNR FIT **não custodia** recursos de terceiros nem faz transferência P2P: o rateio é executado
   pela instituição regulada.
4. **Exceção relevante:** quando o profissional ainda **não tem subconta habilitada** no Asaas, o
   `split` não é aplicado e **o valor inteiro é creditado na conta da SNR FIT**, sendo repassado
   depois, mediante solicitação de saque. **Ponto a avaliar** — ver seção 8.1.

---

## 2. A mudança de modelo que motivou esta revisão

Esta é a alteração central e é **material**, porque muda quanto cada parte recebe e paga.

### Antes (até a versão 2.1)

```
Serviço de R$ 100  →  cliente paga R$ 100  →  profissional recebe R$ 90
                                              plataforma retém R$ 10 (desconto)
```

### Agora (versão 3.0)

```
Serviço de R$ 100  →  cliente paga R$ 110  →  profissional recebe R$ 100 (integral)
                                              plataforma recebe R$ 10 (acréscimo)
```

**Consequências jurídicas que os documentos passaram a tratar:**

| Tema | Tratamento adotado | Onde |
|---|---|---|
| Quem paga a comissão | o **cliente**, como acréscimo ao preço | Termos gerais, cl. 7.2 |
| Transparência do preço | valor final + composição discriminada **antes** de contratar | cl. 7.3 |
| Tarifa de cartão | **repassada ao cliente** por gross-up; no PIX, absorvida pela plataforma | cl. 7.4 |
| Valor mínimo de cobrança | **R$ 19,90**, derivado da tarifa do PIX ÷ comissão | cl. 7.5 |
| Nota fiscal | cada parte emite a sua; a plataforma **não** é substituta tributária | cl. 7.6 |
| Reembolso | **integral**, inclusive a comissão da plataforma | cl. 8.1 |
| Recomposição | compensação em repasses futuros do profissional | cl. 8.5 e termos do Personal, cl. 7 |

### 2.1. Decisões de negócio já tomadas (não precisam de deliberação, só de validação)

1. **Idade mínima: 18 anos (aluno) e 21 (personal).** O sistema já aplicava; a Política de
   Privacidade dizia erradamente 14. Corrigido o documento, mantida a regra do sistema. Efeito
   colateral positivo: **não há tratamento de dado de menor**, o que afasta o art. 14 da LGPD.
2. **Reembolso integral no arrependimento**, incluindo a devolução da comissão da plataforma.
   Entendeu-se que retenção de parte do valor no arrependimento seria cláusula abusiva.
3. **Taxa igual para todos os perfis do mesmo tipo**, sem tabela negociada individualmente.
4. **Academia não paga mensalidade.** A cláusula anterior autorizava "valor mensal" que nunca
   existiu na operação; foi removida.

---

## 3. Fundamentação invocada, cláusula por cláusula

Para conferência da pertinência e suficiência de cada fundamento.

### Termos de Uso — gerais

| Cl. | Tema | Fundamento invocado |
|---|---|---|
| 0 | Identificação do titular | Decreto 7.962/2013, art. 2º, I e II; CDC art. 4º, III e 6º, III; LGPD art. 41 |
| 1.1 | Aceite eletrônico e prova | registro de versão/data/IP/UA, append-only |
| 2.1 | Natureza de provedor de aplicação | Lei 12.965/2014, art. 5º, VII |
| 2.2 | Não presta educação física | Lei 9.696/1998 |
| 2.2 | Não é instituição de pagamento | regulamentação do BCB (a confirmar a norma exata — seção 8.2) |
| 3.1 | Idade mínima / não tratamos menor | LGPD art. 14 (afastado pela maioridade) |
| 5 | Condutas vedadas e consequências | CC arts. 186 e 927 |
| 6 | Saúde, risco e omissão de informação | CDC art. 14 (excludentes); dever de informar do aluno |
| 7.3 | Transparência do preço total | CDC arts. 6º, III e 31; Decreto 7.962/2013, art. 2º, V |
| 7.7 | Vedação a contornar a intermediação | inadimplemento contratual; CC art. 422 (boa-fé) |
| 8.1 | Arrependimento 7 dias, reembolso integral | CDC art. 49; Decreto 7.962/2013, art. 5º |
| 8.2 | Cancelamento pelo mesmo meio eletrônico | Decreto 7.962/2013, art. 4º, V |
| 8.3 | Política de cancelamento do profissional | CDC art. 51 (abusividade) |
| 8.4 | Vício do serviço/produto | CDC arts. 18, 20 e 26 |
| 9.1 | Bônus é liberalidade, não remuneração | afastamento de vínculo; Lei 4.886/1965 (representação) |
| 9.1 | **Não é sorteio nem distribuição de prêmios** | Lei 5.768/1971 e Decreto 70.951/1972 — **ausência de álea** |
| 9.3 | Restituição de bônus indevido | CC art. 884 (enriquecimento sem causa) |
| 9.7 | KYC para pagamento de bônus | Lei 9.613/1998 |
| 10.1 | Responsabilidade por conteúdo de terceiro | **Lei 12.965/2014, art. 19** (e art. 21 para cena íntima) |
| 11 | Propriedade intelectual e imagem | Leis 9.279/1996, 9.610/1998, 9.609/1998; CC art. 20 |
| 12 | O que a plataforma faz e responde | CDC art. 14 (serviço próprio) |
| 14 | Limitação de responsabilidade | **restrita ao B2B**; CDC arts. 25 e 51, I preservados |
| 16.2 | Devido processo antes de suspender | boa-fé objetiva (CC art. 422); função social (CC art. 421) |
| 18 | Guarda de registros de acesso — 6 meses | **Lei 12.965/2014, art. 15**; LGPD art. 16, I |
| 19 | Alteração com aviso de 30 dias e saída sem ônus | **CDC art. 51, XIII** |
| 20 | Nulidade parcial, força maior, cessão | CC art. 393; prática contratual usual |
| 21 | Foro | **CDC art. 101, I** (consumidor) + eleição para B2B; MCI art. 11 (jurisdição) |

### Política de Privacidade

| Seção | Tema | Fundamento |
|---|---|---|
| 1 | Controlador e Encarregado | LGPD arts. 9º, I e 41 |
| 2 | Matriz controlador/operador por perfil | LGPD arts. 5º, VI e VII, e 39 |
| 3.3 | Dados sensíveis de saúde | LGPD art. 11, I (consentimento específico e destacado) |
| 4 | Tabela finalidade × base legal × retenção | LGPD art. 7º, I, II, V, VI, IX; art. 11, I |
| 5.1 | Transferência internacional | LGPD art. 33 |
| 6 | Retenção e exceções à eliminação | LGPD arts. 15, 16 (I) e 12 (anonimização) |
| 7 | Direitos do titular e prazo de resposta | LGPD art. 18 e § 1º (petição à ANPD) |
| 8 | Segurança e comunicação de incidente | LGPD arts. 46 e 48 |
| 9 | Cookies e medição com consentimento | LGPD art. 7º, I |
| 10 | Ausência de decisão automatizada | LGPD art. 20 |
| 11 | Maioridade | LGPD art. 14 |

---

## 4. O que os documentos passaram a dizer que antes não diziam

Lista para o advogado saber **onde olhar primeiro** — são as cláusulas novas ou substancialmente
alteradas:

1. **Identificação do titular** (cl. 0) — não existia. Era infração ao Decreto 7.962/2013.
2. **"O que a plataforma NÃO é"** (cl. 2.2) — não prestadora de educação física, não empregadora,
   **não instituição de pagamento**, não vendedora, não plano de saúde, não investimento.
3. **"O que você PODE fazer"** (cl. 4) — não existia como cláusula; estava implícito. Importa porque
   delimita a licença concedida e o que a plataforma promete entregar.
4. **"O que você NÃO PODE fazer"** (cl. 5) — reorganizado em cinco grupos (identidade, pessoas,
   segurança, dinheiro, conteúdo) com consequências graduadas.
5. **Marco Civil art. 19** (cl. 10.1) — **a principal blindagem da plataforma quanto a conteúdo de
   terceiro, e estava ausente.** Também a cláusula de que moderar é faculdade e não torna a
   plataforma autora do conteúdo.
6. **"Pelo que a SNR FIT responde"** (cl. 12) — separado de "pelo que não responde" (cl. 13). A
   assunção expressa do que é próprio fortalece a exclusão do que é alheio.
7. **Limitação de responsabilidade bipartida** (cl. 14) — teto de 12 meses **apenas no B2B**, com
   ressalva expressa de que nas relações de consumo a limitação não se aplica.
8. **Devido processo antes de suspender** (cl. 16.2) — prazo mínimo de 5 dias para manifestação,
   salvo urgência. Reduz o risco de a suspensão ser tida por arbitrária.
9. **Guarda de registros — 6 meses** (cl. 18) — obrigação legal que não estava declarada.
10. **Alteração com aviso de 30 dias e direito de sair sem ônus** (cl. 19) — endereça o art. 51,
    XIII, do CDC.
11. **Disposições gerais** (cl. 20) — nulidade parcial, tolerância, cessão, força maior,
    sobrevivência. Não existiam.
12. **Programa de indicação: não é sorteio** (cl. 9.1) — ver seção 8.3.
13. **Política de Privacidade: tabela de base legal por finalidade** — antes havia um parágrafo
    genérico citando três bases sem vinculá-las a finalidades, o que não atende ao art. 9º da LGPD.
14. **Matriz de papéis controlador/operador** (seção 2 da Política) — define a quem o titular
    dirige cada pedido.

---

## 5. Divergências entre documento e sistema que foram corrigidas

Estas eram **afirmações falsas** nos documentos publicados. Registro-as porque indicam exposição
pretérita, que o advogado pode querer avaliar.

| Divergência | Documento dizia | Sistema fazia | Exposição |
|---|---|---|---|
| **Idade mínima do aluno** | 14 anos (Política e Termos do Aluno) | recusava < 18 | Promessa de serviço a público que o sistema rejeita; e, se tivesse aceitado, tratamento de dado sensível de menor sem o regime do art. 14 da LGPD |
| **Idade mínima do personal** | 18 anos | exigia 21 | idem |
| **Data de atualização** | imprimia a **data de hoje**, sempre | — | Documento impresso afirmava vigência falsa; prejudica a prova de qual versão foi aceita |
| **Modelo de comissão** | "retém sua taxa e repassa o restante" | acréscimo ao preço | Contradição com a cobrança real |
| **Mensalidade da academia** | "pode estar sujeito a valor mensal" | nunca cobrado | Cláusula autorizando cobrança inexistente |
| **Identificação do titular** | ausente | — | Infração ao Decreto 7.962/2013, art. 2º |

**Medida preventiva adotada:** criei `tests/Feature/DocumentosLegaisTest.php`, que **quebra a
publicação** se um documento legal passar a exibir idade, taxa, valor mínimo, prazo ou meta
diferentes do que o sistema aplica, ou se voltar a imprimir a data de hoje como vigência. A
divergência dos 14 anos sobreviveu meses justamente porque nada comparava texto e código.

---

## 6. Pontos em que pedi uma posição e qual foi adotada

| Questão | Posição adotada | Fundamento da escolha |
|---|---|---|
| Idade mínima | **18/21**, corrigindo o documento | o sistema já aplicava; evita todo o regime de menor |
| Reembolso no arrependimento | **integral**, a plataforma devolve a própria comissão | retenção parcial no arrependimento tende a ser abusiva (CDC art. 49 + art. 51) |
| Dados da empresa | **preenchidos**; endereço e telefone **não divulgados** | ver a seção 7 — é decisão do titular, com risco assumido |

---

## 7. Identificação do titular — o que foi preenchido e o que foi omitido

Está **toda em um único arquivo**: `config/empresa.php`. Os documentos leem de lá.

Preenchido em **13/10/2026**:

| Campo | Valor |
|---|---|
| `razao_social` | SNR FIT |
| `cnpj` | 66.915.488/0001-95 |
| `dpo_nome` | Samuel (Encarregado de Dados — o art. 41 da LGPD exige **identidade**, não só e-mail) |

**Omitido por decisão do titular, que não os fornecerá:** endereço físico (`endereco`, `cidade`,
`uf`, `cep`), `comarca` e `telefone`. As chaves foram removidas do config, do
`Empresa::OBRIGATORIOS` e de todos os documentos — não há placeholder exibido e o aviso de "minuta"
deixou de existir.

**Risco assumido, que registro aqui para não se perder:** o art. 2º, I e II, do Decreto nº
7.962/2013 obriga o fornecedor a exibir, em local de destaque, nome empresarial, CNPJ e **endereço
físico e eletrônico**. A ausência do endereço físico é, por si, infração ao CDC, pode gerar
autuação pelos Procons e enfraquece as cláusulas de limitação de responsabilidade dos Termos (seções
14 e 15), porque o fornecedor que não se identifica plenamente tem menos crédito para invocar
limitação contra o consumidor. Pelo mesmo motivo, o único canal de atendimento divulgado passou a
ser o e-mail de suporte, que também é o canal do Encarregado (LGPD, art. 41).

A **eleição de foro para as relações B2B** (seção 21 dos Termos) deixou de nomear a comarca: passou
a ser "a comarca da sede da SNR FIT, conforme consta do seu registro no CNPJ". Um foro que o
aderente não consegue identificar não é eleição válida; pelo registro público ele é determinável.
Vale confirmar se a redação se sustenta sem o endereço no corpo do contrato.

Se o endereço for fornecido depois, o caminho é reintroduzir as chaves no config, voltar a listá-las
em `Empresa::OBRIGATORIOS` e exibi-las na seção 0 dos Termos e na seção 1 da Política.
`DocumentosLegaisTest::test_nenhum_documento_publica_lacuna_entre_colchetes` barra a lacuna
chegando ao documento em vigor, que era o papel do aviso de minuta.

Decisões adicionais que dependem do advogado e **não** estão nos documentos:

- Prazo de resposta ao titular: adotei **15 dias** (prazo do art. 19, II, da LGPD para a forma
  simplificada). Confirmar se é o compromisso que se quer assumir.
- Teto de responsabilidade no B2B: adotei **12 meses de taxas recebidas**. Validar se é adequado ao
  risco real.
- Prazo de manifestação antes de suspensão: adotei **5 dias**. Validar.
- Aviso prévio de alteração dos Termos: adotei **30 dias**. Validar.

---

## 8. Riscos e pontos de atenção que submeto à análise

### 8.1. Repasse sem subconta habilitada (prioridade alta)

Quando o profissional ainda não tem subconta no Asaas, **o valor integral da cobrança é creditado na
conta da SNR FIT** e repassado depois, mediante saque. Nesse intervalo a plataforma detém recurso de
terceiro.

**Perguntas:** essa retenção temporária, mesmo transitória e com registro contábil segregado, pode
caracterizar atividade de **instituição de pagamento** (gestão de conta de pagamento) e atrair a
regulamentação do BCB? Convém impor **prazo máximo** para o repasse nos Termos? Convém bloquear a
venda até a subconta existir — hoje a cobrança é permitida sem ela?

Os Termos (cl. 7.6) declaram que esse valor "não se confunde com receita da Plataforma". É
declaração contratual; **não resolve** a questão regulatória.

### 8.2. Enquadramento como instituição de pagamento (prioridade alta)

A cl. 2.2 afirma que a plataforma **não** é instituição de pagamento e que a liquidação é feita
integralmente pela instituição autorizada. Pedi ao texto que não citasse número de resolução
específico do BCB para não errar a norma vigente.

**Pergunta:** qual a norma a citar (ou é melhor não citar nenhuma?), e o arranjo descrito na seção
1.1 — cobrança na conta da plataforma com split configurado por ela — se sustenta nesse
enquadramento, inclusive considerando o item 8.1?

### 8.3. Pagamento de bônus em dinheiro, por Pix, a usuários (prioridade alta)

O Programa de Indicação paga **dinheiro** (não crédito, não desconto) por Pix, calculado sobre a
comissão que a plataforma auferiu com a conta indicada.

Os Termos sustentam (cl. 9.1) que **não** se aplica a Lei 5.768/1971 por **ausência de álea** — o
bônus decorre de fato objetivo e verificável, não de sorte. E que não é remuneração nem
representação comercial (Lei 4.886/1965), por ausência dos elementos.

**Perguntas:** a tese da ausência de álea se sustenta? O pagamento habitual e oneroso a quem
"traz clientes" pode ser requalificado como **comissão de representação comercial** ou
**corretagem** (CC art. 722 e segs.), com os efeitos daí decorrentes? Há dever de retenção de IR na
fonte nesses pagamentos? Como tratar o participante pessoa física sem CNPJ?

### 8.4. Comunicação promocional sem mecanismo de opt-out (prioridade média)

Os documentos prometem que comunicação **promocional** só ocorre com consentimento e **sempre com
meio de descadastramento**.

**Hoje o sistema não tem** nem campo de preferência de notificação, nem separação entre marketing e
transacional. Só existem avisos operacionais (lembrete de aula, pagamento confirmado), que os
documentos classificam como execução do contrato.

**Consequência prática:** enquanto o opt-out não existir no produto, **não se pode disparar
comunicação promocional** — o documento passaria a prometer o que o sistema não entrega. Está
sinalizado para a equipe; registro aqui para o advogado dimensionar o risco.

### 8.5. Classificação fiscal da taxa de intermediação (prioridade média)

Os documentos afirmam que cada parte emite o documento fiscal da sua própria operação e que a
plataforma **não** é substituta tributária. Deliberadamente **não** cito o item da lista da LC
116/2003, para não errar o enquadramento.

**Pergunta para o advogado tributarista/contador:** qual o item de serviço aplicável à taxa de
intermediação, qual o município competente para o ISS e há retenção a fazer sobre o repasse ao
profissional?

### 8.6. Vedação a contornar a intermediação (prioridade média)

A cl. 7.7 autoriza cobrar as taxas devidas pelas operações desviadas, apuradas "pelos registros da
Plataforma e pelos elementos de prova disponíveis". **Não** estipulei multa penal, por entender que
exigiria decisão jurídica.

**Perguntas:** a cláusula é suficiente e exequível? Convém fixar cláusula penal e em que patamar?
Cabe a ressalva que incluí (atender clientela própria **não** é vedado), para afastar leitura de
restrição à livre concorrência ou de exclusividade de fato?

### 8.7. Responsabilidade solidária no marketplace (prioridade alta)

Os documentos sustentam que a plataforma não é parte do contrato aluno-profissional e responde
apenas pelo serviço próprio (cl. 12 e 13).

**Pergunta central:** essa separação se sustenta diante da jurisprudência de **responsabilidade
solidária do marketplace** (CDC art. 7º, parágrafo único, e art. 25, § 1º), sobretudo agora que a
plataforma **recebe a cobrança em sua conta e define o preço final exibido** — dois elementos que
aumentam a aparência de participação na cadeia de fornecimento? A inversão do modelo de comissão
**piora** essa exposição? Se sim, convém ajustar a redação ou contratar seguro?

### 8.8. Dados sensíveis de saúde — adequação do consentimento (prioridade alta)

A anamnese é tratada com base em **consentimento** (art. 11, I). O sistema permite deixá-la em
branco e removê-la.

**Perguntas:** o consentimento está sendo colhido de forma **específica e destacada**, como exige o
art. 11, I — ou está embutido no aceite geral dos Termos? (Tecnicamente: há registro de aceite dos
Termos com versão, mas **não** um registro de consentimento separado para o dado de saúde.)
Convém hipótese alternativa — "tutela da saúde, por profissional de saúde" (art. 11, II, "f") — para
o acesso do profissional? A matriz de papéis da seção 2 da Política está correta ao tratar o
personal como controlador dessa parcela?

### 8.9. Vitrine, ordenação e "pioneiro" (prioridade baixa)

A Política afirma que não há decisão automatizada que afete interesses (art. 20 da LGPD) e que a
ordenação usa critérios declarados. Existem no sistema selos de "novo profissional" e "pioneiro" e
ordenação por distância e avaliação.

**Pergunta:** a ordenação e os selos exigem transparência adicional, ou a redação atual basta?

### 8.10. Transferência internacional (prioridade média)

A Política invoca o art. 33 da LGPD com base em necessidade para execução do contrato e
"salvaguardas oferecidas pelo operador".

**Pergunta:** há contratos/cláusulas-padrão firmados com esses operadores (hospedagem, e-mail, push,
Meta)? Sem o instrumento, a base invocada fica frágil.

---

## 9. Onde está cada coisa (para conferência do texto)

| Documento | Arquivo | URL |
|---|---|---|
| Termos gerais | `resources/views/legal/termos-index.blade.php` | `/termos` |
| Termos — Aluno | `resources/views/legal/termos-aluno.blade.php` | `/termos/aluno` |
| Termos — Personal | `resources/views/legal/termos-personal.blade.php` | `/termos/personal` |
| Termos — Academia | `resources/views/legal/termos-academia.blade.php` | `/termos/academia` |
| Termos — Studio | `resources/views/legal/termos-studio.blade.php` | `/termos/studio` |
| Termos — Loja | `resources/views/legal/termos-loja.blade.php` | `/termos/loja` |
| Política de Privacidade | `resources/views/lgpd/politica.blade.php` | `/politica-de-privacidade` |
| Identificação do titular | `config/empresa.php` | — |
| Versão e resumo das mudanças | `config/termos.php` | — |
| Regras do Programa de Indicação | `config/indicacao.php` | — |
| Taxas | `config/taxas.php` | — |

**Todas as páginas têm botão de impressão** e layout próprio para papel, com marca d'água — podem ser
enviadas em PDF ao advogado direto do navegador.

### 9.1. Os números dos documentos são lidos do sistema

Nenhum percentual, prazo, idade ou valor está escrito no texto dos documentos: todos são lidos das
constantes e dos configs que o sistema efetivamente aplica (`AsaasService::feeRate()`,
`AsaasService::valorMinimo()`, `CadastroHelper::IDADE_MINIMA_*`, `config('indicacao.*')`).

**Implicação para a revisão:** se o advogado alterar um desses números no texto, a alteração **não
terá efeito** — é preciso mudar o config correspondente, e a mudança se propaga para os seis
documentos e para o sistema ao mesmo tempo. Isso é deliberado: foi a ausência dessa amarração que
produziu a divergência dos 14 anos.

---

## 10. Reaceite: efeito operacional da publicação

`config('termos.versao')` está em **3.0**. Subir essa versão faz **toda conta logada cair numa tela
de reaceite** no próximo acesso, com o resumo do que mudou (`config('termos.resumo')`), e o aceite é
registrado em `termo_aceites` com versão, data, IP e user-agent, de forma append-only.

Como a mudança é na contraprestação (quem paga a comissão), entendeu-se que o reaceite é o
comportamento correto, e não um efeito colateral a evitar.

**A confirmar com o advogado:** o resumo apresentado na tela de reaceite é suficiente para dar
validade ao consentimento, ou convém detalhá-lo? O texto atual está em `config/termos.php`, chave
`resumo`.

---

## 11. Sugestão de ordem de leitura

1. Esta seção 2 (a mudança de modelo) e a seção 1.1 (como o dinheiro circula) — sem isso o resto não
   se avalia.
2. Seção 8, riscos — em especial 8.1, 8.2, 8.3, 8.7 e 8.8, que são os de maior impacto.
3. Termos gerais, cláusulas 7, 8, 9, 12, 13 e 14 — o núcleo econômico e de responsabilidade.
4. Política de Privacidade, seções 2 e 4 — papéis e bases legais.
5. Seção 7 deste memorando — as lacunas a preencher.
