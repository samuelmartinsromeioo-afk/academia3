# SNR FIT — API REST (v1)

API em JSON para o app mobile, autenticada por token **Laravel Sanctum**. A camada web (Blade + sessão) continua intocada — esta API vive em paralelo, sob o prefixo `/api/v1`.

- **Base URL local (Laragon):** `http://academia3.test/api/v1` (ou `http://localhost/academia3/public/api/v1`, conforme seu virtual host)
- **Formato:** JSON em requisições e respostas. Envie sempre os headers:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `Authorization: Bearer {token}` (nas rotas protegidas)
- **Papéis:** o token pertence a um **personal** ou a um **cliente**. Cada endpoint indica quem pode usá-lo. Todo dado retornado é filtrado pelo dono do token — um usuário nunca vê pagamentos/avaliações de outro.
- **Pagamentos:** a v1 cobre **PIX** (cobrança única e assinatura mensal). Cartão de crédito permanece no fluxo web. O webhook Asaas (`POST /api/asaas-webhook`) é o mesmo do web e não mudou.
- **Segurança:** nenhuma resposta expõe `asaas_api_key`, `asaas_wallet_id`, `chave_pix` ou `senha`.

## Erros

Erros de negócio retornam `{"error": "mensagem"}` com status 401/403/404/422/500. Erros de validação retornam o formato padrão do Laravel:

```json
{ "message": "The email field is required.", "errors": { "email": ["The email field is required."] } }
```

---

## 1. Autenticação

### POST /api/v1/login
Público (rate limit: 10/min). Tenta autenticar como **personal** (precisa estar `aprovado`, mesma regra do site) e depois como **cliente**.

Parâmetros:

| Campo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `login` | string | sim | E-mail |
| `senha` | string | sim | Senha |
| `device_name` | string | não | Nome do aparelho (rótulo do token). Padrão: `mobile` |

Resposta `200`:

```json
{
  "token": "1|Xy9AbC...",
  "token_type": "Bearer",
  "user_type": "cliente",
  "user": { "id": 12, "tipo": "cliente", "nome": "Maria", "email": "maria@email.com", "whatsapp": null, "foto": null, "cidade": null, "estado": null, "idade": null, "sexo": null, "altura": null, "peso": null, "resumo_objetivo": null, "plano_ativo": false, "studio_plano_ativo": false }
}
```

Erros: `401` credenciais inválidas · `403` personal ainda não aprovado.

### POST /api/v1/register
Público (rate limit: 10/min). Cadastro de **cliente** (personal se cadastra pelo site, pois exige certificado/CREF e aprovação do admin).

| Campo | Tipo | Obrigatório |
|---|---|---|
| `nome` | string | sim |
| `email` | string (único) | sim |
| `senha` | string (mín. 6) | sim |
| `whatsapp` | string | não |
| `aceita_termos` | boolean (`true`) | sim |
| `device_name` | string | não |

Resposta `201`: mesmo formato do login (já retorna o token).

### POST /api/v1/logout
Autenticado. Revoga o token usado na requisição.

```json
{ "success": true, "message": "Sessão encerrada." }
```

### GET /api/v1/me
Autenticado. Retorna o usuário dono do token.

```json
{ "user_type": "personal", "user": { "id": 3, "tipo": "personal", "nome": "João Personal", "email": "joao@email.com", "whatsapp": "44999998888", "foto": "fotos/joao.jpg", "cref": "012345-G/PR", "cidade": "Maringá", "estado": "PR", "status": "aprovado", "valor_secao": 80.0, "valor_ficha": 50.0, "valor_avaliacao": 120.0, "precos_avaliacao": { "oximetro": 40.0 }, "media_avaliacao": "4.8", "pioneiro": true } }
```

> Os atalhos `POST /api/login`, `POST /api/register`, `POST /api/logout` e `GET /api/me` (sem `/v1`) também existem e apontam para os mesmos controllers.

---

## 2. Dashboard do Personal

### GET /api/v1/personal/profile
Somente **personal** (`403` para cliente). Perfil + financeiro do mês (mesmo cálculo `calcularFinanceiroMes()` do site).

