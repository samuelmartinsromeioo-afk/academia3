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

### Revenue Model — 90/10 everywhere

**There is no SaaS subscription.** No role ever pays a monthly fee to use the platform. The single revenue model is a **marketplace split on every transaction: 90% to the professional/business, 10% platform commission.** This holds for *all* of them — personal trainer (package + single session), nutritionist (consultation paid by a platform client **and** billing their own patients), academia, studio, loja.

The rate lives in exactly two places: `AsaasService::SPLIT_RATE` (0.90, what is sent to Asaas) and the `$feeRate = 0.10` default of `AsaasService::calculateSplit()` (what is recorded in `payments.company_fee`/`trainer_amount`). **Don't introduce a third.** Every charge is created in the *platform* Asaas account with a `split` array pointing at the receiver's `asaas_wallet_id`; `montarSplit()` picks `fixedValue` vs `percentualValue` so Asaas won't reject the split when the card fee eats the net.

Note `academias.valor_mensalidade` is the price the **gym charges its students**, not something the gym pays us — it flows through the same split.

Known gap, deliberate: when a receiver has no `asaas_wallet_id`, `montarSplit()` returns `null` and the charge still goes through **without a split**, so 100% lands in the platform account and the payout is manual (`personal_saques`). Only the nutri flows fail closed instead. If you change this, change it everywhere.

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

### Professional Types & Nutrition Module

The `personals` table is no longer personal-trainer-only. A `professional_type` discriminator column (`App\Enums\ProfessionalType`: `PERSONAL_TRAINER` | `NUTRITIONIST`, default backfilled to `PERSONAL_TRAINER`) opens it to other professions **additively** — the personal-trainer flow is unchanged. `Personal::isNutricionista()` / `isPersonalTrainer()` / `registroConselho()` branch behavior; `cref` is now nullable and a `crn` column was added, validated conditionally in `Cadastro\PersonalController@store` (`CadastroHelper::validarCRN()` — regex + region 1–11). The cadastro form (`cadastro/personal.blade.php`) has a **step-1 type selector** with JS-driven conditional fields (CREF↔CRN, especialidades chips, modalidade, bio). On login, an approved nutritionist is redirected to `nutri.painel` instead of `personal.dashboard`.

Editable UI/marketing strings (labels, especialidades, "diferencial" copy) live in `config/textos.php` — run `php artisan config:clear` after edits.

### Tela de "cadastro recebido"

Every signup that waits for admin approval (Personal, Nutricionista, Academia, Studio, Loja) redirects to `cadastro.sucesso` with `->with('cad_tipo', ...)`, instead of dropping a flash message on the login page. `resources/views/cadastro/sucesso.blade.php` is a thin renderer: the whole welcome message (title, paragraphs, three highlight cards) comes from `config('textos.boas_vindas.{cad_tipo}')`, falling back to `padrao` when the flash is gone (F5 on the page). Paragraphs are printed with `{!! !!}` so `<strong>` survives — it is our copy from config, never user input, so nothing from a form may be interpolated there. The copy must not mention the 90/10 split, a contract, or a monthly fee; there is none (see *Revenue Model*). Cliente signup doesn't need approval and still goes straight to login.

**Client-facing discovery**: since personals and nutritionists share the `personals` table, the model has `scopePersonalTrainers()` (tipo nulo conta como PT) and `scopeNutricionistas()`. The client explore page (`ClienteController@listarPersonais` → `cliente/personais.blade.php`) shows both under **"Explorar Profissionais"** with two tabs (Personais / Nutricionistas; `?tipo=nutricionistas` deep-links to the nutri tab). The nutritionist has a client-facing profile (`nutricionistas/{id}/detalhes` → `detalheNutricionista` → `cliente/nutri-detalhes.blade.php`) with bio, especialidades, CRN, avaliações, a **"Falar no WhatsApp"** button and, when `personals.valor_consulta` is set, a **"Pagar consulta"** button. **Consultation payment**: the nutri sets `valor_consulta` in the Financeiro page (`CobrancaController@salvarConfig`); the client pays via `nutricionistas/{id}/pagar-consulta` (`ClienteController@pagarConsultaNutri`), which creates a `nutri_cobrancas` row (`cliente_id` set, `paciente_id` null) and a **marketplace charge in the platform Asaas account with a 90/10 split** to the nutri's `asaas_wallet_id` (`AsaasService::criarCobrancaAvulsaComSplit` + `splitPersonal(...,'CREDIT_CARD')` — same 90% pro / 10% platform fee as personal/academia; billingType UNDEFINED → Pix/card/boleto), then redirects to the Asaas `invoiceUrl`. Requires the nutri to have `asaas_wallet_id`; without it the charge is rolled back and the client is told to use WhatsApp. The nutri→patient billing in `CobrancaController@store` (`Cobranca::gerarLinkAsaas()`) is on the **same 90/10**: the Payment Link is created in the *platform* account with a split to the nutri's `asaas_wallet_id`, not in the nutri's own subaccount. Without a wallet no link is generated and the charge stays as manual control. `AsaasWebhookController@confirmarCobrancaNutri` auto-marks the cobrança paid (matched by `externalReference` `nutri_cobranca:{id}` or the payment-link id) and fires a server-side `Purchase` (dedup id `nutri_cobranca_{id}`). Nutris can still mark charges paid manually (`marcarPago`). The client dashboard (`ClienteController@index`) filters `$personals` with `personalTrainers()` so nutritionists don't show up as bookable trainers.

