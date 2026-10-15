# Auditoria legal e preenchimento do App Store Connect — SNR FIT

**Data:** 14/10/2026 · **Versão dos documentos antes:** 3.0 (13/10/2026) · **depois:** 3.1 (14/10/2026)
**Motivo imediato:** reprovação do iOS 1.1 (8) pela Guideline 5.1.2(i) — o rótulo de privacidade declara
*Nome* e *Localização Precisa* como usados para **rastreamento**.

Todo fato afirmado aqui tem evidência em `arquivo:linha`. O que o código não responde está na seção (g).
Caminhos relativos a `www/academia3` (site) e `www/snrfit-app` (app).

---

## (a) Mapa de operação — como a empresa realmente opera

### A1. Localização

| Pergunta | Resposta | Evidência |
|---|---|---|
| Onde é coletada | **Só no app.** O site não pede geolocalização do navegador em nenhum lugar | ausência de `navigator.geolocation` em `resources/views/` |
| Em que momento | **Só ao tocar em "Perto de mim"** na tela Serviços — nunca na abertura do app | `src/screens/ExplorarScreen.js:139` (`togglePerto`), botão em `:308` |
| Primeiro ou segundo plano | **Primeiro plano apenas.** Só `requestForegroundPermissionsAsync`; não existe chamada de background em nenhum arquivo | `src/screens/ExplorarScreen.js:179` |
| Precisa ou aproximada | **Aproximada** — `Accuracy.Balanced` (~100 m), não `Highest` | `src/screens/ExplorarScreen.js:192` |
| Vai ao servidor | **Sim**, como query string `lat`/`lng` de `GET /api/v1/explorar/{tipo}` | `src/screens/ExplorarScreen.js:92` |
| Fica gravada | **Não.** O servidor usa lat/lng apenas dentro do `SELECT` (Haversine) para calcular `distancia_km` e ordenar. Nenhum `INSERT`/`UPDATE` com esses valores; `clientes` **não tem** colunas latitude/longitude | `app/Http/Controllers/Api/ExplorarController.php:99-128` |
| Por quanto tempo | Vida da requisição |  |
| Aparece em log | **Não no log da aplicação.** `LogSecurityEvents` grava `$request->path()` — sem query string — e só em 401/403/419/429/5xx | `app/Http/Middleware/LogSecurityEvents.php:26-37` |
| Coordenadas de profissionais/estabelecimentos | Origem: **endereço que eles mesmos cadastram**, geocodificado no Nominatim (site) e no app após o ViaCEP. Guardadas em `personals`/`academias`/`studios`/`lojas`. Exibidas na vitrine e usadas para ordenar | `app/Services/Geocoder.php:19-52`; `src/utils/br.js` (`geocodificar`); anonimização zera as colunas em `app/Services/ExclusaoDeConta.php:108,123,136` |

> ⚠️ **Ponto fora do app:** a coordenada viaja na **query string**, então o *access log do servidor web*
> (Apache/nginx) registra a URL completa, inclusive `lat`/`lng`. Isso está fora do código auditado.
> Ver pergunta **Q6**.

### A2. Exclusão de conta

Mesmo motor nos dois lados: `app/Services/ExclusaoDeConta.php`.

| | App | Site |
|---|---|---|
| Caminho de telas | Configurações → **Privacidade e meus dados** → Excluir minha conta (e também direto em Configurações → Conta) | menu da conta → **Privacidade e meus dados** → Excluir minha conta |
| Rota | `DELETE /api/v1/conta` | `POST /meus-dados/excluir` |
| Controller | `app/Http/Controllers/Api/ContaController.php:destroy` | `app/Http/Controllers/Cadastro/LgpdController.php:excluirConta` |
| Confirmação exigida | senha | senha + checkbox |
| Tela | `src/screens/ExcluirContaScreen.js` | `resources/views/lgpd/meus-dados.blade.php` |

**O que é apagado** (`ExclusaoDeConta.php:53-75`, aluno): anamnese, fotos de progresso (arquivo + linha),
medidas, metas, registros de exercício, treinos concluídos, mensagens de chat, notificações, foto de perfil.

**O que é anonimizado:** nome → "Conta removida", e-mail → `removido_{id}@snrfit.local`, senha → aleatória de
40 caracteres, e zeram-se telefone, endereço, CEP, cidade, altura, peso, condição clínica.
Profissionais/estabelecimentos perdem também CPF/CNPJ, CREF, chave PIX e os IDs Asaas, e o `status` vai a
`rejeitado` (`:87-141`).

**O que é retido, e por quê:** a **linha** da conta sobrevive anonimizada — apagá-la quebraria a integridade
referencial de `payments`, que a retenção fiscal exige. Registros financeiros ficam (prazo fiscal/prescricional,
descritos como 5 anos) e registros de acesso por 6 meses (art. 15 do Marco Civil).

**Imediata, irreversível, sem carência.** Não há status "desativada" nem rota de reativação.

**Por perfil** — divergências reais encontradas:

| Perfil | Apaga mensagens | Apaga notificações | Observação |
|---|---|---|---|
| Cliente | ✅ `:64` | ✅ `:65` | o mais completo |
| Personal | ✅ `:83` | ✅ `:84` | fotos do portfólio apagadas `:79-82` |
| Academia | ❌ | ❌ | ver **Q7** |
| Studio | ❌ | ❌ | ver **Q7** |
| Loja | ❌ | ❌ | ver **Q7** |

**O que NÃO acontece na exclusão** (e os documentos agora dizem isso):

- **Saldo a receber:** nada é pago nem bloqueado automaticamente. `personal_saques` e as subcontas Asaas não
  são tocados pelo serviço.
- **Assinaturas recorrentes:** **não são canceladas.** `ExclusaoDeConta` não toca em `subscriptions` nem chama
  o Asaas. ⚠️ Ver **Q1** — é o achado operacional mais sério desta auditoria.
- **Agendamentos futuros:** `agendas` não é apagada; as aulas continuam na agenda do profissional.
- **Bônus de indicação:** perdido por cláusula 9.10, mas o serviço **não** cancela `cupom_usos` nem
  `indicacao_saques` — a perda é contratual, não executada em código. Ver **Q8**.
- **Avaliações** feitas pelo usuário: permanecem (não há delete de `avaliacoes`).

### A3. Consentimentos implementados (depois desta auditoria)

| Consentimento | Onde é coletado | Como é registrado | Como se revoga | Efeito da revogação |
|---|---|---|---|---|
| **Aceite de termos** | cadastro (5 portas) e tela de reaceite | `termo_aceites`, append-only, com versão/IP/user-agent/origem | não se revoga (é contrato); discordar = encerrar a conta | cláusula 19 |
| **Saúde / anamnese** | checkbox destacado na tela da anamnese, **app e site** | `consentimentos`, append-only, data/IP/origem | Privacidade e meus dados, ou a própria tela da anamnese, nos dois lados | apaga a anamnese; **conta continua ativa**; confirmação por escrito |
| **Cookies / medição** | banner do site (Aceitar / Recusar) | cookie `snrfit_consent` + linha em `consentimentos` quando há sessão | botão **Preferências de cookies** (rodapé dos documentos legais) ou Privacidade e meus dados | nada vai à Meta, **inclusive na renovação de assinatura** |
| **Localização** | permissão do SO, após aviso explicativo no app | no próprio SO | Ajustes do aparelho | sem ordenação por distância; busca por nome/cidade/bairro intacta |
| **Notificações push** | permissão do SO, após aviso explicativo | no próprio SO + `push_tokens` | Ajustes do aparelho | sem push; avisos continuam na caixa *Avisos* |
| **Comunicação promocional** | **não existe** | — | — | nada a revogar (ver A6) |

Arquivos: `database/migrations/2026_10_14_000001_create_consentimentos_table.php`,
`app/Models/Consentimento.php`, `resources/views/partials/banner-cookies.blade.php`.

### A4. Pagamentos e valores — número do código vs. documento