```json
{
  "personal": { "id": 3, "nome": "João Personal", "...": "..." },
  "financeiro_mes": { "pacotes": 1200.0, "avulsas": 320.0, "total": 1520.0, "detalhes": { "quantidade_aulas_pacote": 24, "quantidade_aulas_avulsa": 4, "valor_secao": 80.0 } }
}
```

### GET /api/v1/personal/clientes
Somente **personal**. Clientes com aulas ativas (não canceladas) na agenda do personal. Paginado (20 por página, `?page=N`).

```json
{
  "data": [ { "id": 12, "tipo": "cliente", "nome": "Maria", "email": "maria@email.com", "...": "..." } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 20, "total": 8, "...": "..." }
}
```

---

## 3. Avaliação Física

Tipos aceitos em `tipo`: `antes_depois`, `antropometrica`, `dobras`, `bioimpedancia`, `dinamometro`, `forca`, `flexibilidade`, `neuromotora`, `funcional`, `cardio`, `oximetro`, `pressao_arterial`, `postural`, `dor`, `anamnese`.

### GET /api/v1/avaliacoes
Autenticado. **Personal:** lista as avaliações que ele criou (filtros opcionais `?cliente_id=` e `?tipo=`). **Cliente:** lista somente as próprias avaliações (filtro `?tipo=`). Paginado (20 por página).

```json
{
  "data": [
    {
      "id": 45, "tipo": "pressao_arterial", "tipo_label": "Pressão Arterial", "data_avaliacao": "2026-07-01",
      "personal_id": 3, "cliente": { "id": 12, "nome": "Maria" },
      "peso": null, "altura": null, "imc": null, "medidas": null,
      "forca": null, "flexao_braco_reps": null, "prancha_tempo": null,
      "spo2": null, "bpm": 72, "pressao_sistolica": 120, "pressao_diastolica": 80,
      "percentual_gordura": null, "massa_gorda": null, "massa_magra": null,
      "vo2max_estimado": null, "observacoes": "Aferição em repouso.", "created_at": "2026-07-01T14:30:00-03:00"
    }
  ],
  "links": { "...": "..." }, "meta": { "...": "..." }
}
```

### POST /api/v1/avaliacoes
Somente **personal**. O `cliente_id` precisa estar vinculado ao personal (pacote ativo na agenda ou avaliação avulsa paga — mesma regra do site); caso contrário `403`.

| Campo | Tipo | Obrigatório |
|---|---|---|
| `cliente_id` | integer | sim |
| `tipo` | string (lista acima) | sim |
| `data_avaliacao` | date (`YYYY-MM-DD`) | sim |
| `peso`, `altura`, `imc` | numeric | não |
| `medidas` | string | não |
| `forca` (dinamômetro) | string | não |
| `flexao_braco_reps` | integer | não |
| `prancha_tempo` | string | não |
| `spo2` (oxímetro, 0–100) | integer | não |
| `bpm` | integer | não |
| `pressao_sistolica`, `pressao_diastolica` | integer | não |
| `percentual_gordura`, `massa_gorda`, `massa_magra` | numeric | não |
| `vo2max_estimado` | numeric | não |
| `observacoes` | string | não |

Resposta `201`: a avaliação criada (mesmo formato do GET).

### GET /api/v1/avaliacoes/{id}
Autenticado. Retorna a avaliação se pertencer ao usuário do token (personal criador ou cliente avaliado); senão `404`.

---

## 4. Pagamentos (Asaas)

Regras de repasse — **idênticas ao site**, via `App\Services\AsaasService` (compartilhado entre web e API):

| Recebedor | Modelo |
|---|---|
| Personal | Split **90/10** — personal recebe 90% do valor bruto; a plataforma absorve a taxa Asaas |
| Academia | Assinatura mensal com **repasse de 100%** (sem comissão da plataforma; só a taxa PIX) |
| Studio (plano) | Assinatura mensal com split **90/10** |

