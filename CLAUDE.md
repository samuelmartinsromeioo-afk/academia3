# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Academia3 / FitSys** — a multi-role fitness platform built with Laravel 10 (PHP 8.1) that connects personal trainers (Personais), clients (Clientes), and gyms/academies (Academias), with an Admin oversight layer. Runs via Laragon on localhost with MySQL.

## Common Commands

```bash
# Start dev server (Laragon serves automatically; for Artisan commands)
php artisan serve

# Run migrations
php artisan migrate

# Run a specific migration
php artisan migrate --path=database/migrations/2026_XX_XX_XXXXXX_file.php

# Rollback last batch
php artisan migrate:rollback

# Clear and rebuild caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear

# Code style linting
./vendor/bin/pint

# Run tests
php artisan test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Tinker (REPL)
php artisan tinker

# Generate storage symlink (required for uploaded files)
php artisan storage:link
```

## Architecture & Key Design Decisions

### Custom Multi-Role Session Auth

**There is no standard Laravel Auth.** Authentication is fully custom and session-based, using four distinct session keys:

| Role | Session Key | Model |
|---|---|---|
| Admin | `admin_id` | `App\Models\Admin` |
| Personal | `personal_id` | `App\Models\cadastro\Personal` |
| Cliente | `cliente_id` | `App\Models\cadastro\cliente` |
| Academia | `academia_id` | `App\Models\cadastro\Academia` (inferred) |

Login logic in `loginController` tries each role in order (Admin → Personal → Cliente → Academia). Personais must have `status = 'aprovado'` to log in; a pending or rejected trainer gets a login error.

Two middleware aliases are registered in `Kernel.php`:
- `check.login` — blocks unauthenticated requests across all roles
- `check.admin` — blocks non-admin requests (checks `admin_id` in session)

**Session hardening**: `loginController::abrirSessao()` calls `session()->regenerate()` before writing the role id (anti session-fixation, CWE-384), and `logout()` calls `invalidate()` + `regenerateToken()`. Never write a role id into the session without going through `abrirSessao()`. Failed logins are recorded on the `security` log channel (`storage/logs/security-*.log`); `LogSecurityEvents` is registered on both the `web` and `api` middleware groups and logs 401/403/419/429 + 5xx.

**Accounts are always self-created.** Only two code paths may create a `Cliente`: `Cadastro\ClienteController@store` (web) and `Api\AuthController@register` (app) — both with the user choosing their own password (`min:8`). An academia/studio/loja **cannot** create student accounts; the student signs up on SnrFit and contracts the gym through the app, which sets `academia_id`. The old "academia cadastra aluno" flow was removed because it assigned the fixed password `123456` to every account created at the front desk with no forced change. Don't reintroduce it.

### Approval Workflow for Personal Trainers

New Personais register with `status = 'pendente'`. The Admin must approve them before they can log in. The approval flow lives in `AdminController` and updates `status`, `data_aprovacao`, or `motivo_rejeicao`.

### Model Namespacing