| Item | Valor no código | Fonte | Documento | Confere |
|---|---|---|---|---|
| Taxa de intermediação | **10%**, acrescida ao preço | `config/taxas.php:33` → `AsaasService::feeRate()` | lido da config pela view | ✅ |
| Valor mínimo de cobrança | **R$ 19,90** (derivado: `pix_fixa / feeRate`) | `app/Services/AsaasService.php:178-182` | lido da config | ✅ |
| Tarifa PIX | R$ 1,99, absorvida pela plataforma | `config/taxas.php:40` | cláusula 7.4 | ✅ |
| Tarifa cartão | 4,99% + R$ 0,49, repassada ao aluno (gross-up) | `config/taxas.php:43-44` | cláusula 7.4 | ✅ |
| Saque mínimo da indicação | **R$ 20,00** | `config/indicacao.php:59` | cláusula 9.7 | ✅ |
| % do bônus | 10% **da comissão** | `config/indicacao.php:44` | cláusula 9.3 | ✅ |
| Janela de indicação | **35 dias**, da aprovação | `config/indicacao.php:47` | cláusula 9.2 | ✅ (agora diz "corridos") |
| Meta de alunos | **6** | `config/indicacao.php:54` | cláusula 9.4 | ✅ |
| Teto do saque automático | R$ 300 / R$ 2.000 dia | `config/indicacao.php:75-76` | "limite publicado no painel" | ✅ |
| Saque automático ligado? | **`false`** por padrão | `config/indicacao.php:74` | cláusula 9.7 descreve as duas vias | ✅ |
| Reembolso | **manual**, fila do admin | `app/Http/Controllers/EstornoController.php:47-101` | cláusula 8.5 (reescrita) | ✅ agora |
| Chargeback | mesmo efeito do reembolso | cláusula 8.5 | — | ✅ |

`DocumentosLegaisTest` já travava a igualdade desses números — e continua verde.

### A5. Exportação, correção e atendimento ao titular

| Direito | App | Site | Prazo |
|---|---|---|---|
| Exportar (portabilidade) | `POST /api/v1/conta/exportar/email` → JSON anexado ao e-mail da conta | `GET /meus-dados/exportar` → download JSON | imediato |
| Acessar | o próprio pacote exportado | idem | imediato |
| Corrigir | Editar perfil (`Api\PerfilController`) | Editar perfil | imediato |
| Revogar saúde | `DELETE /api/v1/anamnese` | `POST /meus-dados/revogar-saude` | imediato |
| Excluir | `DELETE /api/v1/conta` | `POST /meus-dados/excluir` | imediato |
| Qualquer outro | e-mail do Encarregado | idem | **15 dias corridos** |

Coleta compartilhada: `app/Services/ExportacaoDeDados.php`. Inclui o histórico de aceites e de consentimentos.
Nunca inclui senha (hash) nem chaves Asaas.

### A6. Envio de dados a terceiros

| Destinatário | O que vai | Base legal | Depende de consentimento | Evidência |
|---|---|---|---|---|
| **Asaas** | nome, e-mail, CPF/CNPJ, valor, split | execução de contrato | não | `app/Services/AsaasService.php` |
| **Meta — Pixel (browser)** | `PageView` + conversões, `_fbp`/`_fbc`, IP, UA | consentimento | **sim**, e já era | `resources/views/partials/meta-pixel.blade.php:11,17` |
| **Meta — CAPI com request** | idem + e-mail/telefone/nome/cidade/UF/id em SHA-256 | consentimento | **sim**, e já era | `app/Services/MetaConversionsService.php:91` (`send`) |
| **Meta — CAPI server-to-server** (renovação de assinatura) | `Purchase` + hashes de e-mail/telefone/nome/cidade/UF/id | consentimento | **SIM — corrigido nesta auditoria.** Antes **não checava nada** | `MetaConversionsService.php:94-101`; chamador `PaymentController.php:1273` |
| **WhatsApp (Meta Cloud API)** | telefone + texto do aviso operacional | execução de contrato | não | `app/Services/WhatsAppService.php` |
| **E-mail (SMTP)** | e-mail + texto | execução de contrato | não | `app/Services/NotificacaoService.php:129` |
| **Expo → APNs/FCM** | token do aparelho + título/corpo | execução de contrato | permissão do SO | `app/Services/ExpoPushService.php` |
| **Nominatim (OSM)** | endereço do profissional | execução de contrato | não | `app/Services/Geocoder.php:31` |
| **ViaCEP / BrasilAPI** | CEP digitado | execução de contrato | não | `config/csp.php:113-114` |
| **Hospedagem** | tudo, como operador | execução de contrato | não | — |
| **Corretores de dados** | **nada** | — | — | nenhum SDK |

**O app não envia nada a terceiros de marketing**: não há Meta SDK, SDK de anúncios, SDK de atribuição nem
`expo-tracking-transparency` em `package.json`.

---

## (b) Tabela de prazos — antes e depois

| # | Prazo | ANTES | DEPOIS | Onde |
|---|---|---|---|---|
| 1 | Arrependimento | "7 (sete) dias corridos, contados da contratação ou do recebimento" — sem regra de fim de semana, sem prazo de resposta nosso | 7 dias **corridos**; dia inicial não conta, último conta, **prorroga para o 1º dia útil** se cair em fim de semana/feriado; resposta nossa em **5 dias úteis**; devolução ordenada em **10 dias corridos**; crédito conforme o meio | geral 8.1, aluno 7, loja 4 |
| 2 | Manifestação antes de suspensão | "não inferior a 5 (cinco) dias" — natureza omissa, termo inicial omisso, sem consequência | **5 dias corridos** do **recebimento** da comunicação; resposta em **5 dias úteis**; em urgência, comunicação em **2 dias úteis** e reavaliação; **descumprido, a medida não se sustenta** | geral 16.2, personal 13, academia 10, studio 9, loja 9 |
| 3 | Saque do bônus (manual) | "5 (cinco) dias úteis após a validação" | mantido, com **termo inicial** (validação), pendência cadastral suspendendo o início, e "o valor continua devido" se descumprido | geral 9.7 |
| 4 | Resposta ao titular de dados | "em até 15 dias" | **15 dias corridos** do **recebimento**; pedido após 18h/fim de semana conta do 1º dia útil; identidade pendente suspende | Política 7 |
| 5 | Aviso de alteração dos Termos | "antecedência mínima de 30 (trinta) dias" | **30 dias corridos** do **envio**, só para alterações **desfavoráveis**; **não cumprido, a alteração não é oponível**; 3 hipóteses fechadas de vigência imediata | geral 19, academia 2 |
| 6 | Janela de indicação | "35 dias, contada da aprovação" | **35 dias corridos** da aprovação; **não prorroga** (não é prazo para agir) | geral 9.2, 9.4 |
| 7 | Registros de acesso | "6 (seis) meses" | **6 meses corridos de cada acesso**, e dito que é **mínimo legal**, não máximo | geral 18, Política 4 |
| 8 | Retenção financeira | "5 anos após a transação" | mantido | Política 4 |
| 9 | Teto de responsabilidade | "12 (doze) meses anteriores ao fato gerador" | mantido | geral 14 |
| 10 | Reclamação por vício | "30 dias / 90 dias" (art. 26) | mantido, **corridos** e com termo inicial explícito na loja | geral 8.4, loja 4 |
| 11 | Reembolso — crédito | "nos prazos operacionais do Processador" / "5 a 10 dias úteis no cartão" (**divergiam entre si**) | **ordenação em 10 dias corridos** (nosso) + crédito do meio: poucos dias no PIX, **até duas faturas** no cartão. Uma redação só nos dois documentos | geral 8.5, aluno 7 |
| 12 | Repasse sem subconta | **não existia prazo** | **5 dias úteis** da solicitação regular, com definição de "regular" | geral 7.6, personal 6.5, academia 5, studio 5, loja 5 |
| 13 | Cancelamento de assinatura | "pode ser feito pelo mesmo meio eletrônico" — sem prazo, sem caminho | **imediato**, com caminho de telas nos dois ambientes e confirmação por escrito | geral 8.2, aluno 6 |
| 14 | Exclusão de conta | **não existia prazo nem efeito** | **imediata, sem carência, irreversível**, com confirmação por escrito **antes** da eliminação | geral 16.1, Política 6 |
| 15 | Revogação de consentimento | "a qualquer momento" — sem caminho, sem efeito | **imediata**, com caminho nos dois ambientes, confirmação por escrito e "a conta continua ativa" | geral 17.1, Política 3.3, aluno 4 |
| 16 | Aviso a alunos no encerramento | "são informados" — sem prazo | **2 dias úteis** | geral 16.3, academia 10, studio 9 |
| 17 | Denúncia de conteúdo | "analisaremos e responderemos" — sem prazo | **5 dias úteis**; risco a pessoas, **imediato** | geral 10.4, 12.1 |
| 18 | Devolução de produto (frete) | **não existia** | custo da devolução **é da loja**; informar como devolver em **2 dias úteis** | loja 5 |
| 19 | Atendimento em geral | "em prazo razoável" | **5 dias úteis** (atendimento) / **15 dias corridos** (titular de dados) | geral 12 |
| 20 | **Regra geral de contagem** | **não existia** | nova **cláusula 1.4**: 8 regras (dia inicial/final, corridos vs. úteis, prorrogação, termo inicial, corte das 18h, fuso de Brasília, consequência do nosso descumprimento, consequência do seu) | geral 1.4 |