Pacote do personal, mensalidade/plano de academia e plano de studio = **assinatura mensal recorrente** (o Asaas gera uma nova cobrança PIX por mês; renovações são processadas pelo webhook). Aula avulsa, ficha e avaliação = **cobrança única**. Valor mínimo por cobrança: **R$ 10,00** (`422` abaixo disso).

### GET /api/v1/payments
Autenticado. **Cliente:** cobranças que ele pagou/deve pagar. **Personal:** cobranças recebidas (onde ele é o `trainer`). Paginado (15 por página).

```json
{
  "data": [
    {
      "id": 101, "tipo": "pacote", "amount_total": 400.0, "company_fee": 40.0, "trainer_amount": 360.0,
      "status": "pending", "payment_method": "pix", "recorrente": true,
      "asaas_payment_id": "pay_abc123", "asaas_subscription_id": "sub_xyz789",
      "paid_at": null, "next_billing_date": null, "created_at": "2026-07-08T10:00:00-03:00",
      "personal": { "id": 3, "nome": "João Personal" },
      "pacote": { "id": 7, "frequencia": 3, "valor_mensal": 400.0 }
    }
  ],
  "links": { "...": "..." }, "meta": { "...": "..." }
}
```

`status` local: `pending` | `succeeded` | `failed`.

### POST /api/v1/payments
Somente **cliente**. Cria uma cobrança PIX. O corpo muda conforme o `contexto`:

**a) Personal** (`contexto: "personal"`) — split 90/10:

| Campo | Tipo | Obrigatório |
|---|---|---|
| `contexto` | `"personal"` | sim |
| `personal_id` | integer | sim |
| `tipo` | `aula_avulsa` \| `pacote` \| `ficha` \| `avaliacao` | sim |
| `pacote_id` | integer | se `tipo=pacote` |
| `frequencia`, `valor_pacote`, `dias_selecionados` (JSON string), `hora_inicio`, `hora_fim`, `academia_nome`, `data` | — | para agendamento do pacote/aula |
| `pacote_avaliacao_id` **ou** `avaliacao_tipo` | — | para `tipo=avaliacao` (sem ambos usa o valor padrão do personal) |
| `objetivos`, `condicoes_clinicas`, `nivel_experiencia`, `observacoes` | string | para `tipo=ficha` |

**b) Academia** (`contexto: "academia"`) — assinatura mensal, 100% para a academia:

| Campo | Tipo | Obrigatório |
|---|---|---|
| `contexto` | `"academia"` | sim |
| `academia_id` | integer | sim |
| `plano_id` | integer | não (sem ele, cobra a mensalidade base) |

**c) Studio** (`contexto: "studio_plano"`) — assinatura mensal, split 90/10:

| Campo | Tipo | Obrigatório |
|---|---|---|
| `contexto` | `"studio_plano"` | sim |
| `studio_id` | integer | sim |
| `plano_id` | integer | sim |

Resposta `201` (cobrança única):

```json
{ "paymentId": 101, "asaasPaymentId": "pay_abc123", "pixPayload": "00020126...", "pixQrCode": "iVBORw0KGgo... (base64)", "amount": 80.0 }
```

Resposta `201` (assinatura — pacote/academia/studio):

```json
{ "paymentId": 102, "asaasPaymentId": "pay_def456", "subscriptionId": "sub_xyz789", "pixPayload": "00020126...", "pixQrCode": "iVBORw0KGgo...", "amount": 400.0, "recorrente": true }
```

Erros: `422` valor abaixo do mínimo / plano inválido / personal não trabalha com a avaliação · `500` falha no Asaas.

### GET /api/v1/payments/{id}
Autenticado. Detalhes de uma cobrança do próprio usuário (`404` se não for dele). Mesmo formato do item da listagem, com relações carregadas.

### GET /api/v1/payments/{id}/pix
Autenticado (dono da cobrança). Retorna o QR code PIX gerado pelo Asaas — útil para reexibir uma cobrança pendente.

```json
{ "paymentId": 101, "asaasPaymentId": "pay_abc123", "pixPayload": "00020126...", "pixQrCode": "iVBORw0KGgo...", "expirationDate": "2026-07-09 23:59:59", "amount": 80.0 }
```