Domain models live under `App\Models\cadastro\` (note lowercase):
- `Personal`, `cliente`, `FichaTreino`, `Pacote`, `ExercicioFicha`, `TreinoConcluido`

Non-domain models live directly in `App\Models\`:
- `User`, `Admin`, `Agenda`, `Aula`

### Core Domain Relationships

```
Personal --< Agenda >-- Cliente
Personal --< Pacote
Personal --< FichaTreino >-- ExercicioFicha
FichaTreino >-- TreinoConcluido  (tracks completion per date)
Personal/Academia --< Fotos (polymorphic: fotavel_type / fotavel_id)
Personal/Academia --< Avaliacoes
```

`Agenda` is the central booking record. It holds `tipo_aula` (pacote vs. avulsa), `frequencia_pacote`, `valor_aula`, cancellation fields, and foreign keys to Personal, Cliente, and optionally Academia.

### Revenue Model — the commission is a MARKUP, not a discount

**There is no SaaS subscription.** No role ever pays a monthly fee. The single revenue model is a **commission added on top of the professional's price and charged to the client**:

```
personal asks R$ 100  →  client pays R$ 110  →  personal receives R$ 100 (in full)
```

This holds for *all* receivers — personal trainer (package + single session), academia, studio, loja. It replaced the old 90/10 split (charge R$ 100, pay out R$ 90) on 2026-10-13.

**`AsaasService::SPLIT_RATE` was removed, not repurposed.** "Split rate" and "markup" produce different numbers from the same `0.10`, and a surviving `SPLIT_RATE` would be read as the old meaning by anyone who missed the change. The one source of the number is now `config('taxas.comissao')`, read through `AsaasService::feeRate()`. **Never write `0.10` anywhere else** — `company_fee` is the base of the referral bonus, so a stale copy pays someone on a commission that was never charged.

Four methods on `AsaasService` hold the whole arithmetic, and they are `static` so Blade views and API resources can price without instantiating a service that talks HTTP:

| | |
|---|---|
| `feeRate()` | the commission rate (`config('taxas.comissao')`) |
| `comissao($base)` | what the platform earns on a service priced `$base` |
| `detalhar($base, $billingType)` | the whole bill: `['base', 'comissao', 'gateway', 'total']` |
| `valorCobrado($base, $billingType)` | shorthand for `detalhar(...)['total']` |
| `comissaoEmbutida($bruto)` | the inverse — the commission *inside* an amount already charged |

Three consequences that must move together, and all of them already do:

1. **`montarSplit()` sends `fixedValue` = the professional's full price.** It takes the *price*, never the charged amount, and the `percentualValue: 100` fallback is defensive only — by construction the net is `price + commission − fee`, always above the price. If it ever triggers it errs in favour of the **professional** (he takes the whole net, the platform nothing), because the opposite would pay him less than agreed.
2. **On CARD the Asaas fee is passed to the client, as a gross-up.** `total = (base + comissao + fixa) / (1 − percentual)` — a *division*. Adding the fee "on the outside" (`base + comissao + percentual×base`) is wrong, because the fee applies to the already-increased total, and the platform would silently eat the difference. `ComissaoSplitTest` asserts the property, not the number: after Asaas takes its cut, the net is **exactly** `price + commission`. On PIX the fee comes out of our commission.
3. **`UNDEFINED` (the Asaas hosted checkout) is priced as CARD.** In that mode the client picks the method *after* the charge exists; pricing it as PIX would leave the split's `fixedValue` above the net and Asaas would reject it at the end of the checkout, in the client's face. So we charge the ceiling. Whoever wants the PIX price picks PIX, where `billingType` really is `PIX`.

**`payments.gateway_fee`** (migration `2026_10_13_000001`) exists so that difference has a name. The four columns answer four different questions and close the bill: `amount_total` (what the client paid) = `trainer_amount` (the professional's price, in full) + `company_fee` (**what we earned** — the referral base) + `gateway_fee` (what Asaas took on card; it passed through us and is not revenue). Folding the card fee into `company_fee` would inflate the referral bonus over money that was never ours; taking it out of `trainer_amount` would contradict the full payout the split actually sends.

**The minimum charge is derived, not chosen.** `AsaasService::valorMinimo()` = `pix_fixa / feeRate` (R$ 19,90 with today's numbers). Below it the commission doesn't cover the PIX fee, the full payout doesn't fit in the net and Asaas rejects the split. The old `MIN_SPLIT_VALUE = 10.0` became unsound on the inversion (R$ 1 of commission against a R$ 1,99 fee) and is gone.

**The gateway fees live in `config/taxas.php`** (`.env`: `ASAAS_TAXA_PIX`, `ASAAS_TAXA_CARTAO_*`), not in constants, because they are contract terms that vary per account. The defaults are Asaas's public table and are **deliberately conservative**: overestimating costs the platform a few cents, underestimating makes Asaas reject the split and the charge fails in front of the client.

**No front recalculates the price.** `GET /api/v1/precos/cotar[?valor=]` (public, throttled) is the single source for both: with `valor` it returns the exact breakdown for both methods, without it just the rule (rate + minimum) — which is what the app loads once at boot to render lists without one request per item. `App\Support\Preco` is the Blade-side entry point (`fmt`, `taxa`, `dados`, `configJs`); the app's is `src/api/precos.js`. The PIX side *is* recomputed in JS (a plain sum), but the **card gross-up is never** — a second implementation of a division diverges by cents and the screen would promise a total the charge doesn't have.

**What the client sees is always the final price** (vitrines, detail pages, app lists), with the composition spelled out at checkout. The store cart is the one exception and for an arithmetic reason: the fee is a *line* there, not baked into each product, because `sum(round(p·1.1))` ≠ `round(sum(p)·1.1)` and the lines would disagree with the total.

**Watch out for `amount_total` read as "what the professional earned".** It stopped meaning that. The inversion needed fixes in five places that had conflated them, and the loja one was caught by a test: `pedidos.valor_total` (the order the store reads and gets on WhatsApp, which must match `pedido_itens.subtotal`), `SolicitacaoFicha.valor`, `SolicitacaoAvaliacao.valor`, the value in `AvisoService::academiaContratada`/`studioPlanoContratado`, and the admin's "faturamento do studio". All now read `trainer_amount`. `amount_total` is correct for: refunds, Meta `Purchase`, the client's own statements and the payment e-mails.

The history worth keeping: the rate once existed in **nine** copies of `0.10` across both PaymentControllers and `AsaasService` — the worst being `calculateSplit(float $amountTotal, float $feeRate = 0.10)`, a wrapper that always forwarded an explicit value, so the `$feeRate ??= feeRate()` inside the service never ran for any web flow. They were reduced to `?float … = null`, and the inversion removed the parameters entirely: **a per-charge fee parameter is itself a second copy of the rule.** `ComissaoSplitTest::test_nenhum_metodo_de_cobranca_aceita_taxa_por_parametro` now asserts by reflection that no cobrança method accepts one at all.

`ComissaoSplitTest` pins **three** ends together, which is the point: the `value` sent to Asaas, the `fixedValue` of the split, and the row recorded in `payments`. It reads the faked HTTP payload, not just the DB row — a test that only checked the row would stay green with the record right and the charge wrong.

Note `academias.valor_mensalidade` is the price the **gym charges its students**, not something the gym pays us — the student pays it plus the commission, and the gym receives it whole.

Known gap, deliberate: when a receiver has no `asaas_wallet_id`, `montarSplit()` returns `null` and the charge still goes through **without a split**, so everything lands in the platform account and the payout is manual (`personal_saques`) — for the full `trainer_amount`, which is now the whole price. If you change this, change it everywhere.

**Active subscriptions created before the inversion were deliberately left alone.** They live at Asaas with the old value and a 90% split, and they keep running that way until the student cancels or re-contracts. Rewriting the value of a subscription in flight would raise a student's monthly bill by 10% without them asking.

#### Subconta do recebedor — "pode receber agora?" são DUAS perguntas, não uma

`App\Models\Concerns\TemSubcontaAsaas` (personal, academia, studio, loja) concentra a pergunta que o sistema não sabia responder — **este profissional pode receber pagamento agora?** — e que por isso só era descoberta no erro, quando o Asaas recusava o split na cara do aluno. Tudo lê de colunas locais, **sem rede**: estes métodos rodam na vitrine, em listagem paginada e no checkout, e um HTTP em qualquer um desses lugares transformaria instabilidade do Asaas em página quebrada.

A trava fica em `montarSplit()` e **não** nos dez pontos de entrada de cobrança: todos terminam lá, e uma guarda espalhada em dez lugares é uma guarda que o décimo primeiro ponto vai esquecer. Ela **aborta 422** em vez de devolver `null` — `null` significa "siga sem split" e o dinheiro cairia inteiro na plataforma, criando dívida manual com alguém cuja conta está bloqueada: pior que falhar, porque o aluno paga e ninguém percebe que o repasse não tem para onde ir.

**Duas colunas, duas fontes, e misturá-las foi o bug.** `asaas_account_status` é a situação **cadastral** (de `/myAccount/status`, dona: `asaas:sincronizar-subcontas`); `asaas_split_bloqueado_em` + `asaas_split_bloqueio_motivo` (migration `2026_10_16_000002`) são o **bloqueio de wallet por compliance**, e a cobrança é a única que o vê. O caso que originou isto: a subconta do personal #7 respondia APPROVED nos **quatro** campos (`general`, `commercialInfo`, `bankAccountInfo`, `documentation`) e o Asaas recusava o split com "pois a mesma está bloqueada". `/myAccount/status` não enxerga a segunda pergunta.

`reportarBloqueioDeRecebedor()` escrevia `asaas_account_status = 'BLOCKED'`, e os dois ficavam **em guerra pelo mesmo campo**: o sync grava APPROVED duas vezes ao dia → a cobrança apanha e grava BLOCKED → o sync grava APPROVED → em círculo. Pior que a oscilação: no estado BLOCKED o aluno lia *"este profissional ainda está finalizando o cadastro de recebimentos"* — mentira, o cadastro dele está aprovado — e o profissional receberia o ticket errado, mexendo em documentação que já passou. Hoje é `registrarBloqueioDeSplit()`, que **não toca** o status cadastral.

**A trava é um TIMESTAMP, não um booleano, e isso é obrigatório.** O Asaas **não avisa quando desbloqueia** e `/myAccount/status` responde APPROVED antes e depois: não existe endpoint que diga "a wallet voltou". Um booleano precisaria de alguém para desligá-lo e não há quem — o profissional ficaria barrado para sempre por um bloqueio já resolvido. Com a data a trava **expira** (`AsaasService::HORAS_RETENTAR_SPLIT`, 6h) e a próxima cobrança testa: liberou, volta a vender sozinho; não liberou, o carimbo se renova. O preço é **uma cobrança falha por janela**, e é o único jeito de detectar o desbloqueio sem um endpoint que não existe.

Três liberações contraintuitivas de `podeReceberPagamento()`, todas deliberadas: **sem `asaas_wallet_id` → pode** (não há split, cai na plataforma e o repasse é manual — fluxo que existe e funciona); **status `null` → pode** (null é "ainda não sincronizamos", não "reprovada"; tratá-lo como impedimento pararia a plataforma inteira no deploy em que a coluna nasceu vazia); **`INVALID_KEY` → pode** (a cobrança usa a chave da **plataforma** e o split só referencia o `walletId` — a chave do profissional serve a saldo e saque, então quebrá-la o deixa sem ver o dinheiro e sem sacar, mas vendendo; barrar a venda puniria duas vezes quem já está prejudicado).

**`scopePodeReceber()` espelha o método em SQL e os dois PRECISAM concordar** — se o filtro divergir, o profissional aparece na vitrine e toma erro no checkout, que é exatamente o defeito que a trava veio corrigir. `BloqueioSplitAsaasTest` compara as duas respostas **na mesma linha**, em vez de afirmar cada uma por fora. *Lacuna conhecida:* nenhuma query de vitrine chama o scope hoje — quem está ligado é `podeReceberPagamento()` (`pode_receber` no payload de `Api\ExplorarController`), ou seja o profissional bloqueado **aparece** na lista com o aviso. Ligar o scope é mudança de produto (esconder vs. mostrar com aviso), não limpeza.

Os textos são **dois públicos diferentes** e não se podem fundir: `motivoNaoRecebe()` é uma frase para o **aluno** (sem `walletId`, sem status cru, sem a palavra "Asaas" — ele não tem o que fazer com "general = PENDING"), e `detalheSubconta()` + `partials/aviso-subconta.blade.php` são para o **profissional**, com a pendência exata e para onde ir. No bloqueio de wallet o aviso dele diz explicitamente **o que NÃO é** ("não é documentação faltando") e aponta o único lugar onde se resolve, o suporte do Asaas. `asaas:diagnosticar-subconta` faz o mesmo no terminal: APPROVED com a coluna carimbada não imprime "aprovada", imprime que a wallet foi recusada.

**`mensagemDeErro()` ainda devolve o texto de "cadastro de recebimentos" para esse mesmo erro** (pinado por `ComissaoSplitTest`), então a *primeira* cobrança — a que dispara o carimbo — mostra o texto genérico e só as seguintes mostram o certo. É inconsistência conhecida, não esquecimento: ali só se tem o corpo do erro do Asaas, sem o recebedor, e o texto do gateway é idêntico nos dois casos.

### Financial Calculation

`Personal::calcularFinanceiroMes()` computes monthly revenue by splitting package sessions (valor_mensal / frequencia) from individual sessions (valor_secao). This logic lives on the model, not in the controller.

### WhatsApp Integration (Twilio)

When a session ends (`POST /personal/aulas/{id}/finalizar`), the app sends a WhatsApp message to the client via Twilio. Credentials are in `.env` as `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_WHATSAPP_FROM`.

### Meta Pixel & Conversions API (CAPI)

Marketing tracking runs on **two parallel channels that deduplicate**:

- **Browser (Pixel)** — `resources/views/partials/meta-pixel.blade.php` fires `PageView` on every page plus any conversion events. It is included in the `<head>` of every layout **and** in each standalone full-page view (the login/hero at `login/index`, `cadastro/sucesso`, `cliente/index`, and the `cliente/{academia,loja,studio}-detalhes` pages don't extend a layout, so they include the partial directly).
- **Server (CAPI)** — `App\Services\MetaConversionsService` POSTs the same events to the Graph API, hashing user data (email, phone, name, city, state, id) with SHA-256 and forwarding IP/user-agent/`_fbp`/`_fbc`. Without `META_CAPI_TOKEN` set it is a **no-op** (browser Pixel still works).

**Deduplication**: the service generates one `event_id` per event; the *same* id is sent server-side and echoed to the browser via `fbq('track', name, params, {eventID})`. Meta collapses the pair.

**How events fire**: at each conversion point the controller calls `$fb->track($event, $customData, $fb->userDataFromModel($model))`, which sends the CAPI event and returns a `['event','params','event_id']` array. That array is handed to the browser partial either via `->with('fb_event', $arr)` (redirect flows) or `@include('partials.meta-pixel', ['fbEvents' => [$fbEvent]])` (view-rendered flows like `ViewContent`). The partial accepts a single event or a list.

Events wired: **CompleteRegistration** (all five cadastros — Personal, Cliente, Academia, Loja, Studio), **Purchase** (`PaymentController@pagarSucesso` after Asaas confirmation, and `ClienteController::contratarPacote`, both with `value`+`BRL`), **Lead** (contratar academia, agendar horário avulso), **ViewContent** (academia/loja/studio detail pages).

`_fbp`/`_fbc` (and the consent cookie `snrfit_consent`) are listed in `app/Http/Middleware/EncryptCookies.php`'s `$except` — otherwise Laravel's cookie encryption returns `null` and they can't be read server-side.

**LGPD consent gate**: with `META_REQUIRE_CONSENT=true` (default) neither channel fires until the visitor accepts the cookie banner. The banner is injected via JS from the `meta-pixel` partial (so it works on every layout without extra includes) and sets the `snrfit_consent` cookie. The partial only renders the Pixel when consent is `granted`; `MetaConversionsService::send()` checks `hasConsent()` before any CAPI POST. Set `META_REQUIRE_CONSENT=false` to bypass (only if consent is handled elsewhere).

**Server-only events**: `MetaConversionsService::trackServer()` sends without a browser request (no consent cookie, no IP/UA) — used for subscription renewals in `PaymentController::processarRenovacaoAssinatura`, which fire `Purchase` from the Asaas webhook with a deterministic `event_id` (`purchase_{payment_id}`) so webhook retries dedup.

Config lives in `config/services.php` under `meta`. To debug, set `META_CAPI_TEST_CODE` to the code from Events Manager → *Test Events* (remove in production). Adding/changing these keys requires `php artisan config:clear`.

### Nutrition Module — NOT in main

The nutritionist module was **removed from main** in commit `683e4647` and lives in full on the branch **`feat/nutricionista`**. Main's focus is the personal trainer. Don't reintroduce nutri code here on your own initiative — if a task needs it, say it needs the branch.

**To bring it back:** `git revert 683e4647`. The removal commit is deliberately **purely subtractive** (it only deletes blocks, it never rewrote the surrounding logic), so the revert reinstates the module with conflicts only on lines the personal work has touched since. To read or work on the module, check out the branch — its `CLAUDE.md` still documents all of it (patient portal, meal-plan editor, anthropometry, billing; ~7.000 lines across 63 files).

**What deliberately STAYED in main, dormant and invisible:** the 12 `nutri_*` migrations plus `2026_09_11_000005_add_uso_portal_to_anamnese_modelos` and `2026_09_04_000001_add_professional_type_to_personals_table`; the `personals.professional_type` and `crn` columns (`cref` is nullable in the schema); `App\Enums\ProfessionalType` including the `NUTRITIONIST` case; `Personal::isNutricionista()` / `isPersonalTrainer()` / `registroConselho()` / `scopePersonalTrainers()` / `scopeNutricionistas()`; `CadastroHelper::validarCRN()`; the nutri strings in `config/textos.php`; and the `nutri_cobranca:` label in `IndicacaoCredito`.

**Why it stayed — don't "finish the cleanup" by deleting it.** Production **already ran** those migrations. Deleting the files would leave the server's `migrations` table with orphan rows and break `migrate:rollback`. The `nutri_cobranca:` label stays because real credits with that origin exist in the production ledger. An empty table is invisible to users, so keeping this costs nothing and removing it breaks something real.

**Practical consequences in main:** `professional_type` no longer comes from the form — `Cadastro\PersonalController@store` pins it to `PERSONAL_TRAINER` and CREF is unconditionally required again. `isNutricionista()` still exists but returns `false` for everyone. `scopePersonalTrainers()` (a null tipo counts as PT) is still the filter used by `ClienteController@index` and `@listarPersonais`, so nutritionists could never show up as bookable trainers. No `nutri.*` route and no `p/{token}` patient portal exist here.

Editable UI/marketing strings (labels, especialidades) live in `config/textos.php` — run `php artisan config:clear` after edits.

### Tela de "cadastro recebido"

Every signup that waits for admin approval (Personal, Academia, Studio, Loja) redirects to `cadastro.sucesso` with `->with('cad_tipo', ...)`, instead of dropping a flash message on the login page. `resources/views/cadastro/sucesso.blade.php` is a thin renderer: the whole welcome message (title, paragraphs, three highlight cards) comes from `config('textos.boas_vindas.{cad_tipo}')`, falling back to `padrao` when the flash is gone (F5 on the page). Paragraphs are printed with `{!! !!}` so `<strong>` survives — it is our copy from config, never user input, so nothing from a form may be interpolated there. The copy must not mention the commission, a contract, or a monthly fee; the first is not the professional cost it used to look like and the other two do not exist (see *Revenue Model*). Cliente signup doesn't need approval and still goes straight to login.

### Cadastro — paridade site ↔ app

**Os dois cadastros coletam o MESMO conjunto de campos.** Durante meses não foi assim: o app pedia 5 campos do aluno (nome, e-mail, senha, WhatsApp, modalidade) contra os 17 do site, e o cadastro de personal não pedia especialidades nem bio. Nada quebrava — a conta nascia, logava e funcionava **pela metade**. O prejuízo aparecia longe da causa: ficha de treino sem data de nascimento, avaliação física sem altura/peso, e um profissional na vitrine sem nenhuma pílula de especialidade e sem apresentação, competindo em desvantagem com quem veio pelo site.

As portas são: `Cadastro\{Cliente,Personal,Academia,Studio,Loja}Controller@store` (web), `Api\AuthController@register` (cliente no app) e `Api\RegisterController@{personal,academia,studio,loja}` (demais papéis no app). Academia/studio/loja já eram espelhos exatos — e a API ainda é mais rígida que o web ali, porque `garantirEmailLivre`/`garantirCnpjLivre` checam colisão entre papéis (o web só faz isso em studio e loja).

`tests/Feature/ParidadeCadastroAppTest.php` **não lista campos**: manda o mesmo payload nas duas portas do aluno e compara `getAttributes()` das duas linhas gravadas, ignorando só id/e-mail/senha/timestamps. Um campo adicionado em um lado só falha ali sem ninguém precisar lembrar de atualizar uma lista. O payload mínimo válido do aluno vive em `TestCase::dadosRegistroAluno()` — três arquivos de teste tinham a sua própria cópia e os três quebraram juntos quando `idade`/`sexo`/`cep` passaram a ser obrigatórios no app.

**`config('textos.profissional.sexos')` é a fonte única da lista de sexo.** Eram três cópias (regra do controller, `<option>`s do blade, nada no app) e elas divergiram: o formulário mandava `value="Prefiro não informar"` enquanto a regra aceitava só `Masculino|Feminino|Outro` e a coluna é um **enum em minúsculas** — escolher a terceira opção derrubava o cadastro inteiro com "sexo é inválido". Hoje a regra sai de `CadastroHelper::regraSexo()` (aceita as duas caixas porque as duas portas gravam `strtolower`, igual ao enum) e o `<select>` itera o config. O rótulo pode ser o que se quiser; o **valor** tem de sair do config.

**`clientes` não tem latitude/longitude.** A regra do web as validava e o mass-assignment as descartava em silêncio; foram removidas em vez de copiadas para a API. Personal/academia/studio/loja **têm** as colunas, e aí é o contrário: o app precisa preenchê-las. `geocodificar()` (`utils/br.js`) resolve a coordenada no Nominatim depois do ViaCEP, que é a sequência exata dos formulários do site — sem ela todo cadastro feito pelo app ficava fora do mapa e da ordenação por distância, sem erro nenhum à vista. Falha em silêncio de propósito: a coordenada é um extra e nunca pode impedir o envio.

**`GET /api/v1/cadastro/opcoes` serve as listas fechadas (modalidades, especialidades, sexos) do `config/textos.php` para o app.** Não embuta uma cópia dessas listas no app: as regras validam com `Rule::in(config(...))`, e uma lista velha dentro de um binário já publicado na loja vira **422 em cima do usuário** no dia em que alguém editar o config — e ninguém vai lembrar da relação. O app mantém só o `FALLBACK_OPCOES` de `src/api/opcoesCadastro.js`, o mínimo para renderizar offline; quem valida continua sendo o servidor.

**O perfil do aluno edita o mesmo conjunto nos dois lados.** A divergência era nas duas direções: `altura`, `peso` e `sexo` só no site; `whatsapp` e `resumo_objetivo` só no app; `condicao_clinica` e `frequencia_semanal` em **nenhum** dos dois (coletados no cadastro e nunca mais editáveis). Altura e peso são os campos que *mudam* e que a avaliação física e o IMC leem — pelo app o aluno ficava preso ao peso do dia em que criou a conta. Hoje `Api\PerfilController` (papel cliente) e `Cadastro\ClienteController@update` cobrem o mesmo conjunto, pinado por `ParidadePerfilAlunoTest`.

Duas decisões dentro disso: **`sexo` é `sometimes|required`, não `required`** — a coluna é um enum NOT NULL (não pode virar null), mas exigir o campo quebraria o salvamento de perfil em todo app **já instalado**, que não o envia; e **`email`/`senha` seguem só no site de propósito** — `email` é o identificador de login e a unicidade entre os cinco papéis não é checada nem no site, e a troca de senha lá não pede a senha atual (sessão roubada = conta tomada). Replicar isso no app espalharia a falha; há um teste afirmando que o PUT do app ignora os dois.

**Conferido e idêntico** (não mexa achando que falta): avaliação física (75 campos em `AvaliacaoFisicaController@salvarRegistro` = `Api\AvaliacaoFisicaController@store` + `cliente_id`, que no site vem da rota), anamnese (9 campos nos três pontos: aluno, personal e academia), progresso/medidas (9) e fotos (4), metas (regras byte a byte iguais), ficha de treino e mesociclo (via `Api\TreinosGestaoController`), preços de pacote, planos/filiais/professores/aulas da academia, studio e loja.

**`pacotes/salvar` tirava o dono do corpo da requisição.** `Cadastro\PacoteController@store` aceitava `personal_id` do formulário validando só `exists:personals,id`, e a rota está sob `CheckLogin` — que aceita os cinco papéis. Ou seja: qualquer usuário logado, **inclusive um aluno**, reescrevia a tabela de preços de qualquer personal (A01). O `Api\PersonalGestaoController@salvarPrecos` já tirava do token; o web é que estava fora do padrão. Dono vem da sessão, sempre.

**Lacuna conhecida que sobrou:** os **pacotes de avaliação física** (`PacoteAvaliacao`) só são gerenciáveis pelo site — `POST/PUT/DELETE personal/avaliacao-fisica/pacotes` não têm equivalente na API. O personal consegue ajustar a *tabela de preços* pelo app (`POST /personal/precos`), mas não criar/editar/apagar os pacotes nomeados. Fechar isso exige os 3 endpoints **e** uma tela no app — backend sozinho seria mais uma funcionalidade write-only.

**Nenhum cadastro do app dispara `CompleteRegistration` no Meta** (o web dispara nos cinco). Isso é uma lacuna conhecida, não um esquecimento: `MetaConversionsService::trackServer()` existe e funcionaria, mas o app não tem o banner de consentimento que o web tem, e `META_REQUIRE_CONSENT=true` é o padrão — disparar dali seria furar o gate de LGPD que o projeto construiu de propósito. Se for para fechar essa lacuna, o caminho é consentimento no app primeiro.

### Idade mínima — 18 para o aluno, 21 para o personal

`CadastroHelper::IDADE_MINIMA_ALUNO` (18) e `IDADE_MINIMA_PROFISSIONAL` (21) são a **fonte única**. Delas saem: as regras das quatro portas de cadastro (`Cadastro\{Cliente,Personal}Controller@store`, `Api\AuthController@register`, `Api\RegisterController@personal`), o `min`/`max` do `<input type="date">` nos dois formulários, o aviso na tela e o `idade_minima` de `GET /api/v1/cadastro/opcoes` que o app baixa. **Não escreva 18 nem 21 em outro lugar** — é a mesma armadilha do `0.10` da comissão, que sobreviveu à própria correção em nove cópias.

A coluna é `idade` mas guarda uma **data de nascimento** (nome histórico, nas duas tabelas). `regraNascimento()` monta `date` + `before_or_equal:hoje-N` + `after_or_equal:hoje-110`; `limitesNascimento()` devolve a mesma faixa para o atributo do campo, para o navegador e o servidor nunca discordarem. O limite é **inclusivo**: quem completa a idade **hoje** entra. O teto de 110 anos existe para o lado de cima — um ano digitado errado passa pela idade mínima com folga e grava um nascimento absurdo que só reaparece como idade estranha na avaliação física, que calcula a idade a partir desta coluna.

Academia, studio e loja **não entram nisso**: são pessoa jurídica (CNPJ) e nenhum dos dois cadastros coleta nascimento de um responsável. Não há o que validar ali.

**`idade` saiu do grupo `sometimes|required` da API, e é a única que saiu.** `sexo` e `cep` continuam `sometimes` por compatibilidade com o binário publicado na loja, que não os envia — o raciocínio original (ver *paridade site ↔ app*) é que uma conta sem esses campos é uma conta incompleta que o próprio aluno termina depois. **Esse raciocínio não transfere para a idade:** ali o campo deixou de ser completude de dado e passou a ser a trava, e com `sometimes` bastava **omitir** o campo para não haver trava nenhuma — e o app antigo omite. Uma trava com saída não é trava, e conta de menor de idade não é "conta pela metade": não se completa, se recusa. O preço é conhecido e aceito: quem estiver no app antigo recebe 422 até atualizar, e por isso a mensagem de `idade.required` na API manda **atualizar o app** em vez de pedir para preencher um campo que naquela tela não existe.

O app valida antes de enviar (`erroNascimento()` em `utils/br.js`, que espelha a regra inclusive na borda), mas **com o número vindo de `/cadastro/opcoes`**, não escrito no binário: uma cópia dentro de um app já publicado fica velha no dia em que o corte mudar, e aí o app deixa passar o que o servidor recusa. No cadastro de personal a checagem roda **antes** da foto, para não mandar escolher e subir imagem antes de dizer que a idade não permite.

`tests/Feature/IdadeMinimaCadastroTest.php` fixa tudo isso, com todas as datas calculadas de `now()` e das constantes — nunca cravadas. Um teste com `2008-10-10` escrito à mão passa a testar outra idade a cada ano, e um teste com `18` cravado continuaria verde justamente no dia em que o corte mudasse.

### Trava de `APP_DEBUG` — duas camadas, e `APP_ENV` é a que importa

`AppServiceProvider::boot()` desliga `app.debug` e as runnable solutions do Ignition à força quando `environment('production')`, além de `URL::forceScheme('https')`. **Essa trava existia e não protegeu nada**, porque o `.env` do servidor estava com `APP_ENV` diferente de `production`: um 404 em snrfit.com.br devolvia `exception`/`file`/`line`/`trace` com o caminho absoluto do servidor — e num 500 a mesma tela mostra `DB_PASSWORD`, `ASAAS_*`, `TWILIO_AUTH_TOKEN` e `APP_KEY`.

A lição é que `APP_ENV` é o interruptor de quase todo o endurecimento (debug, HTTPS nos links, Ignition, e por tabela cookie Secure e CORS). Conferir só `APP_DEBUG` não basta.

Daí a **segunda camada**, `travarDebugEmHostPublico()`: decide por `App\Support\Ambiente::hostEhPublico(request()->getHost())`, não pelo `.env`. O host vem da requisição, então não há o que esquecer de configurar. Domínio público ou IP roteável → debug desligado nesta requisição + um `critical` no canal `security` (throttled a 1/hora com `Cache::add`, porque isto roda em toda requisição enquanto o servidor estiver assim). `localhost`, `.test`, hostname sem ponto e **IP de rede privada** continuam com debug — este último é obrigatório: o celular acessa o backend por IP de LAN durante o trabalho no app.

**Não há escape por env, de propósito.** `APP_DEBUG` governa apenas o que o *visitante* vê; o stack trace completo vai para `storage/logs/laravel.log` nos dois casos. Travar não custa diagnóstico — por isso a trava pode ser absoluta.

`Ambiente::hostEhPublico()` usa `FILTER_FLAG_NO_PRIV_RANGE|NO_RES_RANGE` em vez de comparar prefixos à mão: a faixa privada 172.16/12 termina em **172.31**, e `172.32.x` é público. `TravaDebugTest` fixa essa tabela de hosts, incluindo esse caso.

### `payments.status` inclui `processing` — não tire

`enum('pending','processing','succeeded','failed','refunded')`. O `processing` existe porque `PaymentController::processarPagamentoConfirmado` reivindica a entrega com um **UPDATE condicional atômico** (`whereNotIn('status', ['succeeded','processing'])->update(['status' => 'processing'])`), que é o que impede o webhook reentregue pelo Asaas de agendar, baixar estoque e repassar duas vezes.

O valor **ficou faltando no enum por ~3 semanas** (o commit `6b8c58a2` escreveu `processing` sem migration). A conexão roda `'strict' => true` com `STRICT_TRANS_TABLES`, então aquele UPDATE **lançava** — e ele é a *primeira* instrução do método. Efeito: **toda cobrança que passava por ali era recebida no Asaas e nunca cumprida.** Nada depois do claim executava — pacote não agendado, estoque não baixado, plano de academia/studio não vinculado, ninguém avisado — e o pagamento ficava preso em `pending` enquanto o Asaas reentregava para estourar de novo. Consertado por `2026_10_09_000001_add_processing_to_payments_status`.

**Num banco sem modo estrito o sintoma é pior:** o valor inválido vira string vazia, `whereNotIn(...)` deixa de casar e o guard de idempotência cai — aí o reenvio do webhook **reprocessa** (estoque, agendamento e repasse em dobro). Se mexer no claim, mexa no enum junto.

Os testes `LojaCheckoutTest > checkout cartao confirma...` e `AcademiaPagamentoTest > pagamento cartao academia confirma...` acusavam isso o tempo todo (esperavam `succeeded`, achavam `pending`) e foram tratados como "falha conhecida". **Um teste que falha desde sempre é uma hipótese, não um fato.** `Data truncated for column` nunca é flake: é valor fora do enum.

### Avisos de eventos — `AvisoService` é o texto, `NotificacaoService` é o canal

`NotificacaoService` é o **canal**: uma chamada entrega em quatro lugares — aviso in-app (`notificacoes`), push no aparelho (via `Notificacao::para` → `ExpoPushService`), WhatsApp e e-mail. `NotificacaoService::conta($model, ...)` serve **os cinco papéis** (detecta pelo model via `tipoDe()`); `personal()` e `cliente()` são só atalhos que delegam para ela.

`AvisoService` é **o que se diz em cada evento**, um método por evento. A separação é prática: quase todo evento é disparado de *dois* lugares (controller do site + controller da API), e quando o texto mora no controller as duas cópias divergem ou só uma existe. Foi o caso do cancelamento de aula — `Cadastro\PersonalController@cancelarAula` e `Api\PersonalGestaoController@cancelarAula` são cópias quase idênticas, as duas mandavam e-mail e **nenhuma** avisava o aluno dentro do app.

Três regras ao acrescentar um evento: **(1) nada ali pode lançar** (aviso é efeito colateral de um fluxo já concluído — por isso tudo passa por `seguro()`, que só loga); **(2) avise os dois lados** (quem vendeu e quem comprou — metade do aviso é o que gera ticket de suporte); **(3) o texto diz o que fazer, não só o que houve** (o push aparece justamente quando o usuário não está no app).

**Antes de criar um evento novo, procure quem já avisa.** O pacote já notificava o personal de dentro de `agendarAulasInterno` (`notificarPersonalWhatsApp`, que carrega o template aprovado na Meta), e a primeira versão de `pacoteConfirmadoParaAluno` avisava os dois — gerando **dois** avisos ao personal. Quem pegou foi o teste de integração afirmando a **contagem** (esperava 1, achou 2). Mesma razão pela qual o pedido pago da loja **não** tem método aqui: `PaymentController::notificarPedidoLoja` já montava um texto melhor (itens, entrega, contato) a partir do `Pedido`; o que faltava era o canal, e lá agora ele passa por `NotificacaoService::conta()`.

**A caixa de avisos existe para os cinco papéis.** `dest()` (no web `Cadastro\NotificacaoController` e no `Api\ChatController`) conhecia só personal e cliente, e devolvia null para academia/studio/loja — lista sempre vazia. Era furo de duas pontas, porque os outros canais já funcionavam para eles: `push_tokens` registra o aparelho pelo papel do token (`AuthController::userType`) e `ExpoPushService::paraDestinatario` é genérico. A loja recebia o push do pedido no celular e, ao abrir "Avisos" no app, não achava nada. Leitura e escrita saem da mesma `NotificacaoService::tipoDe()`, de propósito.

### Notificações in-app — `notificacoes.mensagem`, não `texto`

A coluna do corpo do aviso é **`mensagem`** (`notificacoes`: `destinatario_tipo`, `destinatario_id`, `titulo`, **`mensagem`**, `url`, `icone`, `lida`). A tabela do chat é outra: `mensagens.texto`. As duas convivem, e confundi-las já custou um bug em produção.

`Api\ChatController@notificacoes` lia `$n->texto`. **Eloquent devolve `null` para atributo inexistente, sem erro nenhum** — então a API respondia 200 com `"texto": null`, `NotificacoesScreen` só desenha o corpo quando `n.texto` tem valor, e **toda notificação aparecia no app com título e data e nenhum texto**. No site aparecia certo, porque a view lê `$n->mensagem` direto do model. O **push nunca teve o problema** (`ExpoPushService` recebe `$mensagem` direto), e foi isso que disfarçou: chegava o aviso com texto no aparelho e a lista dentro do app ficava vazia.

**A chave do JSON continua `texto`** — é o nome que o app já consome, e mantê-la corrige em todo aparelho já instalado no deploy do backend, sem build novo nem revisão de loja. Não renomeie para `mensagem` sem publicar o app junto.

**A lição que vale além deste campo:** um smoke test de status 200 não pega isto, e o `SmokeAppEndpointsTest` de fato passava com o bug no lugar. Quando a tela depende do **valor** de um campo, afirme o valor. `NotificacaoApiTest` compara o corpo que a API entrega com o que o site lê da mesma linha, então a divergência não pode voltar sem alguém ver.

**`criada_em` vai em ISO-8601 COM fuso (`toIso8601String()`), nunca `toDateTimeString()`.** Mesmo campo, segundo bug, descoberto depois: `config('app.timezone')` é **UTC** de propósito (ver o comentário em `config/app.php`, que explica por que não se troca). `toDateTimeString()` devolvia a hora certa **sem dizer em que fuso ela estava**, e o JS interpreta string com espaço como hora *local* — então um aviso criado 11:30 no Brasil era desenhado como **14:30, três horas no futuro, em toda linha da lista**. Não havia como o app acertar sem adivinhar a config do servidor. Com o offset explícito o aparelho resolve o instante e desenha no fuso de quem está olhando, que é o único jeito certo — o usuário pode estar em qualquer lugar. Trocar a *chave* quebraria o app publicado; trocar o *formato* não, porque a expressão que o app publicado aplicava neste campo já produzia texto quebrado nos dois formatos. `NotificacaoApiTest::test_criada_em_informa_o_fuso` afirma as duas coisas: que o fuso vem, e que o instante entregue é o mesmo que está gravado.

**Lacuna deliberada:** o push sempre manda `data = ['tela' => 'Notificacoes']` (`Notificacao::para`), e a API **não** expõe `url` nem `icone`. Então todo aviso leva à *lista*, nunca ao assunto — "nova mensagem de X" abre Avisos, não a conversa. Fechar isso não é expor `url`: a coluna guarda uma rota **web** (`route('chat.conversa', …)`), que não existe no app. Exigiria um campo de destino que os dois lados entendam (ex.: `destino: {tela, params}`) decidido em `AvisoService`/`Notificacao::para`, mais o mapeamento no app — e o app publicado ignoraria o campo novo até atualizar, o que é aceitável porque o fallback já é a lista.

#### Sinal de aviso novo DENTRO do app

`GET /notificacoes/nao-lidas` existe desde sempre e o app **nunca o chamava**; `QuickAccessRow` (`components/UI.js`) já desenhava `item.badge` com estilo pronto e **nenhuma tela passava o valor**. As duas metades da mesma funcionalidade estavam construídas e desconectadas, então o atalho "Avisos" ficava idêntico com 0 ou com 12 pendências: fora do banner na tela de bloqueio, o app não dava **nenhum** sinal de que havia aviso novo — quem descartasse o push ou abrisse o app direto não ficava sabendo. É o mesmo modo de falha que `personals.modalidade` teve (dado coletado que ninguém lê), aqui na direção inversa: UI pronta que ninguém alimenta.

O contador mora no `AppContext` (`avisosNaoLidos` / `recarregarAvisos`), não na tela, porque quem o exibe é a **Home** e quem o zera é a tela de Avisos — e o push que chega precisa mexer nos dois. Falha de rede **mantém** o valor anterior em vez de zerar: zerar esconderia aviso que existe.

Também faltava o listener de notificação **recebida**: só havia o de *toque* (`addNotificationResponseReceivedListener`). Com o app aberto o banner aparecia e nada dentro do app reagia. Agora `aoReceberNotificacao` (`notifications/push.js`) atualiza o contador e a lista aberta.

Ainda não ligado, de propósito: `shouldSetBadge: false` no handler — o número no ícone do app exige o `badge` dentro do payload do push (o Expo não soma sozinho), ou seja, o backend teria de contar as não lidas na hora do envio. É uma mudança no `ExpoPushService`, não no app.

#### Avisos PROATIVOS — os únicos que alcançam quem não está no app

Quase todo aviso do projeto é **reativo**: dispara de um controller, porque alguém fez algo. Dos pontos que chamam `Notificacao::para`/`NotificacaoService`, **só dois são agendados** — `personal:lembrete-diario` e `aluno:lembrete-aula`. São os únicos avisos que a plataforma gera por iniciativa própria, e por isso os únicos em que o push tem valor real (o usuário não está olhando).

**O lembrete do aluno não existia.** O personal era avisado ~90 min antes do primeiro compromisso desde o começo, e o aluno — que é quem precisa sair de casa, levar roupa e chegar no horário — não recebia **nada**. O módulo de reposição inteiro existe para remediar a falta depois que ela acontece.

Três armadilhas que o comando do personal tinha, e que o novo evita (`app/Console/Commands/LembreteAulaAluno.php`):

1. **A conta de horário tem de passar por `AgendaService::agora()`/`inicioDaAula()`.** `agendas.hora_inicio` é hora de parede do Brasil, `app.timezone` é UTC. O comando antigo usava `Carbon::now()`/`Carbon::today()` e montava o início da aula com `Carbon::parse()` sem fuso — medido minuto a minuto, **o lembrete de uma aula das 07:00 disparava entre 02:20 e 02:40 da manhã**. `Carbon::today()` em UTC também erra a *data* da agenda entre 21:00 e meia-noite no Brasil. `AgendaService` já tinha os helpers certos; este comando nunca recebeu a correção.
2. **Push não pode depender do WhatsApp.** A consulta filtrava `whereNotNull('whatsapp')` e havia um `return self::FAILURE` quando `WHATSAPP_TOKEN`/`WHATSAPP_PHONE_NUMBER_ID` faltavam — chaves que **não estavam no `.env.example`**. Ou seja: a única notificação proativa da plataforma ficava desligada por uma credencial que ninguém sabia que precisava configurar, e personal sem número não recebia nem push. A falta agora só desliga o canal WhatsApp.
3. **A reserva do envio vem ANTES do envio, e é atômica (`Cache::add`).** O agendador roda a cada 5 min dentro de uma janela de 20, então quatro rodadas tocam a mesma aula. O código antigo gravava a chave *depois*, e só se `$enviado` fosse true — mas `$enviado` reflete apenas WhatsApp/e-mail, não o push: um personal sem WhatsApp cujo e-mail falhasse recebia **quatro pushes** do mesmo lembrete. Reserve por aula (o aluno pode ter duas no dia), não por pessoa.

`tests/Feature/LembreteAulaTest.php` fixa a **hora** do disparo, não só o conteúdo — congela o relógio em UTC e define a aula em hora de parede, que é exatamente a combinação onde o erro aparecia.

**O que ainda não existe de proativo** (lacunas conhecidas, não esquecimento): nada de reengajamento do aluno que parou de treinar; nenhum disparo em massa/promocional — `ExpoPushService::enviar()`, o método que já faz lotes de 100 tokens, **nunca é chamado**, só `paraDestinatario()`; e `celebracoes:retrospectivas` roda diariamente mas apenas *enfileira* para o app exibir no próximo acesso, sem push. Para promoção faltam ainda duas coisas que não existem em nenhum lugar do banco: **preferência/opt-out de notificação** e separação entre marketing e transacional — exigência de política da Apple/Google e base legal distinta na LGPD (o aceite dos Termos não cobre marketing).

#### `Screen` é o dono do "puxar para atualizar"

O app tinha **60+ telas e nenhum `RefreshControl`** — a única forma de recarregar qualquer coisa era sair da tela e voltar. Como `components/UI.js > Screen` é o dono do `ScrollView` de todas elas, o gesto virou uma prop opcional ali: `onRefresh` (deve devolver Promise; o spinner fica até resolver). Ligado em `Notificacoes` e `Home`; nas outras é uma linha. Só funciona com `scroll` (o padrão) — `View` não tem `refreshControl`.

### Referral Coupons ("Indique e ganhe")

Every account (Personal, Cliente, Academia, Studio, Loja) gets a personal referral code and can enter someone else's at signup. Tracking + bonus only — it does **not** change any amount charged.

**The bonus is revenue share, not a fixed amount.** The referrer earns `config('indicacao.percentual')` (**10%**) of the **platform's commission** on everything the **referred** account bills during a window of `config('indicacao.janela_dias')` (**35 days**) counted from the referred account's **approval** — see the base-of-calculation block below, it is 10% of our 10%. The accrued amount can only be **withdrawn** after that window closes — and only if the referred account also has `config('indicacao.meta_alunos')` (6) students won through the platform. There is **no fixed R$ 30 bonus** any more; `cupons.bonus_valor` stays 0 on `indicacao` coupons (it still carries the fixed value of admin `promocional` campaigns).

**The base is the PLATFORM'S COMMISSION** (`payments.company_fee`) — it is **10% of our 10%**, not 10% of what was charged. The base is **not** the referred account's revenue, **not** what the professional takes home, and the bonus never touches their payout. On a referred account that sells R$ 2.500 of services inside the window:

| | |
|---|---|
| Sold by the referred account | R$ 2.500,00 (not the base) |
| Goes to the professional (in full) | R$ 2.500,00 — untouched |
| Paid by the students | R$ 2.750,00 (price + commission) |
| **Platform commission (10%)** | **R$ 250,00 ← the base** |
| Referrer's bonus (10% of the commission) | R$ 25,00 |
| Platform keeps | R$ 225,00 |

So the bonus is effectively **1% of what the referred account sells**, and the platform still keeps 9%. Because the commission is a markup rather than a discount, the referred account receives exactly the same with or without the programme. Because the bonus is carved out of the commission, it is **structurally capped** by it: it can never exceed what actually came in, which is why `CupomService::percentual()` clamps to `[0, 1]` (1.0 = the whole commission). `config('indicacao.percentual')` is the one number that moves the split between the two: 0.10 → R$ 25 referrer / R$ 225 platform; 0.50 → R$ 125 each.

`AsaasService::feeRate()` (from `config('taxas.comissao')`, never a second literal) is the commission rate.

`indicacao_creditos.base_valor` is the **commission**; `bruto_valor` carries `payments.amount_total` alongside it **purely for display**, so the extrato can show the whole chain (aluno pagou → comissão → sua parte). It is null on credits written before that column and enters no calculation. When a receita has no usable recorded commission (legacy rows may hold zero), `CupomService::comissao()` derives it rather than crediting zero — zero would silently erase someone's bonus, and a recorded value above the gross would pay out more than came in, so both are rejected.

**That derivation is `comissaoEmbutida()`, not `bruto × feeRate()`.** With the commission as a markup, the gross *contains* it, so it comes out by dividing: in R$ 110 the commission was R$ 10 (110 × 1/11), not R$ 11. Multiplying would pay the referrer on money that never came in. It only ever reaches rows with no usable `company_fee`; rows from the old model land there with a few cents of drift, and accepting that beats keeping two derivations and picking one by payment date.

The arithmetic is spelled out in five places that must stay in sync: the header comment of `config/indicacao.php`, the "Sobre qual valor incidem os 10%" block on `/indicacoes`, the "Regra atual" card on `/admin/indicacoes`, clause **8.5** of the terms (plus the one-paragraph version in each of the four role-specific terms), and here. The panel and the terms both **compute** the worked example from `percentual` × `feeRate()` × `config('indicacao.exemplo_base')` instead of hardcoding R$ 25 — a hardcoded example starts lying the day the percentage changes, and nobody opens a view when editing a config.

**Changing the base means re-apuração, not a data fix.** `2026_10_03_000003_indicacao_base_passa_a_ser_a_comissao` deletes the credits of referrals **not yet attached to a saque** and clears `apurado_em`, letting the scan rewrite them under the new rule from the same `payments` — that self-healing property is the whole reason the apuração is a scan. Referrals already linked to a saque (`saque_id` set) are deliberately left alone: the money has left or is in flight, and rewriting the ledger under a completed payment is worse than the inconsistency.

Six rules that are easy to get wrong:

1. **The window is stamped on `cupom_usos`, not derived on read** — `CupomService::iniciarJanela()` writes `janela_inicio` (= the referred account's `data_aprovacao`) and `janela_fim` **only when `janela_inicio` is null**. That guard is load-bearing: `data_aprovacao` is rewritten by reactivation flows (`AdminController::reativarLoja`), so deriving the window from it on every read would hand out 35 fresh days every time an account is blocked and reactivated.
2. **Apuração is a scan, not a hook** — `CupomService::apurar()` sweeps `payments` inside the window and inserts the missing credits. Revenue lands through many paths (`pagarSucesso`, the Asaas webhook, subscription renewal) and a forgotten hook in any one of them would silently lose someone's money. The scan is idempotent by construction, self-heals, and stays off the payment hot path. **Don't replace it with a hook in `handleSuccessfulPayment`.**
3. **`indicacao_creditos.origem` is the idempotency key** — unique in the DB (`payment:123`). It is what stops a webhook retry or a re-apuração from paying twice. Production also holds historical `nutri_cobranca:45` rows from when the nutri module was in main; nothing writes that prefix any more, but `IndicacaoCredito` still labels it so the extrato of those credits keeps reading correctly. The table is **append-only**: never UPDATE a credit; a correction is a new row.
4. **What counts as a student** — `TemCupomIndicacao::alunosPelaPlataforma()` counts distinct clients with a *confirmed payment* (`Cupom::STATUS_PAGAMENTO_VALIDO`) in `payments`/`subscriptions` for that receiver (`trainer_id`/`academia_id`/`studio_id`/`loja_id`). It deliberately does **not** count hand-made links (`clientes.academia_id`, unpaid agenda rows) — those are free to create, so counting them would let anyone link 6 friends and unlock the withdrawal. If you extend this, keep the "money actually moved" bar.
5. **Referring a student earns nothing** — a `Cliente` has no students, so the use is stored as `sem_bonus` with `bonus_valor = 0` (history only). Otherwise creating fake student accounts would be a cheap farm.
6. **Release is sticky** — `CupomService::reavaliar()` only moves `pendente → liberado` and stamps `liberado_em`. A referred account that later loses students does **not** re-lock the bonus; the referrer doesn't control the other party's churn, and a balance that disappears from the panel is worse than a strict rule.

`cupom_usos.bonus_valor` is the **running total** of that referral's credits (it grows while the window is open and freezes when it closes), not a snapshot of a fixed amount. `apurado_em` keeps the cron cheap: once the window has closed and been apurado after `janela_fim`, `apurar()` short-circuits.

**Known gap, deliberate:** a refund after a credit was written does not claw the credit back (`refunded` is excluded from `STATUS_PAGAMENTO_VALIDO`, so it never *creates* a credit, but an already-credited payment later refunded stays credited). Reversing that would mean clawing back money possibly already paid out; for now the admin's lever is refusing the saque.

Re-evaluation (window + apuração + release) runs when the referrer opens `/indicacoes`, and daily at 04:00 via `php artisan indicacoes:reavaliar` (registered in `Console\Kernel`). **The scheduled run is not optional** — without it a bonus only unlocks the next time the referrer happens to log in.

Four tables: `cupons` (`codigo` unique, `tipo` = `indicacao` | `promocional`, polymorphic `dono` — null on admin campaigns, `bonus_valor`, `ativo`, `expira_em`, `limite_usos`, `usos`); `cupom_usos` (polymorphic `usuario`, `bonus_valor` accrued, `status` = `pendente` | `liberado` | `sem_bonus` | `cancelado`, `janela_inicio`, `janela_fim`, `apurado_em`, `liberado_em`, `saque_id`, `ip`; **unique on `usuario_type` + `usuario_id`** so an account can only be referred once); `indicacao_creditos` (the ledger: `cupom_uso_id`, `origem` unique, `base_valor` = the platform's commission, `bruto_valor` = the gross, display-only and nullable, `percentual`, `valor`, `ocorreu_em`); `indicacao_saques` (withdrawal requests: polymorphic `usuario`, `valor`, `status` = `solicitado` | `processando` | `pago` | `recusado` | `falhou`, `metodo` = `automatico` | `manual`, `pix_chave` **encrypted** + `pix_tipo`, `asaas_transfer_id` **unique**, `asaas_status`, `transferencia_em`, `receipt_url`, `falha_motivo`, `admin_id`, `processado_em`, `ip`). Models `App\Models\{Cupom, CupomUso, IndicacaoCredito, IndicacaoSaque}`; the five account models use the `App\Models\Concerns\TemCupomIndicacao` trait (`cupomIndicacao()`, `indicacaoRecebida()`, `indicacoesFeitas()`, `codigoIndicacao()`, `saldoDisponivel()`, `bonusEmSaque()`, `bonusSacado()`, `bonusPendente()`, `totalIndicacoes()`).

All the accrual rules live in `App\Services\CupomService`:
- `regraValidacao()` — drop into each cadastro's `$request->validate()` as the `cupom` rule. A wrong code **blocks the submit** with a clear message instead of being silently dropped. Always `Arr::pull($dados, 'cupom')` before `Model::create()` — `cupom` is not a column on any of the five tables.
- `registrarIndicacao($codigo, $model, $ip)` — call right after create. Never throws (a failure here must not undo a persisted signup), blocks self-referral (same account **or** same e-mail as the owner), increments `usos` inside a `lockForUpdate` transaction, and decides the starting status (`sem_bonus` for a `Cliente`, otherwise `pendente` with `bonus_valor = 0`).
- `iniciarJanela($uso)` / `apurar($uso)` — see rules 1–3. Both are idempotent and neither throws.
- `reavaliar($usos)` / `reavaliarDoIndicador($dono)` — the full cycle: open window → apurar → release what cleared both gates.
- `cupomDe($model)` — get-or-create the account's own code, generated from the name with an unambiguous alphabet (no 0/O, 1/I) so it can be dictated over the phone.

**Withdrawals** live in `App\Services\IndicacaoSaqueService` — the only place that moves money out:
- `solicitar($usuario, $pixChave, $ip)` — the form posts **only the Pix key**. The amount is summed server-side from `cupom_usos` that are `liberado` with `saque_id` null, inside a transaction that `lockForUpdate`s those rows and then stamps `saque_id` on them. That stamp is what makes double withdrawal impossible: a concurrent second request finds the rows already attached, sums 0, and is refused. One open request per account (`emAberto` spans `solicitado` **and** `processando`); minimum `config('indicacao.saque_minimo')`.
- `pagar($saque, $adminId)` / `recusar($saque, $adminId)` — both idempotent (no-op on an already-processed request) and both act **only** on `solicitado`: a `processando` saque has a real transfer in flight at Asaas and must not be closed by hand. Refusing unbinds the `cupom_usos` so the balance returns to the referrer; the bonus itself is never cancelled.
- This service **never looks at dates** — it only trusts the `liberado` status, so `CupomUso::podeLiberar()` stays the single gate that can unlock money.

#### Automatic Pix payout (Asaas `/transfers`) — the most dangerous path in the app

The bonus comes from the platform's commission, which sits in the **root** Asaas account, so the automatic payout is a `POST /transfers` with the platform's `access_token` — money leaving our own balance to a key a user typed. It is **opt-in** (`INDICACAO_SAQUE_AUTO`, default `false`): no deploy ever enables real money movement.

`AsaasService::transferirPix()` / `saldoPlataforma()` / `consultarTransferencia()` / `tipoChavePix()` wrap the API. `transferirPix` never throws — it returns `['ok' => false, ...]`, and on a **timeout** it returns `indeterminado` because the transfer may exist; the caller must then keep the saque in `processando` and let reconciliation settle it (retrying would risk paying twice).

Seven layers stop an over-transfer, each sufficient on its own:

1. Amount is server-summed; the form carries no value and no user id (pinned by a test that posts `valor` and `status` and asserts they are ignored).
2. One open request per account, enforced with `lockForUpdate` + the `saque_id` stamp.
3. **Per-request ceiling** `saque_auto_teto` — above it there is no automation at all; the request goes to the admin queue. This is what bounds the damage of any single failure.
4. **Global daily ceiling** `saque_auto_teto_diario`, summing the day's automatic saques.
5. Balance is checked first, and `saldoPlataforma()` returning `null` (couldn't ask) **aborts** — never assume funds.
6. Status is flipped to `processando` with an optimistic guard (`where status = solicitado AND asaas_transfer_id IS NULL`) **before** the API call, so two concurrent runs can't both transfer. `indicacao_saques.asaas_transfer_id` is UNIQUE as the DB-level backstop.
7. **The Asaas withdrawal-validation webhook is a second, independent authorization.** Every payout sits ~5 s pending while Asaas POSTs the operation to us and waits for `{"status":"APPROVED"}` or `{"status":"REFUSED","refuseReason":"…"}`; if our webhook fails 3× or answers neither, **Asaas cancels the operation** — so our failures fail closed.

`AsaasWebhookController::validarSaque()` holds that policy, and it is **fail-closed**:
- `type: TRANSFER` → `IndicacaoSaqueService::autorizarTransferencia()` approves only when the `externalReference` (`indicacao_saque:{id}`) resolves to a saque in `processando`, the `value` matches to the cent, the `transfer.id` matches what we stored, and the `pixAddressKey` matches the key the owner registered. Anything else is `REFUSED` and logged.
- `type: PAYMENT_SPLIT` → **APPROVED**. Splits are the core of the payout model and are defined by us at charge creation; refusing here would halt every payout on the platform. Don't "harden" this into a refusal.
- any other type (BILL, PIX_QR_CODE, PIX_REFUND, MOBILE_PHONE_RECHARGE) → `REFUSED`; the platform never emits them.
- `personal_saque:{id}` → `autorizarSaquePersonal()`, verified against `personal_saques` (value + a `CREATING`/`PENDING` status).

Because of that fail-closed rule, **every transfer the app creates must carry a verifiable `externalReference`**. That is why `PaymentController::sacarPersonal()` and `sacarSubconta()` now create their `personal_saques` row *before* calling Asaas (`status = 'CREATING'`, then `external_reference = personal_saque:{id}`) and update it after. If you add a new transfer anywhere, give it a reference and teach `validarSaque()` to verify it, or the webhook will refuse it.

Reconciliation: `TRANSFER_DONE` → `pago`; `TRANSFER_FAILED`/`CANCELLED` → `falhou`, which **unbinds the `cupom_usos` so the balance returns** to the referrer (no money left, so the bonus must come back). `concluirTransferencia()` is idempotent, and events whose `transfer.id` doesn't match ours are ignored. `php artisan indicacoes:conciliar-saques` (scheduled every 10 min) polls Asaas for anything stuck in `processando` — the net for a lost webhook. A saque left `processando` with **no** `asaas_transfer_id` (creation timed out) is deliberately left for a human rather than retried.

Admin queue (`/admin/indicacoes`): "Enviar Pix" (`adminSaqueTransferir` → `pagarViaAsaas`) fires the transfer for a request above the ceiling — the admin **authorizes**, they never type an amount or a key, both come from the record. "Marcar pago" / "Recusar" remain the manual fallback.

**Operational prerequisites for turning this on**: `ASAAS_WEBHOOK_TOKEN` must be set (without it `validarSaque` refuses everything), the withdrawal-validation webhook must point at `POST /api/asaas-webhook`, and the Asaas account must **not** require an SMS token for transfers — with it the API returns `authorized: false` and the payout waits for a human in the Asaas panel (we record that in `falha_motivo`).

`Cupom::normalizar()` is the single entry point for anything the user typed (`" perso-nd9qs "` → `PERSOND9QS`) — use it for form input, query strings and admin search alike.

UI: the shared field is `partials/campo-cupom.blade.php` (included by all five cadastro views; live check against `GET /cupom/validar`, public but `throttle:20,1` so codes can't be enumerated). Invite link `/cadastro/selecionar?cupom=XXXX` shows who referred you and carries the code into the chosen form. `/indicacoes` (`check.login`, resolves any of the five session keys) is the user panel — balance, the "Quem entrou pelo seu código" table (one row per referral with the window countdown, the students progress and the accrued bonus), a per-referral **extrato** expandable row listing every credited receita (date, origin, the referred account's gross, and the referrer's cut) so the total is auditable before withdrawing, plus the withdrawal form and request history; `POST /indicacoes/saque` is `check.login` + `throttle:5,1`. The extrato needs `creditos` eager-loaded in `painel()` or it is one query per row. `/admin/indicacoes` (`check.admin`) lists coupons, bonus owed, the withdrawal queue (full Pix key, needed to pay) and creates promotional campaigns. Percentage, window, goal, minimum and all copy are in `config/indicacao.php` — run `php artisan config:clear` after editing.

OWASP notes for this module (keep them if you touch it): the amount is never accepted from the client and the owner is always resolved from the session, never from request input (A01/A04); `status`, `metodo`, `admin_id`, `processado_em` and every transfer column are outside `IndicacaoSaque::$fillable` so no mass assignment can move a request to `pago` (A04); `pix_chave` uses the `encrypted` cast and is masked everywhere except the admin queue, where it's needed to pay (A02); the window query uses `whereRaw(... BETWEEN ? AND ?)` with bindings and the receiver column comes from a `match` whitelist, never from input (A03); credits are idempotent and append-only, and transfer outcomes are idempotent (A08); solicitation, transfer, payment and refusal are logged on the `security` channel **without** the Pix key or the webhook body, which carries destination bank data (A09); the transfer-authorization webhook compares the Pix key with `hash_equals` and is fail-closed in every branch, including a missing/invalid `asaas-access-token` (A01/A07).

Two test files pin all of it: `tests/Feature/IndicacaoRevenueShareTest.php` (accrual, the date gate, "cannot withdraw before the window closes", "a posted `valor` is ignored") and `tests/Feature/IndicacaoSaqueAutomaticoTest.php` (ceilings, balance aborts, and the webhook refusing a tampered value, a changed Pix key, an unknown reference and a replayed transfer — all with `Http::fake`, never the real API).

### Modalidade — offer vs. want vs. this class

Two columns, deliberately **different domains**:

- `personals.modalidade` — what the professional **offers**: `Presencial` | `Online` | `Híbrido` (`config('textos.profissional.modalidades')`).
- `clientes.modalidade_preferida` — what the student **wants**: `Presencial` | `Online` only (`config('textos.profissional.modalidades_aluno')`), chosen at signup, nullable.

**`Híbrido` is not a want.** Nobody searches for "I want both" — it's an offer meaning "I serve either way". So it is absent from the student's options, and instead a hybrid professional **satisfies both** preferences. That rule lives in two mirrored places and must stay in sync: `Cliente::modalidadesCompativeis()`/`atendidoPor()` on the server, and `atendeModalidade()` in the `cliente/personais` JS. There is deliberately **no "Híbrido" filter pill** — it would isolate the most flexible professional from the very searches he serves.

A professional who left `modalidade` blank is **not** filtered out by a student's preference: the missing data is his omission, not the student's choice, and hiding him would punish an incomplete signup. A student with no preference sees everyone.

**Both fields must stay wired to something.** `personals.modalidade` spent months as write-only data — collected at signup, never editable, never displayed anywhere the student could see. That's the failure mode to avoid: it's now editable in the personal's dashboard and shown on the student's card/detail, and `clientes.modalidade_preferida` pre-applies the vitrine filter in `ClienteController@listarPersonais`. If you remove the read, you recreate the dead field.

Filter precedence on `/personais/explorar`: `?modalidade=` from the URL wins over the stored preference; `?modalidade=todas` is the explicit escape; an unrecognized value falls back to "all" rather than erroring. The server resolves it and ships the answer in `data-inicial` on `#filtrosModalidade`, so the JS starts from the server's decision instead of re-parsing the URL. The "como você escolheu no cadastro" notice shows **only** when the filter came from the stored preference — an explicit click or URL needs no explanation.