---

## (c) Alterações nos documentos — cláusula, antes, depois, motivo

### Configuração

| Arquivo | Antes | Depois | Motivo |
|---|---|---|---|
| `config/empresa.php:62` | `razao_social = 'SNR FIT'` | `'SNR FIT TECH LTDA'` | era o **nome fantasia**, não a razão social. O art. 2º, I, do Decreto 7.962/2013 exige nome empresarial — é por ele que o consumidor localiza quem responde |
| `config/termos.php:14,16` | `3.0` / `2026-10-13` | `3.1` / `2026-10-14` | nova versão |
| `config/termos.php:resumo` | 5 itens sobre a inversão da comissão | 6 itens sobre prazos, procedimentos, app e consentimentos | a tela de reaceite mostra esta lista; descrever a mudança errada esvazia o valor probatório do aceite |

### Termos gerais (`legal/termos-index.blade.php`)

| Cláusula | Antes | Depois | Motivo |
|---|---|---|---|
| **1.3** | sem definição de dia | define **dia útil** e **dia corrido** | sem isso "5 dias" é ambíguo |
| **1.4** | **não existia** | 8 regras de contagem + consequência de descumprimento de cada lado | em contrato de adesão a dúvida se interpreta contra quem redigiu (art. 47 CDC) |
| **7.6** | repasse sem subconta "mediante solicitação", sem prazo | **5 dias úteis** da solicitação regular, com definição de regular | obrigação de pagar sem prazo é obrigação sem exigibilidade clara |
| **8.1** | prazo e canal vagos | contagem explícita + canal + **5 dias úteis** de resposta + **10 dias corridos** de devolução + nota de que o atraso não extingue o direito | art. 49 CDC + art. 5º do Decreto 7.962/2013 |
| **8.2** | "pelo mesmo meio eletrônico" | caminho de telas **no app e no site**, efeito imediato, confirmação por escrito como comprovante, e aviso de que cancelar ≠ reembolso | o app não tinha como cancelar; a confusão cancelar/reembolsar é a principal fonte de disputa |
| **8.5** | "nos prazos operacionais do Processador" | separa **o que é nosso** (ordenar em 10 dias corridos) do **que é do banco**; e descreve a **fila manual**, que é como o reembolso funciona de verdade | a redação anterior sugeria automatismo que não existe |
| **9.2 / 9.4** | "35 dias" | "35 dias **corridos**", e dito que **não prorroga** | é período de apuração, não prazo para agir — a exceção precisava ser nomeada |
| **9.7** | "até o limite definido pela Plataforma" | diz que o saque **só existe no site** e por quê; termo inicial do prazo; pendência suspende; valor continua devido | o app mostra o painel mas não solicita — o documento não dizia |
| **12** (obrigações) | "responder em prazo razoável" | prazos nomeados + nova obrigação de **confirmar por escrito** exclusão, cancelamento e revogação | "razoável" não é prazo |
| **12.1** | **não existia** | tabela de **14 direitos** com canal, caminho de telas, quem executa, prazo, confirmação e efeito; e regra de que, se o caminho não existir, prevalece a tabela | era o pedido central: deixar explícito o modo de operar |
| **16.1** | "pelos canais da Plataforma, sem penalidade" | caminho, **efeito imediato e irreversível**, sem reativação, confirmação antes da eliminação, e a distinção entre excluir conta / cancelar assinatura / revogar saúde | usuário excluía a conta querendo só parar de pagar |
| **16.2** | "prazo razoável — não inferior a 5 dias" | natureza, termo inicial, prazo de resposta, prazo da urgência e **consequência do nosso descumprimento** | devido processo sem prazo definido não é devido processo |
| **16.3** | "alunos são informados" | **2 dias úteis** | — |
| **17** | só papéis de controlador/operador | + **17.1**: os dois consentimentos, como se dão, como se registram, como se revogam; e por que notificação não é consentimento | a Política prometia revogação que não existia |
| **18** | "6 (seis) meses" | "6 meses **corridos de cada acesso**", e que é **mínimo legal** | "6 meses" sem termo inicial é indeterminado |
| **19** | 30 dias para "alterações relevantes"; vigência imediata para lei/erro material | distingue **desfavorável** (30 dias corridos do envio, não oponível se descumprido) de **ampliação de direito** (imediata), com 3 hipóteses fechadas + nota enquadrando a própria 3.1 | sem isso, a 3.1 entraria em vigor violando a cláusula 19 do documento que ela altera |
| **20** (Comunicações) | "e-mail, aviso no produto ou notificação" | lista os avisos, afirma que **todos são operacionais**, que **não há envio promocional**, e como desativar | exigência de política de loja e base legal distinta na LGPD |

### Política de Privacidade (`lgpd/politica.blade.php`)

| Seção | Antes | Depois | Motivo |
|---|---|---|---|
| **3.2** | "Localização aproximada — somente no momento em que você usa Perto de mim" | + não é gravada, não é associada ao cadastro, vai só dentro da consulta, e há aviso antes do pedido; + parágrafo separando a coordenada **do estabelecimento** (essa sim armazenada) da do aluno (nenhuma) | era a afirmação mais importante para o rótulo da Apple e estava incompleta |
| **3.3** | "coletados mediante consentimento específico e destacado" + "pode revogar" | descreve o checkbox real, o registro com data/IP/origem, o caráter append-only, o caminho da revogação, o efeito imediato, a confirmação escrita e que **a conta continua ativa**; separa medidas/fotos | **a promessa não tinha nada no código** — era o pior defeito encontrado |
| **4** (tabela) | — | + linha de **retenção dos registros de consentimento** (5 anos); localização "não armazenada"; Meta marcada como **só no site** e com "nem na renovação" | a renovação furava o gate |
| **6** | "anonimizamos o perfil e eliminamos os dados" | detalha **apagado / anonimizado / mantido**, o efeito imediato e irreversível, e a confirmação antes | o titular tinha de adivinhar o que sobrava |
| **7** | "onde você acessa, exporta e exclui por conta própria" (no app **não havia exportação**) | lista exata do que se faz em cada ambiente (download no site, e-mail no app), onde se corrige, prazo com termo inicial e corte de 18h | a Política afirmava função inexistente |
| **9** | "rever a escolha limpando os cookies do navegador" | botão **Preferências de cookies** com caminho; vale para envios do servidor, inclusive renovação; + caixa nova afirmando que **o app não tem Pixel/SDK/ATT** | mandar o titular limpar o navegador não é revogação pelo mesmo meio (art. 8º) |
| **10** | lista de 4 permissões em texto | **tabela**: permissão × quando é pedida × para quê × o que acontece se recusar; + menção ao aviso prévio | é o texto que o revisor da Apple compara com o rótulo |
| **10** (caixa) | "não usa microfone, contatos, SMS" | + agenda, **Apple Health/Google Fit**, arquivos, e "não declara nenhuma dessas permissões" | fecha a dúvida de *Health & Fitness* lido do sistema |

### Termos por perfil

| Documento | Alterações |
|---|---|
| **Aluno** | §2 encerramento com os dois ambientes; §4 anamnese reescrita (checkbox, registro, revogação, conta ativa); §6 cancelamento com caminho + comprovante + cancelar ≠ reembolso; §7 contagem dos 7 dias, canal, prazos nossos, crédito por meio de pagamento; §11 lista dos direitos por ambiente + prazo + referência a 12.1 |
| **Personal** | §4 encerramento (efeito imediato, sem reativação) e direitos de titular; §6.5 **5 dias úteis** do saque sem subconta, com definição de regular e consequência; §7 "7 dias corridos", aviso em 2 dias úteis, manifestação em 5 dias corridos, resposta em 5 dias úteis; §13 devido processo completo |
| **Academia** | §2 "30 dias corridos do envio" + não exigibilidade se descumprido; §5 reembolso com prazos + **repasse sem subconta** (não existia); §10 devido processo + **§10.1 encerramento pela academia** (não existia) |
| **Studio** | §5 e §9 equivalentes à academia, + **§9.1 encerramento pelo studio** |
| **Loja** | §4 arrependimento com termo inicial (**recebimento**), prorrogação e **frete da devolução por conta da loja**; art. 26 com prazos corridos e termo inicial; §5 **reescrito** — era cópia do texto de academia/studio falando de "aluno" e "serviço já prestado", que não descreve venda de produto; + repasse sem subconta; §9 devido processo + remoção imediata por risco ao consumidor + **§9.1 encerramento pela loja** |
| **Layout** (`legal/layout.blade.php`) | CSS de tabela (não existia — a tabela 12.1 sairia sem formatação e estouraria no celular), print styles, e botão **Preferências de cookies** no rodapé + include do banner |

---

## (d) Divergências código × documento