`422` se a cobrança não tiver QR disponível.

### GET /api/v1/payments/{id}/status
Somente **cliente** (dono da cobrança). Polling de confirmação — consulta o status atual no Asaas e, quando confirmado, dispara o mesmo processamento do site (agendamento de aulas, ficha, avaliação, ativação de plano):

```json
{ "status": "RECEIVED", "confirmed": true }
```

`status` (Asaas): `PENDING`, `CONFIRMED`, `RECEIVED`, `RECEIVED_IN_CASH`, `OVERDUE`, ...

---

## Observações de implementação

- **Sanctum:** tabela `personal_access_tokens` (polimórfica — o token aponta para `Personal` ou `Cliente`). Migration: `2026_07_08_000001_create_personal_access_tokens_table.php`.
- **Service compartilhado:** `app/Services/AsaasService.php` concentra split 90/10 (`montarSplit`), precificação (`resolverItemPersonal`/`resolverItemAcademia`/`validarStudioPlano`), assinaturas PIX e QR code. O `PaymentController` web delega para ele — comportamento inalterado.
- **Webhook:** `POST /api/asaas-webhook` não foi alterado; os prefixos de `externalReference`/`idempotency_key` da API são os mesmos do web, então renovações e confirmações continuam fluindo pelo webhook normalmente.
- **Rotas AJAX legadas** (`/api/criar-pagamento`, `/api/payments/history`, etc.) continuam funcionando por sessão web, sem mudanças.


---

# NOVOS ENDPOINTS (expansão completa do app — julho/2026)

A partir desta versão a API cobre **todos os papéis** (cliente, personal, academia, studio e loja). O login (`POST /api/v1/login`) agora tenta, na ordem: personal → cliente → academia (e-mail ou CNPJ; conta principal ou subconta de filial) → studio → loja, com as mesmas regras de aprovação do site. Tokens de subconta de filial carregam a ability `filial:{id}` e enxergam só os alunos da filial.

## Cadastros (públicos, rate limit 10/min)
- `POST /register/personal` — multipart (campo `foto` é arquivo). Entra como pendente.
- `POST /register/academia` · `POST /register/studio` · `POST /register/loja` — entram como pendentes.

## Cliente (aluno)
- Fichas: `GET /fichas`, `GET /fichas/{id}`, `POST /fichas/{id}/concluir` (com `registros` de carga por exercício), `POST /fichas/{id}/desmarcar`
- Treino do dia: `GET /treino-do-dia`, `POST /treino-do-dia/{mesocicloId}/concluir`
- Desempenho: `GET /desempenho`, `GET /evolucao-carga?exercicio=&dias=30|90|180`
- Progresso: `GET /progresso`, `POST /progresso/medidas`, `DELETE /progresso/medidas/{id}`, `POST /progresso/fotos` (multipart), `DELETE /progresso/fotos/{id}`
- Metas: `GET|POST /metas`, `POST /metas/{id}/alternar`, `DELETE /metas/{id}`
- Anamnese: `GET|POST /anamnese`
- Explorar: `GET /explorar/{personais|academias|studios|lojas}` (+ `/{id}` para detalhes)
- Contratar: `GET /personais/{id}/pacotes`, `GET /personais/{id}/horarios/{dia}`, `GET /studios/{id}/horarios/{dia}`, `POST /agendar`, `POST /pacotes/contratar`, `POST /academias/contratar`