**Three levels, not two.** `agendas.modalidade` is the third: what **this** class is. The first two don't settle it — a `Híbrido` professional serves both ways, so without a per-booking value the personal receives a reservation not knowing whether to drive to the gym or open a video call.

| Column | Question it answers | Domain |
|---|---|---|
| `personals.modalidade` | what the professional offers | Presencial / Online / **Híbrido** |
| `clientes.modalidade_preferida` | what the student generally wants | Presencial / Online |
| `agendas.modalidade` | how **this** class happens | Presencial / Online (`Agenda::MODALIDADES`) |

`Agenda::modalidadeResolvida()` is the only place that decides it: the student's choice wins; absent that, it fills in **only when deducible** (professional attends one way); for `Híbrido` with no choice it returns **null** rather than guessing — writing "Presencial" by omission would assert something nobody chose and the personal would plan on a hunch. `Agenda::modalidadeValida()` blocks booking a format the professional doesn't offer (editing the form to pick "Online" with a presencial-only trainer would otherwise only surface on the day of the class).

The choice must survive the **whole payment**: package classes are created after confirmation, in `ClienteController@agendarAulasInterno()`, which reads `booking_data['modalidade']` — so `PaymentController` carries it into `bookingData` on both the PIX and card paths, and `agendarAulasInterno` **revalidates** it (the professional may have changed modalidade between payment and confirmation). Drop it from `booking_data` and the student's choice silently disappears on the paid path.