### Resolvidas nesta auditoria (você escolheu "o documento estava certo, corrigir o código")

| # | Divergência | Evidência do defeito | Correção |
|---|---|---|---|
| **D1** | Política §9 prometia "enquanto você não aceitar, **nada** é enviado à Meta", mas `trackServer` não checava consentimento algum — e renovação de assinatura **criada no app** (onde banner nunca existiu) mandava nome/e-mail/telefone/cidade/UF/id em hash **todo mês** | `MetaConversionsService.php` (antes): `trackServer` recebia `array $userData` e só verificava `enabled()`; chamador em `PaymentController.php:1273` | `trackServer` agora exige o **titular** (não um array) e consulta `Consentimento::concedido(...)`. A porta insegura deixou de existir. `MetaConversionsService.php:94-114` |
| **D2** | Política §7 e Aluno §11 afirmavam exportação "no app e no site"; **no app não havia nem tela nem endpoint** | `ConfiguracoesScreen.js` tinha só links e exclusão; nenhuma rota `/conta/exportar` em `routes/api.php` | `ExportacaoDeDados` (coleta compartilhada), `GET /api/v1/conta/exportar`, `POST /api/v1/conta/exportar/email`, e tela `PrivacidadeScreen.js` |
| **D3** | Política §3.3 e Aluno §4 afirmavam consentimento "específico e destacado" e revogação "a qualquer momento". Não havia **coluna**, o site só tinha um parágrafo dizendo que salvar equivalia a consentir, o app **não tinha aviso nenhum**, e não existia rota de exclusão da anamnese | `create_anamneses_table` sem campo de consentimento; `cliente/Anamnese.blade.php:77` (aviso passivo) | tabela `consentimentos`, checkbox obrigatório (`accepted`) nos dois lados, `DELETE /api/v1/anamnese` e `POST /meus-dados/revogar-saude`, confirmação por `AvisoService::consentimentoSaudeRevogado` |
| **D4** | Termos 8.2 prometiam cancelamento "pelo mesmo meio eletrônico da contratação" e "confirma por escrito". O app **cria** assinatura mensal e **não cancelava** (rota exigia `session('cliente_id')`), e nada era enviado | `routes/api.php` (grupo web) + `PaymentController::cancelarAssinatura` devolvia só JSON | `cancelarAssinaturaDe()` compartilhado + `confirmarCancelamento()`; `GET /api/v1/assinaturas`, `POST /api/v1/assinaturas/{id}/cancelar`, tela `AssinaturasScreen.js` |
| **D5** | Razão social nos documentos era o nome fantasia | `config/empresa.php:62` | `SNR FIT TECH LTDA` |
| **D6** | Política §9 mandava "rever a escolha limpando os cookies do navegador" — o banner dava `return` quando o cookie existia, não havia como reabrir | `meta-pixel.blade.php:51` (antes) | `banner-cookies.blade.php` separado, `window.snrfitCookies.abrir()`, botões nas páginas legais e em meus-dados |
| **D7** | Loja §5 falava de "aluno" e "parcela do serviço já prestada" numa venda de produto | cópia literal do texto do studio | reescrito para produto/comprador/frete |
| **D8** | Nenhuma confirmação escrita de exclusão de conta — e, se enviada depois, iria para `removido_{id}@snrfit.local` | `ExclusaoDeConta::executar` | `ExclusaoDeConta::confirmarAoTitular()`, chamada **antes** da anonimização nos dois fluxos |
| **D9** | `ios.infoPlist` recebia **3** chaves de localização, duas delas com o texto **em inglês** padrão do plugin (`"Allow $(PRODUCT_NAME) to access your location"`), inclusive `NSLocationAlwaysAndWhenInUseUsageDescription` — declarando intenção de localização **em segundo plano** que o app nunca pede, contra a própria Política | `node_modules/expo-location/plugin/build/withLocation.js:22-28` + `@expo/config-plugins/build/ios/Permissions.js:32` (default quando não informado) | `app.json`: as duas chaves vão a `false` (o plugin **deleta** a chave), e `isIosBackgroundLocationEnabled: false` |

### Resolvidas na segunda rodada (você pediu "arrume todas")

| # | Divergência | O que estava errado | Correção |
|---|---|---|---|
| **Q1** | **Excluir a conta não cancelava a assinatura** — a mais cara de todas | `ExclusaoDeConta` não tocava `subscriptions` nem chamava o Asaas: o aluno excluía a conta e **seguia sendo cobrado todo mês**, sem conta para cancelar, sem e-mail (já anonimizado) e sem nada ligando a cobrança a uma pessoa. Cobrança indevida continuada, silenciosa das duas pontas | `ExclusaoDeConta::encerrarCobrancas()`. **Aluno** → cancela as assinaturas dele no Asaas e localmente. **Profissional/estabelecimento** → NÃO cancela as dos alunos (dinheiro de terceiro); avisa cada um com as saídas, como a cláusula 16.3 promete. `executar()` agora faz cancelar → confirmar → anonimizar numa ordem só, para o chamador não ter ordem a lembrar |
| **Q7** | Exclusão incompleta em academia/studio/loja | **Eu errei no relatório anterior**: disse que faltava apagar `Mensagem` nos três. Não falta — `mensagens` só tem `personal_id`/`cliente_id`, esses perfis **não têm chat**. O furo real era `notificacoes` (só cliente e personal apagavam) e, pior, **`push_tokens` não era apagado para nenhum dos cinco** — o token é o identificador do aparelho da pessoa | `limparCanaisDeAviso($tipo, $id)` aplicado nos cinco perfis |
| **Q8** | Bônus de indicação não pago sobrevivia ao encerramento | A cláusula 9.10 diz que se perde; o saldo continuava `liberado` e sacável, atrelado a titular inexistente | `cancelarBonusDeIndicacao()`. Só mexe no que **não** está em saque: `saque_id` preenchido = dinheiro em trânsito no Asaas, e reescrever o livro embaixo de uma transferência é pior que a inconsistência |
| **Q9** | Academia gravava anamnese do aluno sem consentimento algum | Dado sensível de terceiro entrando sem base legal demonstrável — e sem nem registrar quem afirmou ter autorização. (Só academia tem essa rota; studio não tem) | `consentimento_titular` obrigatório nas duas portas + colunas `declarado_por_*`. É **declaração do estabelecimento**, não consentimento por procuração: a base legal segue sendo do titular, que revoga pelo caminho normal, e o estabelecimento responde pela veracidade |
| **Q10** | Comentário do `EstornoController` descrevia o split 90/10 | Revogado em 13/10/2026 | Reescrito, dizendo o que mudou e por que o raciocínio do método continua válido |
| **Q11** | `RECEIVE_BOOT_COMPLETED` no Android | Declarada por `expo-notifications` para restaurar notificação **local agendada**; o app não agenda nenhuma | Bloqueada em `android.blockedPermissions` |
| **Q6** | Coordenada no access log do servidor web | A busca por proximidade é GET, então a URL (com `lat`/`lng`) entra no log do Apache/nginx por 6 meses — a Política dizia "não é guardada", verdade quanto ao banco e quase não quanto ao log | O app **arredonda a coordenada para 3 casas (~110 m)** antes de enviar, a mesma ordem de grandeza da precisão que já pedimos. O log passa a conter só a localização aproximada que declaramos. POST seria "mais correto" e quebraria o app publicado, por ganho pequeno. Documentado na Política |
| **Q2** | Medição de renovação na Meta | Decisão mantida: com o gate novo, renovação de assinatura **criada no site por quem aceitou cookies** segue sendo medida; a criada no app nunca é (não há banner lá). É o comportamento correto e está descrito na Política | — |
| **Q12** | Vigência imediata da 3.1 | Mantida, com a cláusula 19 reescrita para distinguir alteração desfavorável (30 dias) de ampliação de direito (imediata) | — |
| **Q13** | Opt-out de notificação | Nada a fazer: todos os avisos são operacionais e não existe envio promocional no backend | — |

### Ainda pendentes — dependem de dado que só você tem