## Personal
- Agenda: `GET /personal/agenda/{data}`, `POST /personal/agenda` (bloqueio), `POST /personal/agenda/{id}/cancelar` (24h + justificativa), `POST /personal/agenda/cancelar-dia`, `POST /personal/agenda/bloquear-fixo`, `POST /personal/aulas/{id}/finalizar`
- Frequência: `GET /personal/frequencia`, `GET /personal/frequencia/{clienteId}`, `POST /personal/frequencia`, `DELETE /personal/frequencia/{id}`
- Alunos: `GET /personal/alunos/{id}` (+ `/anamnese`, `/progresso`, `/evolucao-carga`, `POST .../metas`)
- Aderência: `GET /personal/aderencia`, `POST /personal/aderencia/cutucar/{clienteId}`
- Preços: `GET|POST /personal/precos`, `POST /personal/valor-ficha`
- Templates: `GET|POST /personal/templates`, `POST /personal/templates/{id}/exercicios`, `DELETE /personal/templates/{id}/exercicios/{index}`, `POST /personal/templates/{id}/aplicar`, `POST /personal/templates/de-ficha/{fichaId}`, `DELETE /personal/templates/{id}`
- Painéis: `GET /personal/feed`, `GET /personal/relatorio/{clienteId}`, `POST /personal/relatorio/{clienteId}/enviar`
- Vínculo com academias: `GET /personal/academias/buscar?q=`, `GET /personal/academias/minhas-solicitacoes`, `POST /personal/academias/{id}/solicitar`, `DELETE /personal/academias/{id}/cancelar`
- Solicitações de ficha: `GET /personal/solicitacoes-ficha`, `POST /personal/solicitacoes-ficha/{id}/concluir`

## Gestão de treinos (personal E academia — o dono é resolvido pelo token)
- Fichas: `GET /gestao/alunos/{clienteId}/fichas`, `POST /gestao/fichas`, `PUT|DELETE /gestao/fichas/{id}`, `POST /gestao/fichas/{id}/exercicios`, `PUT|DELETE /gestao/exercicios/{id}`
- Periodização: `GET|POST /gestao/alunos/{clienteId}/mesociclos`, `PUT /gestao/mesociclo-treinos/{id}`, `POST /gestao/mesociclo-treinos/{id}/exercicios`, `DELETE /gestao/mesociclo-exercicios/{id}`, `POST /gestao/mesociclos/{id}/{ativar|renovar}`, `DELETE /gestao/mesociclos/{id}`

## Academia
- `GET /academia/dashboard`, `PUT /academia/perfil`, `POST /academia/infraestrutura`
- Alunos: `GET|POST /academia/alunos`, `GET|POST /academia/alunos/{id}/anamnese`
- Planos: `GET|POST /academia/planos`, `PUT|DELETE /academia/planos/{id}`
- Filiais (só conta principal): `GET|POST /academia/filiais`, `DELETE /academia/filiais/{id}`
- Gestão: `GET /academia/gestao`, `POST /academia/professores`, `DELETE /academia/professores/{id}`, `POST /academia/aulas`, `DELETE /academia/aulas/{id}`
- Personais: `GET /academia/solicitacoes`, `POST /academia/solicitacoes/{personalId}/{aprovar|rejeitar}`

## Studio
- `GET /studio/dashboard`, `PUT /studio/perfil`
- Alunos: `GET /studio/alunos`, `DELETE /studio/alunos/{id}` (desvincular)
- Planos: `GET|POST /studio/planos`, `DELETE /studio/planos/{id}`
- Horários: `GET /studio/horarios?data=`, `POST /studio/horarios`, `DELETE /studio/horarios/{id}`, `POST /studio/professores`, `DELETE /studio/professores/{id}`, `POST /studio/aulas`, `DELETE /studio/aulas/{id}`, `POST /studio/bloqueios`, `DELETE /studio/bloqueios/{id}`
- Agenda: `GET /studio/agenda/{data}`

## Loja
- `GET /loja/dashboard`
- Produtos: `GET|POST /loja/produtos` (POST aceita multipart `imagem`), `POST /loja/produtos/{id}` (atualizar), `PUT /loja/produtos/{id}/estoque`, `DELETE /loja/produtos/{id}`
- Pedidos: `GET /loja/pedidos`, `PUT /loja/pedidos/{id}/concluir`

## Notificações e chat (personal ↔ aluno)
- `GET /notificacoes`, `GET /notificacoes/nao-lidas`, `POST /notificacoes/{id}/lida`, `POST /notificacoes/marcar-todas`
- `GET /chat` (conversas), `GET /chat/{outroId}` (mensagens; marca como lidas), `POST /chat/{outroId}` (enviar)