In the student's modals the question is rendered by `montarEscolhaModalidade()` **only for `Híbrido`** — a professional with a single modality gets an informational line instead, because asking would be noise. The payment buttons are gated until the choice exists (`atualizarBotao()` for the package, `modalidadeAvulsaPendente()` for the single class — the latter guards inside the entry functions rather than toggling `disabled`, so it covers every caller). The personal's dashboard agenda card shows **AULA ONLINE** in brand color, which is the whole point of collecting it.

Pinned by `tests/Feature/ModalidadePersonalTest.php`, `tests/Feature/PreferenciaModalidadeAlunoTest.php` and `tests/Feature/ModalidadeDaAulaTest.php`. When asserting on the vitrine cards, note that the filter pills reuse the same icons — count `<div class="card-meta">` + icon, never the bare icon, or you get a false positive (that bit me twice).

#### Mobile API parity

The app reaches all three levels (it used to reach none): `Api\AuthController@register` takes `modalidade_preferida`, `Api\RegisterController@personal` takes `modalidade`, `Api\PerfilController@update` edits both (the personal's rules allow `Híbrido`, the cliente's don't — the two `Rule::in()` allowlists read the same two configs as the web), and `Api\ExplorarController`'s `agendar`/`pacotes/contratar` accept `modalidade` and refuse an incompatible one with **422**. `personals.modalidade` is in the explore payload, and `pacotesDoPersonal` also ships `modalidades_disponiveis` (`Agenda::modalidadesDisponiveis()`) so the app asks the question only when there is one, without reimplementing the rule.

`PerfilController@show` derives its field list from `array_keys($regras)`, so adding a rule exposes the field in the GET automatically — that's why the new fields appear in both without a second edit.

**`ClienteController@agendarAulaAvulsaInterno()` is the shared door** of the web's **paid** single-class path (`PaymentController`, after confirmation) and the app's booking endpoint. The resolution lives *there*, not only in `reservarHorario()`: when it didn't, the paid avulsa was silently dropping the student's choice on the web too — the API work is what surfaced it. Anything added to one of those two callers must pass `modalidade` in the booking array, and the method revalidates it against the professional's current offer, discarding an incompatible value to `null` with a `Log::warning` rather than writing it.

Pinned by `tests/Feature/ModalidadeApiTest.php`, including the shared-door case.

### Terms of Use — versioning and re-acceptance

`config('termos.versao')` is the **single source of truth** for the current terms version. The six legal blades read it via `@section('doc_versao', config('termos.versao'))` — don't hardcode a version in a view again.

**Bumping `termos.versao` puts every logged-in account behind a wall** on their next page load, until they click accept. So bump it only on a material change, and fill `config('termos.resumo')` with what changed — the re-acceptance screen renders that list, and an acceptance of "something changed" has little evidentiary value.

Acceptance is recorded in `termo_aceites` (polymorphic `usuario`, `versao`, `aceito_em`, `ip`, `user_agent`, `origem` = `cadastro` | `reaceite`), **unique on (usuario_type, usuario_id, versao)** and **append-only**: a new acceptance is a new row, never an UPDATE, so the consent history stays provable (LGPD art. 8º, §1º puts the burden of proving consent on the controller). The legacy columns (`clientes.aceita_termos` + dates, `personals.data_aceicao_termos_atualizacao` — note the typo, it's in the schema) are kept as the record of the original signup acceptance and are **not** rewritten; none of them stored a version, which is why this table exists.

The five account models use `App\Models\Concerns\AceitaTermos`: `aceitouTermos()`, `precisaAceitarTermos()`, `versaoTermosAceita()`, `registrarAceiteTermos()` (idempotent, never throws — failing here would trap the user on the screen). `aceitesTermos()` orders by `aceito_em` **and `id`** desc; the `id` tiebreaker is load-bearing, since two acceptances can land in the same second and `versaoTermosAceita()` would otherwise return the older one.

`App\Http\Middleware\VerificaAceiteTermos` is registered in the **`web` group** (not route by route, so no page is ever forgotten). Three rules keep it from trapping anyone — keep all three if you touch it:
1. **Only GET requests that accept HTML.** POST/PUT/DELETE, AJAX and JSON pass through: a 302 in the middle of a submit or a `fetch` breaks the flow for no benefit, since every session starts with an HTML GET anyway.
2. **`config('termos.rotas_livres')` is exempt** — the acceptance screen itself, logout, login, and all legal documents. Without it the user can neither read what they must accept nor leave: a closed loop.
3. **Admin is never checked** (no account in the five profiles; blocking it would halt operations).

On the way in, the middleware stores the attempted URL in `session('termos_destino')` and `TermoAceiteController::destino()` only honors it if it starts with this host — otherwise a tampered session would turn it into an open redirect.

**Signup already records acceptance** (`ORIGEM_CADASTRO`) in all five cadastro controllers and in `Api\AuthController@register`, right next to `registrarIndicacao` — without that, a brand-new account would hit the wall on its very first page.

**Writing tests that log in via session?** Register an acceptance for the fixture account or every HTML GET returns 302 to `termos.aceite`. `IndicacaoRevenueShareTest` calls `registrarAceiteTermos()` in `setUp`; `VinculoAcademiaPersonalTest` (whose accounts are raw `DB::table` inserts, with no model) has an `aceitarTermos()` helper that inserts the row directly. `tests/Feature/ReaceiteTermosTest.php` pins the gate, the exits, idempotency, the append-only history and the open-redirect guard.

### Security invariants (don't regress these)

An OWASP pass audited the whole app. Most of it held up — `whereRaw`/`selectRaw` all use bindings, uploads have mime allowlists, the password-reset token is sha256-hashed + `hash_equals` + 60-min expiry + single use, `MediaController::exercicioVideo` defends traversal with a strict regex allowlist, Ignition runnable solutions are off, `SecurityHeaders` sets HSTS/nosniff/X-Frame-Options/Referrer-Policy. What follows are the things that were **wrong and got fixed**, written down because none of them break a functional test when reintroduced:

- **Never validate an upload with `image`.** Laravel's `image` rule accepts **svg** (`jpg,jpeg,png,gif,bmp,svg,webp`), SVG is XML and can carry `<script>`, and these files land on the **public** disk served from the app's own origin — so an SVG avatar is stored XSS with session theft. Always use the explicit list the rest of the project uses: `file|mimes:jpeg,jpg,png,gif,webp,heic,heif`. This bit `Api\PerfilController@foto` and `Cadastro\ProgressoController@uploadFoto`.
- **Never compare `fotavel_type` (or any morph type) to a string literal.** `FotoController@destroy` compared against `'App\Models\cadastro\Personal'` and `'...\cadastro\academia'` — lowercase `cadastro`, against the real `App\Models\Cadastro\…`. Those branches never matched, so the legitimate owner got 403 on their own photo. It failed *closed*, but literal-string authz breaks silently on any namespace rename and a future edit could flip it to fail *open*. Derive from `(new $classe)->getMorphClass()`.
- **`RecuperarSenhaController::PERFIS` is the single source of the five profiles.** The list used to be written twice (once to find the user, once in the `match` that writes the new password) and the two drifted: **Studio and Loja had no password recovery at all**. The `match` also had no `default`, so an unexpected `tipo` threw `UnhandledMatchError` (500). Keep both paths reading `self::PERFIS`.
- **`json_encode` inside `<script>` needs `JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP`** whenever the payload can contain user text (exercise names, notes, `old()` input). Without them a `</script>` in the data closes the block and the rest becomes executable HTML. Most of `cliente/index.blade.php` already did this; three sites didn't.
- **Payment/withdrawal routes in `routes/api.php` are deliberately in the `web` group** (they need the session) and are guarded by an in-controller `session()` check returning **401 JSON**. Do **not** "harden" them with `check.login`: that middleware redirects (302 HTML), which would break the AJAX callers. The controller check is the correct layer here.
- The patient-portal token travels in the URL (`p/{token}`), which is why `Referrer-Policy: strict-origin-when-cross-origin` in `SecurityHeaders` is load-bearing, not decoration.

Production hardening lives in `.env.example` under "ENDURECIMENTO PARA PRODUÇÃO": `APP_DEBUG=false`, `SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT`, `CORS_ALLOWED_ORIGINS` (the default is `*`). `tests/Feature/SegurancaOwaspTest.php` pins all of the above.

#### Content-Security-Policy

`config/csp.php` + `ContentSecurityPolicy` middleware (in the `web` group, kept **separate** from `SecurityHeaders` because CSP is the one header that can break a page, so it needs its own kill switch).

**`script-src` keeps `'unsafe-inline'`, and that is a measured decision, not laziness.** This repo has **527 inline event handlers** (`onclick=`, `onchange=`, …) across 54 views, 77 inline `<script>` blocks and 117 inline `<style>` blocks. Nonces fix inline `<script>` blocks but do **nothing** for event-handler attributes — dropping `'unsafe-inline'` means rewriting all 527 handlers as `addEventListener`, which is its own project. What the policy still blocks, and why it's worth shipping anyway: script from any **unknown host** (the usual stored-XSS payload), `<base href>` hijacking of every relative URL, injected forms exfiltrating credentials (`form-action 'self'`), `<object>`/`<embed>`, framing of the site (`frame-ancestors`), and fetch/XHR to hosts outside `connect-src` (how a payload ships stolen data out). Those six assertions are pinned in `CspTest::test_diretivas_restritivas` — **don't loosen them**.

The host allowlist was **derived from this codebase**, not copied from a template: a sweep of views/js/css for external hosts, cross-checked against the rendered HTML of 17 real pages (public + admin + logged-in). A missing host means a silently blocked resource, so change it with evidence. Specifically: `unpkg.com` is leaflet, `server.arcgisonline.com` is the map tiles, `nominatim.openstreetmap.org` geocodes addresses, `viacep.com.br`/`brasilapi.com.br` look up CEP, `ui-avatars.com` renders initials avatars, `connect.facebook.net` is the Pixel. `config('media.url')` (the optional CDN) is injected into `img-src`/`media-src` — without that, switching the CDN on would break all media.

**Rolling it out:** publish with `CSP_REPORT_ONLY=true` first. Browsers then only *report* violations to `POST /csp-report` (`CspReportController` → `security` log channel, throttled, CSRF-excepted because browsers send no token), so you can see what the policy *would* break before it breaks anything. Watch `storage/logs/security-*.log` for `csp_violacao` for a day or two, fix what shows up, then flip to `false`. `CSP_ENABLED=false` turns the header off entirely without a code deploy. The middleware only marks `text/html` responses — JSON, downloads and video streams have no execution context.

With the policy enforced, a `script-src` violation pointing at an unknown host is a **signal of attempted injection**, not a config mistake.

### Frontend

No SPA framework — standard Blade templates with Vite for asset bundling. Views are organized by role: `resources/views/personal/`, `cliente/`, `academia/`, `admin/`, `cadastro/`. Run `npm run dev` for hot-reloading during frontend work.

#### Layouts are NOT interchangeable

There is no single app layout, and the section names differ between them. Extending the wrong one renders a **blank page** with no error:

| Layout | Section to fill |
|---|---|
| `layouts.academia`, `layouts.dashboard` | `@section('content')` |
| `layouts.personal` | **has no `@yield` at all** — not extendable |

Most admin views (`admin/dashboard`, `admin/relatorio_financeiro`, `admin/*/lista`, `admin/*/detalhes`) and several role pages (`login/index`, `cliente/index`) are **standalone documents**, not `@extends` children.

**Default for a new page that must serve more than one role, or any new admin page: write it standalone.** Only `@extends` when the page belongs to a single role whose layout you have verified. Examples of standalone pages added this way: `indicacoes/painel.blade.php`, `admin/indicacoes.blade.php`.

#### Blade directives glued to text are not compiled

Blade only recognizes a directive when it is **not** preceded by a word character. These silently become literal text and unbalance the block:

```blade
Personal@if($p->cref) ... @endif   {{-- @if is literal; the @endif is orphaned --}}
@endif@endif                       {{-- the 2nd @endif is literal --}}
```

Use `@php`/ternary for inline conditions, or put whitespace before the directive. This has already caused a hard syntax error once, in a receipt view.

#### `@php($x = ...)` does not exist in Laravel 10 — always use `@php ... @endphp`

The one-line form was **removed in Laravel 9**, and the failure mode is the worst kind: Blade still rewrites `@php` into `<?php`, so `@php($x = 1)` compiles to **`<?php($x = 1)`** — an opened PHP tag that is never closed. Everything after it in the template stops being markup and is parsed as PHP until the next `?>`, which is whatever `@endphp` happens to come next, **hundreds of lines below**.

So the error surfaces nowhere near the cause. In `cadastro/personal.blade.php` the symptom was `Undefined variable $espPersonal` at line 698 — because the *real* `@php` block at 502 had been swallowed and never ran. The page was a blank 500; the only thing that pointed at the truth was reading the compiled file in `storage/framework/views/` and seeing the literal `@php` sitting next to a bare `?>`.

Two things follow: `@php ... @endphp` always, even for a single assignment; and **when a Blade page 500s with an undefined variable that is plainly assigned above it, go read the compiled view** — the template is lying to you about where the PHP block starts.

#### Shared partials over duplicated markup

Form/field markup repeated across role views drifts fast. Extract a partial and include it:

- `partials/campo-cupom.blade.php` — referral-coupon field, included by **all five** cadastro forms (personal, cliente, academia, studio, loja). It relies on `.form-group` + `.input-wrapper`, which the cadastro views define with the same look; without those classes the input renders as a raw white box.

## Environment Requirements

Key `.env` variables (see `.env.example`):

```
DB_DATABASE=academia          # MySQL database name
TWILIO_ACCOUNT_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_WHATSAPP_FROM=...      # e.g. whatsapp:+14155238886
ADMIN_EMAIL=...               # Seeded admin account
ADMIN_PASSWORD=...
MAIL_MAILER=smtp              # Email via GoDaddy SMTP

META_PIXEL_ID=...             # Meta/Facebook Pixel ID (defaults to the SnrFit pixel)
META_CAPI_TOKEN=...           # Conversions API token; empty = server-side tracking off
META_API_VERSION=v21.0        # Graph API version
META_CAPI_TEST_CODE=...       # Only while debugging in Events Manager > Test Events
```

The `admins` table is seeded from these `.env` values. If the admin can't log in, check that the `admins` table has a row with `email` matching `ADMIN_EMAIL`.

## File Storage

Uploaded files (trainer photos, certificates) use Laravel's `storage/public` disk. The symlink `public/storage → storage/app/public` must exist. Run `php artisan storage:link` if files aren't loading.