| # | O que falta | Por que não posso resolver |
|---|---|---|
| **Q-A** | **Conta de demonstração** para as Review Notes | Preciso de e-mail e senha de uma conta real. Se for de aluno, indique também um profissional aprovado e visível na vitrine, senão o revisor testa "Perto de mim" e não vê resultado |
| **Q3** | **Endereço físico** da empresa | `config/empresa.php:36-50` registra a decisão de não divulgar. O art. 2º, I e II, do Decreto 7.962/2013 **exige** endereço físico em destaque; a ausência é infração ao CDC por si e enfraquece as cláusulas de limitação de responsabilidade. A cláusula 21 também não nomeia a comarca por isso. Não invento endereço |
| ~~**Q4**~~ | ~~Nome completo do Encarregado~~ | ✅ **Resolvido em 14/10/2026**: `dpo_nome = 'Samuel Martins Romeiro'`. Aparece agora na cláusula 0 e 21 dos Termos e nas seções 1 e 13 da Política, lido do config |
| **Q5** | **Telefone/WhatsApp de atendimento** | Hoje o único canal divulgado é e-mail. Existe número para publicar? |
| **Q6-b** | **Retenção do access log** do servidor web | Preciso saber qual servidor (Apache/nginx) e a política de retenção para descrever o prazo com precisão na Política — hoje ela se apoia no prazo legal de 6 meses |
| **CREF-1** | **URLs de consulta dos conselhos que faltam** | `config/cref.php` tem link direto só para **SP, RS, SC, BA, PA, AP** — as que consegui conferir. As outras 21 UFs caem na busca nacional do CONFEF, que funciona mas dá mais trabalho ao admin. Link inventado é pior que ausente: manda o admin a uma página que não existe e ele conclui que o problema é o profissional. Me passe as URLs, ou eu as levanto numa próxima rodada |
| **CREF-2** | **Consulta automática ao conselho** | Não existe API pública nacional (confirmei: CONFEF é busca HTML; os regionais são sistemas distintos, vários em ASP.NET com `__VIEWSTATE`). A única via automática é **API paga** de consulta a conselhos (ex.: Infosimples). Duas consequências a decidir antes: custo por consulta e **envio do CREF/CPF do profissional a um operador novo**, o que exige atualizar a Política (art. 7º, V, e art. 39 da LGPD). Quer que eu avalie? |

---

## (e) Permissões e notificações — inventário e o que foi corrigido

### iOS — chaves de uso

| Chave | Antes | Depois | Como é gerada |
|---|---|---|---|
| `NSLocationWhenInUseUsageDescription` | português, mas sem dizer que a posição não é guardada | português, ampliado | `app.json` → plugin `expo-location` |
| `NSLocationAlwaysAndWhenInUseUsageDescription` | ⚠️ **presente, em inglês** (default do plugin) | ❌ **removida** (`false`) | — |
| `NSLocationAlwaysUsageDescription` | ⚠️ **presente, em inglês** | ❌ **removida** (`false`) | — |
| `NSCameraUsageDescription` | português ✅ | mantida | plugin `expo-image-picker` |
| `NSPhotoLibraryUsageDescription` | português ✅ | mantida | idem |
| `NSPhotoLibraryAddUsageDescription` | ausente ✅ **correto** | ausente | o app **não salva** imagens na galeria (não há `expo-media-library`) |
| `NSMicrophoneUsageDescription` | ausente ✅ | ausente | `microphonePermission: false` já estava certo |
| `NSUserTrackingUsageDescription` | **ausente** | ausente — **correto** | não há rastreamento (ver seção f) |
| `UIBackgroundModes: location` | ausente ✅ | ausente, agora explicitamente | `isIosBackgroundLocationEnabled: false` |

**SDKs de rastreamento/anúncio/atribuição procurados e NÃO encontrados:** Facebook/Meta SDK,
`expo-tracking-transparency`, AppsFlyer, Adjust, Branch, AdSupport/IDFA, Google Mobile Ads, Firebase Analytics.
Dependências totais: 18, todas em `package.json` — nenhuma de publicidade.

### Android

| Permissão | Origem | Usada? | Ação |
|---|---|---|---|
| `ACCESS_COARSE_LOCATION` / `ACCESS_FINE_LOCATION` | `expo-location` | sim (primeiro plano) | mantidas |
| `ACCESS_BACKGROUND_LOCATION` | só se habilitada | **não declarada** | confirmado `false` |
| `FOREGROUND_SERVICE` / `FOREGROUND_SERVICE_LOCATION` | só se habilitado | **não declaradas** | confirmado `false` |
| `CAMERA` | `expo-image-picker` | sim | mantida |
| `READ_EXTERNAL_STORAGE` | `expo-image-picker` | sim, em Android ≤ 12 | **mantida** — remover quebraria a seleção de fotos nessas versões |
| `WRITE_EXTERNAL_STORAGE` | `expo-image-picker` | **não** (sem efeito desde a API 29; o picker grava em cache do app) | ❌ **bloqueada** (`android.blockedPermissions`) |
| `POST_NOTIFICATIONS` | `expo-notifications` | sim | mantida |
| `RECEIVE_BOOT_COMPLETED` | `expo-notifications` | **não** (o app não agenda notificação local) | **mantida** — ver **Q11** |
| `INTERNET` | `expo-video` | sim | mantida |

### Avisos antes do pedido do sistema

| Permissão | Antes | Depois |
|---|---|---|
| **Localização** | ❌ nenhum aviso: `requestForegroundPermissionsAsync()` direto no toque | ✅ `PrePermissao` explicando finalidade, "só em primeiro plano", "não é guardada" e o que funciona recusando. `ExplorarScreen.js:266-280`. Negada em definitivo (`canAskAgain: false`) → orienta os Ajustes em vez de insistir (`:155-165`) |
| **Notificações** | ❌ prompt disparado **no login**, sem contexto | ✅ `PrePermissao` listando os avisos reais e afirmando "nada de propaganda". `App.js` (`PrePermissaoPush`) + `AppContext.js:220-247`. Já concedida → registra o token em silêncio |
| **Câmera / Fotos** | contexto implícito (o usuário toca em "tirar foto"/"escolher") | mantido — o contexto já é o próprio ato |

**Funcionamento com permissão negada:** confirmado. `fetchPage` só envia `lat`/`lng` quando
`perto && coords` (`ExplorarScreen.js:92`); sem isso a busca por nome/cidade/bairro e os chips de cidade
funcionam igual.

### Notificações enviadas pelo sistema

Canal unificado: uma chamada entrega em até 4 lugares — aviso in-app, push, WhatsApp e e-mail
(`NotificacaoService::conta`, `:47-65`).

| Gatilho | Quem recebe | Canais | Natureza | Consentimento | Desativável |
|---|---|---|---|---|---|
| Nova mensagem no chat | o outro lado | in-app + push | operacional | não | push: Ajustes |
| Pagamento confirmado | aluno + recebedor | 4 canais | operacional | não | push: Ajustes |
| Pacote confirmado | aluno e personal | 4 | operacional | não | idem |
| Academia/studio contratado | aluno e estabelecimento | 4 | operacional | não | idem |
| Aula de studio contratada | aluno e studio | 4 | operacional | não | idem |
| Aula cancelada pelo personal | aluno | 4 | operacional | não | idem |
| Dia cancelado pelo personal | alunos do dia | 4 | operacional | não | idem |
| Ficha de treino pronta | aluno | 4 | operacional | não | idem |
| Relatório mensal | aluno | 4 | operacional | não | idem |
| Pedido pago da loja | loja | 4 | operacional | não | idem |
| Devolução concluída/recusada | aluno | 4 | operacional | não | idem |
| Cadastro aprovado/rejeitado | profissional | 4 | operacional | não | idem |
| **Lembrete de aula (~90 min antes)** | aluno | 4 | operacional | não | idem |
| **Lembrete diário** | personal | 4 | operacional | não | idem |
| Reposição de aula | aluno | 4 | operacional | não | idem |
| **Conta encerrada** 🆕 | titular | e-mail + WhatsApp | operacional | não | — |
| **Assinatura cancelada** 🆕 | aluno | 4 | operacional | não | — |
| **Consentimento de saúde revogado** 🆕 | aluno | 4 | operacional | não | — |
| Exportação de dados 🆕 | titular | e-mail (anexo) | operacional | não | — |
| Recuperação de senha | titular | e-mail | operacional | não | — |

**Nenhuma notificação promocional existe.** `ExpoPushService::enviar()` (envio em lote) **nunca é chamado** —
só `paraDestinatario()`. Não há tabela de preferência/opt-out nem separação marketing/transacional.
Os únicos disparos proativos são `aluno:lembrete-aula` e `personal:lembrete-diario`.

**Avisos exigidos que faltavam, e agora existem:** confirmação de exclusão de conta 🆕, de cancelamento de
assinatura 🆕 e de revogação de consentimento 🆕. **Alteração de termos** já tinha a parede de reaceite com o
resumo do que mudou (`VerificaAceiteTermos` + `config('termos.resumo')`).

### Site