The **nutrition module** lives under `App\...\Nutri\` (controllers, models, `Services\Nutri\PlanoAlimentarService`) with all tables prefixed `nutri_`. Patients (`nutri_pacientes`) are a distinct entity owned by the nutritionist (**not** a platform `Cliente`). Access is gated by the `check.nutri` middleware (session `personal_id` + `isNutricionista()` + approved); the trait `Nutri\Concerns\ResolveNutri` resolves the nutritionist and enforces per-owner access. Routes are grouped under prefix `nutri`/name `nutri.`. Covers: patient management, customizable anamnese (`nutri_anamnese_modelos` + responses), anthropometry (with Chart.js evolution), meal-plan editor (autosave + `nutri_plano_versoes` versioning + macro totals; food base seeded from TACO via `NutriAlimentosSeeder`, `verificado` = official), per-item **substitution options** (separate `nutri_plano_substituicoes` table — each `nutri_plano_itens` row has N equivalents the patient may swap to, created together in the same editor save via `PlanoItem::opcoes()`; also in the snapshot for versioning/restore, and shown in the portal/PDF), agenda (`.ics` + Google Calendar link), Asaas billing links, chat, roadmap/voting, CSV/print exports (portability/LGPD). A tokenized **patient portal** (`p/{token}`, no login — `PortalController`) exposes plano, shopping list, food diary, check-ins, chat and a pre-consultation questionnaire.

**Multiple fichas per patient / per weekday**: a patient can have more than one active plan at once — activating one no longer deactivates the others (`ativar`/`desativar`). Each `nutri_planos` row carries `dias_semana` (JSON array of `Carbon::dayOfWeek` ints, 0=Sun…6=Sat; empty/null = every day), edited via the weekday pills in the plan editor header. `Paciente::planosAtivos()` returns all active fichas; `Paciente::escolherPlanoDoDia($ativos, $dia)` picks the day's ficha (a day-specific one wins over an "every day" one), and `planoDoDia()`/`planoAtivo()` (today's) wrap it. The plan editor has a **ficha navigator** (`PlanoAlimentarController::fichasIrmas()` → prev/next + day chips) to flip through a patient's fichas without leaving the editor. The **assisted generation** (`gerarIA`) has a *"uma ficha por dia"* mode: with `por_dia` + `dias_semana[]` it creates/reuses one ficha per selected day (varied menu via a per-day rotation seed), then redirects to the patient page; an empty origin ficha is cleaned up. Each generated item also gets **auto substitutions** (2 equivalents from the same role pool, matched to the same kcal). The **food budget is the patient's** (`nutri_pacientes.orcamento_mensal`, set in the patient form / IA form and persisted): the assisted generation divides it across the month — daily target = `orcamento_mensal ÷ days-in-month`, and each ficha's monthly cota = daily × how many times its weekday(s) fall in the month (`ocorrenciasNoMes()`), so the fichas' cotas sum to the stipulated budget (1 ficha = full budget; N fichas split it). The success message reports the cota vs estimated cost per ficha/day. The patient portal (`PortalController@plano`, `?dia=`) shows a weekday tab bar and renders that day's ficha; the shopping list aggregates all active fichas. Day labels come from `PlanoAlimentar::diasSemanaLabels()`.

**Absent patients**: `Paciente::estaAusente()` / `diasSemRetorno()` / `ultimaInteracaoEm()` flag patients with no "retorno" in over a month (`Paciente::DIAS_AUSENCIA` = 30). "Retorno" = latest of a concluded consulta, a check-in, an anthropometry, or (fallback) the registration date. The `scopeAusentes()` query filter (SQL `whereDoesntHave` on all three, paginatable) and `scopeComUltimaInteracao()` (withMax pre-aggregation to avoid N+1) power the painel "Pacientes ausentes" card, the pacientes-list *Situação* filter/badge, and the ficha banner.

### Referral Coupons ("Indique e ganhe")

Every account (Personal/Nutri, Cliente, Academia, Studio, Loja) gets a personal referral code and can enter someone else's at signup. Tracking + bonus only — it does **not** change any amount charged.

**The bonus is revenue share, not a fixed amount.** The referrer earns `config('indicacao.percentual')` (**10%**) of everything the **referred** account bills on the platform during a window of `config('indicacao.janela_dias')` (**35 days**) counted from the referred account's **approval**. The accrued amount can only be **withdrawn** after that window closes — and only if the referred account also has `config('indicacao.meta_alunos')` (6) students won through the platform. There is **no fixed R$ 30 bonus** any more; `cupons.bonus_valor` stays 0 on `indicacao` coupons (it still carries the fixed value of admin `promocional` campaigns).

**The base is the referred account's GROSS revenue** (`payments.amount_total`) — the full amount their students paid through the platform, before any split. It is **not** what the professional takes home, and **not** the platform's commission. On a referred account that generates R$ 2.500 inside the window:

| | |
|---|---|
| Gross generated by the referred account | **R$ 2.500,00** ← the base |
| Goes to the professional (90% split) | R$ 2.250,00 |
| Platform commission (10%) | R$ 250,00 |
| Referrer's bonus (10% of gross) | R$ 250,00 |

**Those last two R$ 250 are the same money, not two amounts.** With `percentual` equal to the split's 10%, the platform collects R$ 250 and hands R$ 250 to the referrer — **net zero** on that account for the 35 days. It does not keep R$ 250 *and* pay another R$ 250; for that, `percentual` would have to be below 0.10 (0.05 would leave R$ 125 on each side). Deliberate acquisition decision — the cost of bringing the account in is the whole margin of its first 35 days, and after the window the commission comes back in full. `config('indicacao.percentual')` is the one number to change, and changing it moves the platform's remainder by the same amount.

That arithmetic is spelled out in three places that must stay in sync: the header comment of `config/indicacao.php`, the "Sobre qual valor incidem os 10%" block on `/indicacoes`, and clause **8.5** of the terms. The panel and the terms both compute the worked example from `config('indicacao.percentual')` × `config('indicacao.exemplo_base')` instead of hardcoding R$ 250 — a hardcoded example would quietly start lying the day the percentage changes.

Six rules that are easy to get wrong:

1. **The window is stamped on `cupom_usos`, not derived on read** — `CupomService::iniciarJanela()` writes `janela_inicio` (= the referred account's `data_aprovacao`) and `janela_fim` **only when `janela_inicio` is null**. That guard is load-bearing: `data_aprovacao` is rewritten by reactivation flows (`AdminController::reativarLoja`), so deriving the window from it on every read would hand out 35 fresh days every time an account is blocked and reactivated.
2. **Apuração is a scan, not a hook** — `CupomService::apurar()` sweeps `payments` (plus `nutri_cobrancas` for nutritionists) inside the window and inserts the missing credits. Revenue lands through many paths (`pagarSucesso`, the Asaas webhook, subscription renewal, nutri consultations) and a forgotten hook in any one of them would silently lose someone's money. The scan is idempotent by construction, self-heals, and stays off the payment hot path. **Don't replace it with a hook in `handleSuccessfulPayment`.**
3. **`indicacao_creditos.origem` is the idempotency key** — unique in the DB (`payment:123`, `nutri_cobranca:45`). It is what stops a webhook retry or a re-apuração from paying twice. The table is **append-only**: never UPDATE a credit; a correction is a new row.
4. **What counts as a student** — `TemCupomIndicacao::alunosPelaPlataforma()` counts distinct clients with a *confirmed payment* (`Cupom::STATUS_PAGAMENTO_VALIDO`) in `payments`/`subscriptions` for that receiver (`trainer_id`/`academia_id`/`studio_id`/`loja_id`), plus paid `nutri_cobrancas` for nutritionists. It deliberately does **not** count hand-made links (`clientes.academia_id`, nutri patients, unpaid agenda rows) — those are free to create, so counting them would let anyone link 6 friends and unlock the withdrawal. If you extend this, keep the "money actually moved" bar.
5. **Referring a student earns nothing** — a `Cliente` has no students, so the use is stored as `sem_bonus` with `bonus_valor = 0` (history only). Otherwise creating fake student accounts would be a cheap farm.
6. **Release is sticky** — `CupomService::reavaliar()` only moves `pendente → liberado` and stamps `liberado_em`. A referred account that later loses students does **not** re-lock the bonus; the referrer doesn't control the other party's churn, and a balance that disappears from the panel is worse than a strict rule.

`cupom_usos.bonus_valor` is the **running total** of that referral's credits (it grows while the window is open and freezes when it closes), not a snapshot of a fixed amount. `apurado_em` keeps the cron cheap: once the window has closed and been apurado after `janela_fim`, `apurar()` short-circuits.

**Known gap, deliberate:** a refund after a credit was written does not claw the credit back (`refunded` is excluded from `STATUS_PAGAMENTO_VALIDO`, so it never *creates* a credit, but an already-credited payment later refunded stays credited). Reversing that would mean clawing back money possibly already paid out; for now the admin's lever is refusing the saque.

Re-evaluation (window + apuração + release) runs when the referrer opens `/indicacoes`, and daily at 04:00 via `php artisan indicacoes:reavaliar` (registered in `Console\Kernel`). **The scheduled run is not optional** — without it a bonus only unlocks the next time the referrer happens to log in.

Four tables: `cupons` (`codigo` unique, `tipo` = `indicacao` | `promocional`, polymorphic `dono` — null on admin campaigns, `bonus_valor`, `ativo`, `expira_em`, `limite_usos`, `usos`); `cupom_usos` (polymorphic `usuario`, `bonus_valor` accrued, `status` = `pendente` | `liberado` | `sem_bonus` | `cancelado`, `janela_inicio`, `janela_fim`, `apurado_em`, `liberado_em`, `saque_id`, `ip`; **unique on `usuario_type` + `usuario_id`** so an account can only be referred once); `indicacao_creditos` (the ledger: `cupom_uso_id`, `origem` unique, `base_valor`, `percentual`, `valor`, `ocorreu_em`); `indicacao_saques` (withdrawal requests: polymorphic `usuario`, `valor`, `status` = `solicitado` | `processando` | `pago` | `recusado` | `falhou`, `metodo` = `automatico` | `manual`, `pix_chave` **encrypted** + `pix_tipo`, `asaas_transfer_id` **unique**, `asaas_status`, `transferencia_em`, `receipt_url`, `falha_motivo`, `admin_id`, `processado_em`, `ip`). Models `App\Models\{Cupom, CupomUso, IndicacaoCredito, IndicacaoSaque}`; the five account models use the `App\Models\Concerns\TemCupomIndicacao` trait (`cupomIndicacao()`, `indicacaoRecebida()`, `indicacoesFeitas()`, `codigoIndicacao()`, `saldoDisponivel()`, `bonusEmSaque()`, `bonusSacado()`, `bonusPendente()`, `totalIndicacoes()`).

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
- `type: PAYMENT_SPLIT` → **APPROVED**. Splits are the 90/10 core and are defined by us at charge creation; refusing here would halt every payout on the platform. Don't "harden" this into a refusal.
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

**Both fields must stay wired to something.** `personals.modalidade` spent months as write-only data — collected at signup, never editable, never displayed except on the nutri profile. That's the failure mode to avoid: it's now editable in the personal's dashboard and shown on the student's card/detail, and `clientes.modalidade_preferida` pre-applies the vitrine filter in `ClienteController@listarPersonais`. If you remove the read, you recreate the dead field.

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
| `layouts.nutri` | `@section('conteudo')` (+ `estilos`, `scripts`) |
| `layouts.academia`, `layouts.dashboard` | `@section('content')` |
| `layouts.personal` | **has no `@yield` at all** — not extendable |

Most admin views (`admin/dashboard`, `admin/relatorio_financeiro`, `admin/*/lista`, `admin/*/detalhes`) and several role pages (`login/index`, `cliente/index`, the patient portal) are **standalone documents**, not `@extends` children.

**Default for a new page that must serve more than one role, or any new admin page: write it standalone.** Only `@extends` when the page belongs to a single role whose layout you have verified. Examples of standalone pages added this way: `indicacoes/painel.blade.php`, `admin/indicacoes.blade.php`, `nutri/financeiro/recibo.blade.php`.

#### Blade directives glued to text are not compiled

Blade only recognizes a directive when it is **not** preceded by a word character. These silently become literal text and unbalance the block:

```blade
Nutricionista@if($nutri->crn) ... @endif   {{-- @if is literal; the @endif is orphaned --}}
@endif@endif                               {{-- the 2nd @endif is literal --}}
```

Use `@php`/ternary for inline conditions, or put whitespace before the directive. This caused a hard syntax error in the recibo view.

#### Shared partials over duplicated markup

Form/field markup repeated across role views drifts fast. Extract a partial and include it:

- `partials/campo-cupom.blade.php` — referral-coupon field, included by **all five** cadastro forms (personal, cliente, academia, studio, loja). It relies on `.form-group` + `.input-wrapper`, which the cadastro views define with the same look; without those classes the input renders as a raw white box.
- `nutri/anamnese/_campo.blade.php` — renders one anamnese field (8 types), shared by the professional's form and the patient portal, so a new field type is written once.

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

