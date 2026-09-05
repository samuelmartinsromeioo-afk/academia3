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

The **nutrition module** lives under `App\...\Nutri\` (controllers, models, `Services\Nutri\PlanoAlimentarService`) with all tables prefixed `nutri_`. Patients (`nutri_pacientes`) are a distinct entity owned by the nutritionist (**not** a platform `Cliente`). Access is gated by the `check.nutri` middleware (session `personal_id` + `isNutricionista()` + approved); the trait `Nutri\Concerns\ResolveNutri` resolves the nutritionist and enforces per-owner access. Routes are grouped under prefix `nutri`/name `nutri.`. Covers: patient management, customizable anamnese (`nutri_anamnese_modelos` + responses), anthropometry (with Chart.js evolution), meal-plan editor (autosave + `nutri_plano_versoes` versioning + macro totals; food base seeded from TACO via `NutriAlimentosSeeder`, `verificado` = official), per-item **substitution options** (separate `nutri_plano_substituicoes` table — each `nutri_plano_itens` row has N equivalents the patient may swap to, created together in the same editor save via `PlanoItem::opcoes()`; also in the snapshot for versioning/restore, and shown in the portal/PDF), agenda (`.ics` + Google Calendar link), Asaas billing links, chat, roadmap/voting, CSV/print exports (portability/LGPD). A tokenized **patient portal** (`p/{token}`, no login — `PortalController`) exposes plano, shopping list, food diary, check-ins, chat and a pre-consultation questionnaire.

**Multiple fichas per patient / per weekday**: a patient can have more than one active plan at once — activating one no longer deactivates the others (`ativar`/`desativar`). Each `nutri_planos` row carries `dias_semana` (JSON array of `Carbon::dayOfWeek` ints, 0=Sun…6=Sat; empty/null = every day), edited via the weekday pills in the plan editor header. `Paciente::planosAtivos()` returns all active fichas; `Paciente::escolherPlanoDoDia($ativos, $dia)` picks the day's ficha (a day-specific one wins over an "every day" one), and `planoDoDia()`/`planoAtivo()` (today's) wrap it. The plan editor has a **ficha navigator** (`PlanoAlimentarController::fichasIrmas()` → prev/next + day chips) to flip through a patient's fichas without leaving the editor. The **assisted generation** (`gerarIA`) has a *"uma ficha por dia"* mode: with `por_dia` + `dias_semana[]` it creates/reuses one ficha per selected day (varied menu via a per-day rotation seed) and **splits the budget across the days** (`orcamento_mensal ÷ nº de dias`), then redirects to the patient page; an empty origin ficha is cleaned up. The patient portal (`PortalController@plano`, `?dia=`) shows a weekday tab bar and renders that day's ficha; the shopping list aggregates all active fichas. Day labels come from `PlanoAlimentar::diasSemanaLabels()`.

**Absent patients**: `Paciente::estaAusente()` / `diasSemRetorno()` / `ultimaInteracaoEm()` flag patients with no "retorno" in over a month (`Paciente::DIAS_AUSENCIA` = 30). "Retorno" = latest of a concluded consulta, a check-in, an anthropometry, or (fallback) the registration date. The `scopeAusentes()` query filter (SQL `whereDoesntHave` on all three, paginatable) and `scopeComUltimaInteracao()` (withMax pre-aggregation to avoid N+1) power the painel "Pacientes ausentes" card, the pacientes-list *Situação* filter/badge, and the ficha banner.

### Frontend

No SPA framework — standard Blade templates with Vite for asset bundling. Views are organized by role: `resources/views/personal/`, `cliente/`, `academia/`, `admin/`, `cadastro/`. Run `npm run dev` for hot-reloading during frontend work.

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