| Item | Antes | Depois |
|---|---|---|
| Banner de cookies — aceitar | ✅ | ✅ |
| Banner — recusar | ✅ | ✅ |
| Banner — **reabrir para mudar** | ❌ só limpando o navegador | ✅ botão no rodapé dos documentos legais e em meus-dados; mostra a escolha atual |
| Escolha registrada no servidor | ❌ só cookie | ✅ `POST /consentimento/cookies` + `consentimentos` quando há sessão |
| Pixel nas páginas legais | não incluído ✅ | mantido assim — o banner foi separado justamente para isso |
| "Privacidade e meus dados" | existia (ler / exportar / excluir) | + painel de **cookies** e de **dados de saúde** com estado atual e revogação |
| Geolocalização do navegador | **não existe** | não existe |

---

## (f) App Store Connect

### f.1 — App Privacy

Convenção: **Linked** = ligado à identidade do usuário. **Tracking** = na definição da Apple (cruzar dados do
app com dados de terceiros para publicidade/medição, ou enviar a *data broker*).

| Tipo de dado (Apple) | Coletado | Tipo específico | Finalidades | Linked | Tracking | Evidência |
|---|---|---|---|---|---|---|
| **Contact Info** | ✅ Sim | Name, Email Address, Phone Number, Physical Address | App Functionality | **Sim** | **Não** | `Api\AuthController@register`; `Api\RegisterController` |
| **Health & Fitness** | ✅ Sim | Health (anamnese, PAR-Q, medicamentos, cirurgias), Fitness (treinos, cargas, frequência, medidas) | App Functionality | **Sim** | **Não** | `Api\ProgressoController:211+`; `Api\TreinosGestaoController`. **Não** lê Apple Health |
| **Financial Info** | ✅ Sim | Payment Info *(apenas chave PIX e histórico; **nunca** número de cartão)* | App Functionality | **Sim** | **Não** | `Api\PaymentController`; cartão só no checkout hospedado do Asaas |
| **Location — Precise** | ❌ **Não** | — | — | — | — | `Accuracy.Balanced` (~100 m), `ExplorarScreen.js:192`. ⚠️ O rótulo atual declara *Precise* — **está errado** |
| **Location — Coarse** | ✅ Sim | Coarse Location | **App Functionality** apenas | **Não** | **Não** | usada na consulta e descartada; `ExplorarController.php:99-128` |
| **Sensitive Info** | ✅ Sim | Health data (a Apple classifica dado de saúde aqui também) | App Functionality | **Sim** | **Não** | idem Health & Fitness |
| **Contacts** | ❌ Não | — | — | — | — | nenhuma permissão de contatos |
| **User Content** | ✅ Sim | Photos or Videos (perfil, progresso, produtos), Other User Content (mensagens de chat, avaliações, observações) | App Functionality | **Sim** | **Não** | `Api\PerfilController@foto`; `Api\ChatController` |
| **Browsing History** | ❌ Não | — | — | — | — | o app não tem navegador nem histórico |
| **Search History** | ❌ Não | — | — | — | — | a busca de `ExplorarScreen` não é persistida |
| **Identifiers** | ✅ Sim | User ID, Device ID *(token de push do Expo)* | App Functionality | **Sim** | **Não** | `Api\PushTokenController:18-34`. **Não** há IDFA/IDFV publicitário |
| **Purchases** | ✅ Sim | Purchase History | App Functionality | **Sim** | **Não** | `Api\PaymentController@index` |
| **Usage Data** | ❌ Não | — | — | — | — | não há SDK de analytics no app |
| **Diagnostics** | ❌ Não | — | — | — | — | sem Crashlytics/Sentry; `ErrorBoundary.js` é local |
| **Other Data** | ❌ Não | — | — | — | — | — |

**Mudanças a fazer no rótulo atual:**
1. **Location** — trocar *Precise* por **Coarse**, finalidade **App Functionality**, **não linked**, **não tracking**.
2. **Name** — manter coletado e linked, mas **desmarcar tracking**.
3. **Marcar "Data Not Used to Track You"** em todos os tipos.

### f.2 — Existe rastreamento na definição da Apple?

**Não, a partir do app.** Fundamentos:

1. **Nenhum SDK** de anúncio, atribuição ou analytics em `package.json` (18 dependências, todas verificadas).
2. **Nenhum identificador publicitário** — sem AdSupport, sem IDFA, sem `expo-tracking-transparency`.
3. **Nenhum envio a data broker.**
4. **Localização nunca sai do propósito funcional**: não é gravada, não é associada ao cadastro e não vai a terceiro.
5. **O único caminho que chegava à Meta a partir de dado coletado no app** era a renovação de assinatura
   (`PaymentController.php:1273` → `trackServer`), que **não checava consentimento**. Esse caminho **agora
   exige consentimento registrado** (`MetaConversionsService.php:94-114`), e **o app não tem banner nem
   qualquer tela que conceda esse consentimento** — logo, para quem veio pelo app, o valor é sempre "não
   concedido" e nada é enviado. Travado por teste: `ConsentimentoDadosTest::test_trackServer_nao_envia_nada_sem_consentimento_de_marketing` (`Http::assertNothingSent()`).
6. O **Meta Pixel existe apenas no site**, com consentimento, e é uma plataforma diferente.

**Saída da Apple aplicável:** a **primeira** — *corrigir o rótulo de privacidade*. Não é caso de "só rastreia em
outra plataforma" (não há rastreamento vindo do app nem antes nem depois, bastava não ter declarado) e **não é
necessário implementar ATT**: ATT sem rastreamento é pedir ao usuário uma permissão que não se usa, o que é
causa de reprovação por si.

> **Honestidade sobre o antes:** se a Apple perguntar, a resposta factual é que o rótulo foi preenchido de forma
> conservadora/errada; não que o app rastreava e parou. O único envio a terceiro de marketing partia do
> **servidor**, no ciclo de renovação, e foi fechado por consentimento. Veja **Q2** se você quiser manter a
> medição de renovação para quem assinou **pelo site** (hoje ela funciona, pois ali há banner).

### f.3 — Review Notes (inglês)

```
SNR FIT is a Brazilian fitness marketplace connecting students with personal
trainers, gyms, studios and supplement stores.

LOCATION — WHERE AND WHY
Location is requested ONLY when the user taps the "Perto de mim" ("Near me")
button on the "Serviços" (Services) tab. Before the system prompt appears, the
app shows an in-app explanation screen describing the purpose and what still
works if the user declines.
- Purpose: sorting gyms, studios, trainers and stores by distance.
- Accuracy: COARSE only (Location.Accuracy.Balanced, ~100 m). We never request
  precise location.
- Foreground only. We never request "Always" / background location, and the
  Info.plist contains no NSLocationAlwaysAndWhenInUseUsageDescription and no
  "location" UIBackgroundModes entry.
- The coordinate is sent as a query parameter of a single search request, used
  server-side to compute distance, and is NEVER stored in our database and
  never associated with the user account.
- Declining is fully supported: search by name, city or neighbourhood keeps
  working. Nothing in the app is blocked.

NO TRACKING
The app contains no advertising, attribution or analytics SDK (no Meta/Facebook
SDK, no AppsFlyer, no Adjust, no Firebase Analytics, no AdSupport/IDFA). No data
collected in the app is linked with third-party data for advertising or ad
measurement, and nothing is shared with data brokers. The privacy label has been
updated accordingly (Location: Coarse, App Functionality, not linked, not used
for tracking).

ACCOUNT DELETION (Guideline 5.1.1(v))
Settings tab -> "Privacidade e meus dados" -> "Excluir minha conta".
Also available directly at Settings -> "Conta" -> "Excluir minha conta".
The user confirms with their account password. Deletion is immediate and
permanent: personal and health data are erased, the profile is anonymised, all
device sessions are revoked, and a written confirmation is e-mailed before
erasure. Only financial records (Brazilian tax law) and access logs (6 months,
Brazilian Internet Civil Framework, art. 15) are retained, unlinked from
identity.

DATA RIGHTS IN-APP
The same screen also offers data export (a machine-readable JSON copy e-mailed
to the account address) and withdrawal of health-data consent (deletes the
health questionnaire without closing the account).

SUBSCRIPTIONS
Monthly plans are charged OUTSIDE Apple's IAP because they pay a real-world
in-person or remote service provided by an independent third party (a personal
trainer, gym or studio), not digital content — Guideline 3.1.3(e) "Goods and
Services Outside of the App". The user can cancel at any time inside the app:
"Meus pagamentos" -> "Minhas assinaturas" -> "Cancelar assinatura".

DEMO ACCOUNT
Email:    [PREENCHER]
Password: [PREENCHER]
Notes:    [PREENCHER — se a conta de demonstração for de ALUNO, indique também
          um personal/academia aprovado e visível na vitrine para que o revisor
          consiga testar a busca "Perto de mim" com resultados.]

The backend is live at https://snrfit.com.br — no server address configuration
is needed.
```

### f.4 — Resposta ao Resolution Center (inglês)

