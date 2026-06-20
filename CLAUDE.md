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
```

The `admins` table is seeded from these `.env` values. If the admin can't log in, check that the `admins` table has a row with `email` matching `ADMIN_EMAIL`.

## File Storage

Uploaded files (trainer photos, certificates) use Laravel's `storage/public` disk. The symlink `public/storage → storage/app/public` must exist. Run `php artisan storage:link` if files aren't loading.