```
Hello,

Thank you for the review. Regarding Guideline 5.1.2(i) and the privacy label
declaring "Name" and "Precise Location" as used for tracking:

That declaration was incorrect on our side, and we have corrected the privacy
label rather than the app's behaviour, because the app does not perform
tracking as defined in the App Store guidelines.

1) PRECISE LOCATION — NOT COLLECTED
The app only ever requests COARSE location. We call
Location.getCurrentPositionAsync with accuracy: Location.Accuracy.Balanced
(~100 m). We never request precise location, and we never request background or
"Always" location — the Info.plist contains no
NSLocationAlwaysAndWhenInUseUsageDescription and no "location" background mode.
We have updated the label to declare Coarse Location only.

2) LOCATION IS NOT USED FOR TRACKING
Location is requested only when the user taps "Perto de mim" ("Near me") in the
Services tab, after an in-app screen explaining the purpose. The coordinate is
sent as a parameter of that single search request so the server can sort results
by distance; it is never written to our database, never associated with the user
account, and never shared with any third party. Declining the permission leaves
the app fully usable through search by name, city or neighbourhood.

3) NAME IS NOT USED FOR TRACKING
The app contains no advertising, attribution or analytics SDK whatsoever — no
Meta/Facebook SDK, no AppsFlyer, no Adjust, no Branch, no Google Mobile Ads, no
Firebase Analytics, and no AdSupport/IDFA access. No data collected in the app
is linked with third-party data for advertising or advertising measurement, and
we do not share data with data brokers.

4) WHAT WE ALSO FIXED
Reviewing this with care, we found and removed two unused iOS location usage
strings that our build tooling was adding by default (including the "Always"
variant), which could have suggested a background-location intent we never had.
We also audited the one server-side marketing integration we operate on our
WEBSITE (Meta Conversions API, website only, consent-gated) and made it require
a recorded consent in every case, including recurring-subscription renewals. The
app has no such integration and no consent banner, so nothing originating from
the app reaches it.

Accordingly, we have:
- changed Location from Precise to Coarse, purpose "App Functionality", not
  linked to the user, not used for tracking;
- unmarked "used for tracking" for Name and for every other data type;
- selected "Data Not Used to Track You";
- added an in-app pre-permission explanation before the system location prompt.

Account deletion remains available in-app at Settings -> "Privacidade e meus
dados" -> "Excluir minha conta" (and directly under Settings -> "Conta"), and
the same screen now also offers data export and withdrawal of health-data
consent.

We did not implement App Tracking Transparency because the app does not track;
requesting that permission without any tracking would itself be misleading to
users. Please let us know if you would like any additional detail or a
walkthrough of the location flow.

Thank you,
SNR FIT TECH LTDA
```

---

## (g) Perguntas para o Samuel

### Bloqueantes para o envio à Apple

**Q-A. Conta de demonstração.** Preencha os `[PREENCHER]` das Review Notes. Se for conta de aluno, indique também
um profissional aprovado e visível na vitrine — sem resultado, o revisor não consegue testar "Perto de mim".

**Q-B. Assinatura fora do IAP (Guideline 3.1.3(e)).** A versão reprovada não citou isso, mas é o próximo risco:
o app cobra plano mensal por PIX/cartão fora do IAP. A justificativa é serviço do mundo real prestado por
terceiro — está redigida nas Review Notes. **Confirma que é essa a posição?**

### Operacionais sérios

**Q1. Excluir conta não cancela assinatura.** `ExclusaoDeConta.php:41-51` não toca `subscriptions`. Hoje, aluno
que exclui a conta **continua sendo cobrado todo mês**, e não há mais conta para ele cancelar nem e-mail para
avisá-lo. Isto é cobrança indevida continuada. Quer que eu cancele toda assinatura ativa no Asaas durante a
exclusão (recomendado), ou que a exclusão seja **bloqueada** enquanto houver assinatura ativa, pedindo que
cancele antes?

**Q7. Exclusão incompleta para academia/studio/loja.** Mensagens de chat e notificações **não** são apagadas
nesses três perfis, ao contrário de cliente e personal (`ExclusaoDeConta.php:98-141`). Conteúdo de conversa
sobrevive à "exclusão". Equalizo com os outros dois perfis?

**Q9. Anamnese preenchida pela academia/studio.** `Api\AcademiaController.php:160-204` grava dado de saúde do
aluno sem registro de consentimento nenhum. Preferência: (a) declaração do estabelecimento ("declaro ter obtido
o consentimento do titular"), registrada em `consentimentos`; (b) exigir que o próprio aluno consinta no app
antes de o estabelecimento poder preencher; (c) deixar como está e descrever assim no documento. Não mexi porque
muda o modo de operar do estabelecimento, não só o texto.

### Decisões de produto / política

**Q2. Medição de renovação para quem assinou pelo site.** Hoje: com o gate novo, renovação de assinatura
**criada no site por quem aceitou cookies** continua sendo medida na Meta; a criada no app nunca é. Isso é o
desejado, ou você prefere desligar a medição de renovação por completo (uma linha a remover em
`PaymentController.php:1273`)?

**Q3. Endereço físico.** `config/empresa.php:36-50` registra a decisão de não divulgar endereço, e o custo: o
art. 2º, I e II, do Decreto 7.962/2013 **exige** endereço físico em destaque, e a ausência é infração ao CDC por
si, além de enfraquecer as cláusulas de limitação de responsabilidade. A cláusula 21 também não nomeia a comarca
do foro por causa disso. Mantém, ou passa o endereço para eu reintroduzir?

**Q4. Nome completo do Encarregado.** `dpo_nome = 'Samuel'`. O art. 41 da LGPD pede a identidade do Encarregado;
só o primeiro nome é identificação fraca. Qual o nome completo?

**Q5. Telefone/WhatsApp de atendimento.** Hoje o único canal divulgado é e-mail. O Decreto 7.962/2013 pede
endereço eletrônico (atendido) e o CDC exige atendimento eficaz. Existe número para publicar?

**Q6. Access log do servidor web.** A coordenada do "Perto de mim" viaja na query string, então o log do
Apache/nginx grava `lat`/`lng`. A Política afirma que a localização não é armazenada — verdade quanto à
aplicação, mas o log é um armazenamento. Caminhos: (a) mudar a busca por proximidade para **POST** (quebra
compatibilidade com o app publicado); (b) não registrar query string nesse endpoint no servidor web;
(c) descrever o log na Política. Preciso saber qual servidor web e qual a política de retenção desse log.

**Q8. Bônus de indicação no encerramento.** A cláusula 9.10 diz que é perdido, mas nada em código cancela
`cupom_usos`. Implemento o cancelamento, ou mudo a cláusula para dizer que o bônus liberado é pago mesmo após o
encerramento?

**Q10. Comentário obsoleto.** `EstornoController.php:12-13` ainda descreve o split 90/10, revogado em 13/10.
É só comentário (não afeta comportamento), mas é o tipo de coisa que engana o próximo leitor. Corrijo?

**Q11. `RECEIVE_BOOT_COMPLETED` no Android.** Declarada por `expo-notifications`, serve para restaurar
notificações **locais agendadas** após reboot — e o app não agenda nenhuma (todo push é remoto). Dá para
bloquear, mas é mexer em entranha do módulo de notificação por ganho pequeno. Bloqueio?

**Q12. Versão 3.1 com vigência imediata.** Reescrevi a cláusula 19 para distinguir alteração **desfavorável**
(30 dias de aviso) de **ampliação de direito** (imediata), e enquadrei a 3.1 na segunda. Sem isso, a 3.1
entraria em vigor violando a cláusula 19 do próprio documento que ela altera. Se preferir o caminho
conservador, é mudar `vigente_desde` para **13/11/2026** e avisar antes — mas aí as correções de texto ficam 30
dias fora do ar, e são elas que corrigem promessas que o produto já cumpre hoje. Confirma a vigência imediata?

**Q13. Opt-out de notificação.** Não existe tabela de preferência nem separação marketing/transacional — hoje
tudo é operacional, então está correto. Se houver plano de comunicação promocional, isso precisa existir antes
(base legal distinta na LGPD + exigência de política de loja). Há esse plano?

---

## Arquivos alterados (para revisão)

### Site (`www/academia3`) — 22 arquivos

**Novos (7)**
`database/migrations/2026_10_14_000001_create_consentimentos_table.php` ·
`app/Models/Consentimento.php` · `app/Services/ExportacaoDeDados.php` ·
`app/Mail/ExportacaoDadosMail.php` · `resources/views/emails/exportacao-dados.blade.php` ·
`resources/views/partials/banner-cookies.blade.php` · `docs/auditoria-legal-apple.md`
**Testes novos (2):** `tests/Feature/ConsentimentoDadosTest.php` · `tests/Feature/PrivacidadeTelasTest.php`

**Alterados (13)**
`config/empresa.php` (razão social) · `config/termos.php` (3.1 + resumo) ·
`routes/web.php` (2 rotas) · `routes/api.php` (5 rotas) ·
`app/Services/MetaConversionsService.php` (gate de consentimento) ·
`app/Services/ExclusaoDeConta.php` (confirmação ao titular) ·
`app/Services/AvisoService.php` (aviso de revogação) ·
`app/Http/Controllers/PaymentController.php` (cancelamento compartilhado + confirmação) ·
`app/Http/Controllers/Api/PaymentController.php` (assinaturas) ·
`app/Http/Controllers/Api/ContaController.php` (exportação + consentimentos) ·
`app/Http/Controllers/Api/ProgressoController.php` (consentimento + revogação) ·
`app/Http/Controllers/Cadastro/LgpdController.php` · `app/Http/Controllers/Cadastro/AnamneseController.php`

**Views legais (8):** `legal/layout.blade.php` · `legal/termos-index.blade.php` ·
`legal/termos-aluno.blade.php` · `legal/termos-personal.blade.php` · `legal/termos-academia.blade.php` ·
`legal/termos-studio.blade.php` · `legal/termos-loja.blade.php` · `lgpd/politica.blade.php` ·
`lgpd/meus-dados.blade.php` · `cliente/Anamnese.blade.php` · `partials/meta-pixel.blade.php`

### App (`www/snrfit-app`) — 11 arquivos

**Novos (3):** `src/components/PrePermissao.js` · `src/screens/PrivacidadeScreen.js` ·
`src/screens/AssinaturasScreen.js`

**Alterados (8):** `app.json` (chaves iOS/Android) · `App.js` (2 telas + pré-permissão de push) ·
`src/context/AppContext.js` (fluxo de push) · `src/notifications/push.js` (`situacaoPermissaoPush`) ·
`src/screens/ExplorarScreen.js` (pré-permissão de localização) ·
`src/screens/AnamneseScreen.js` (consentimento + revogação) ·
`src/screens/ConfiguracoesScreen.js` (entrada para Privacidade) ·
`src/screens/PagamentosScreen.js` (atalho para assinaturas) ·
`scripts/rotas-backend.json` (regerado, estava desatualizado)

> O repositório do app já tinha **12 outros arquivos modificados antes** desta auditoria
> (`src/api/opcoesCadastro.js`, `src/components/UI.js`, `src/utils/br.js`, `src/api/precos.js` e telas de
> cadastro/detalhe). **Não encostei neles** — ao revisar o diff, separe-os.

**Nada foi comitado, enviado ou publicado.**

---

## Verificação do CREF — as duas camadas

Pedido: "no cadastro de personal quero que o próprio site já verifique se o CREF é verídico ou não".
Entregue em duas camadas, porque **autenticidade e formato são coisas diferentes** e prometer a
primeira entregando a segunda é publicidade enganosa.

### Camada 1 — formato, automática e bloqueante

`app/Support/Cref.php`. Antes: `cref => 'required|string|max:30'` nas duas portas — "aaa",
"não tenho" e "000000-X/ZZ" passavam, e o número ia para o perfil público ao lado da foto como se
tivesse sido conferido. Pior que campo ausente: parecia credencial validada.

Agora valida matrícula (3–8 dígitos, não só zeros) e **UF real**, aceita a categoria quando vem
(G/P/E/L), normaliza para `123456-G/SP` antes de gravar e recusa o resto com mensagem que explica o
formato. **Não há dígito verificador de CREF publicado** — ao contrário de CPF/CNPJ, é matrícula
sequencial do conselho —, então nenhuma conta aritmética pode dizer "este número é impossível".

A categoria é **opcional** de propósito: está na carteira, mas é a parte que o profissional menos
decora, e barrar quem digitou `123456/SP` seria trocar cadastro legítimo por campo bonito.

### Camada 2 — existência e atividade, humana e registrada

Só o conselho pode dizer se o registro existe e está ativo. O admin **já aprovava cada personal à
mão** (todo cadastro nasce `pendente`); o que faltava era (a) onde conferir e (b) registro de que
conferiu.

- `config/cref.php` mapeia UF → consulta pública do conselho e UF → qual conselho é, e a tela de
  aprovação mostra **um link direto para o conselho certo**, com a matrícula a procurar. Antes isso
  era uma busca no Google por cadastro — ou, na prática, nenhuma conferência.
- A aprovação passou a **exigir** a afirmação "conferi o CREF no conselho" e a gravar
  `cref_conferido_em`, `cref_conferido_por` e `cref_conferido_resultado`. Os Termos dizem que a
  aprovação é "conferência cadastral": afirmação sem registro é afirmação que não se prova, e num
  litígio sobre profissional sem habilitação é exatamente ela que se discute.

### Por que NÃO há consulta automática

**Não existe API pública nacional.** Verifiquei: o CONFEF tem busca em HTML; cada regional tem o
seu sistema (vários na plataforma Implanta, em ASP.NET, que exige sessão e `__VIEWSTATE` — chamada
direta devolve página de erro). Raspar 20+ sites como etapa **bloqueante** do cadastro seria frágil
(quebra a cada mudança de HTML, e a quebra aparece como "profissional não encontrado", acusando
gente honesta), lento (duas requisições por consulta) e perigoso (conselho fora do ar barraria
cadastro legítimo).

A via automática real é **API paga** de consulta a conselhos — ver **CREF-2** nas pendências.

### O que os documentos passaram a dizer

Nova **cláusula 2.1** dos Termos do Personal, com o alcance exato das duas camadas e três limites
explícitos: retrata o que o conselho informava **na data da aprovação**, **não é monitoramento
contínuo**, e **não garante** a habilitação a terceiros. O dever de manter o registro ativo e de
comunicar suspensão continua inteiro do profissional. A cláusula 3.3 dos Termos gerais e o item de
habilitação da cláusula 13 foram alinhados.

---

## Verificação executada

| Verificação | Resultado |
|---|---|
| `php artisan test` (suíte completa) | **537 passed, 2 skipped, 0 failed** (1868 asserções) — eram 454 no início; +83 testes novos, nenhuma regressão |
| `php artisan test --filter=DocumentosLegaisTest` | **12 passed** (163 asserções) — números, versão, vigência, razão social, sem placeholder, sem modelo antigo |
| `ConsentimentoDadosTest` (novo, 17 testes) | **17 passed** — inclui `Http::assertNothingSent()` provando que nada vai à Meta sem consentimento |
| `PrivacidadeTelasTest` (novo, 7 testes) | **7 passed** — `meus-dados` e o formulário da anamnese renderizam com as variáveis novas e mostram o estado certo de cada consentimento |
| `CrefTest` (novo, 37 testes) | **37 passed** — parser caso por caso, incluindo as armadilhas de sigla que começa com letra de categoria (GO/PA/PE/ES) e as 27 UFs |
| `CrefCadastroTest` (novo, 14 testes) | **14 passed** — formato recusado nas duas portas, gravação canônica, aprovação exigindo e registrando a conferência, e o link apontando para o conselho certo |
| `ExclusaoContaEncerraCobrancaTest` (novo, 8 testes) | **8 passed** — assinatura do aluno cancelada **no Asaas**, assinatura de outro aluno intocada, falha do processador não impedindo a exclusão, saída do profissional avisando sem cancelar, avisos/tokens apagados, bônus cancelado mas **não** o que está em saque |
| `php artisan route:list --json` | ok — todos os controllers resolvem |
| `php -l` nos 17 arquivos PHP tocados | sem erro de sintaxe |
| `node scripts/check-rotas.js` (app ↔ backend) | **95 arquivos · 62 telas · nenhum problema** |
| `node -e require('./app.json')` | parseia; chaves novas confirmadas |
| `php artisan migrate` | `create_consentimentos_table` aplicada |

### Não verificado em execução

- **Telas do app** (`PrivacidadeScreen`, `AssinaturasScreen`, `PrePermissao`, anamnese com checkbox): escritas e
  com rotas conferidas pelo `check-rotas`, mas **não rodei o app no aparelho**. Os prompts de permissão e o
  comportamento de `canAskAgain` só se verificam em dispositivo físico (não funcionam em Expo Go nem simulador).
- **Envio real de e-mail** (confirmação de exclusão, cancelamento, revogação, exportação anexada): depende de
  SMTP configurado.
- **Build iOS**: a remoção das chaves de localização só é observável no `Info.plist` gerado por
  `eas build` / `expo prebuild`. **Confirme no próximo build** que
  `NSLocationAlwaysAndWhenInUseUsageDescription` não está mais presente.
