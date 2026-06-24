# Guia de Deploy — SnrFit

> ⚠️ A tabela `migrations` desta produção está **desalinhada**. Por isso, **nunca** rode
> `migrate` "seco" e **jamais** `migrate:fresh/refresh/reset`. Siga os passos abaixo.

## 0. Backup (sempre, antes de tudo)

```bash
mysqldump -u root -p academia > backup_antes_deploy.sql
```

## 1. Subir o código

```bash
git pull origin main
```

O código novo (controllers, views, middleware, rotas) **não apaga dados** — pode subir tranquilo.

## 2. Rodar SOMENTE as migrations novas, uma a uma, em ordem

As migrations abaixo apenas **criam tabelas novas** e **adicionam colunas** — nenhuma
toca em dados existentes. Rode individualmente com `--path=` para evitar o erro de
"table already exists" causado pela tabela `migrations` desalinhada.

```bash
php artisan migrate --path=database/migrations/2026_06_23_000001_create_registros_exercicio_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000002_create_mesociclos_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000003_create_mesociclo_treinos_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000004_create_mesociclo_exercicios_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000005_create_anamneses_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000006_add_feedback_to_treinos_concluidos.php --force
php artisan migrate --path=database/migrations/2026_06_23_000007_create_medidas_corporais_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000008_create_fotos_progresso_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000009_create_metas_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000010_create_ficha_templates_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000011_create_notificacoes_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000012_create_mensagens_table.php --force
```

> Se alguma acusar "table already exists", significa que ela já foi aplicada — pule para a próxima.

## 3. Limpar caches e garantir o symlink de storage

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan storage:link   # só se public/storage ainda não existir (necessário para fotos)
```

## ❌ NUNCA rode em produção

```bash
php artisan migrate:fresh     # APAGA TUDO e recria
php artisan migrate:refresh   # rollback + remigra (perde dados)
php artisan migrate:reset     # dropa todas as tabelas
php artisan db:seed --class=TesteSnrFitSeeder   # apaga/recria contas de TESTE
```

## Checklist pós-deploy

- [ ] Backup feito antes
- [ ] `git pull` na branch `main`
- [ ] As 12 migrations rodaram (ou já existiam)
- [ ] Caches limpos
- [ ] `public/storage` existe (fotos carregam)
- [ ] Ajustar no `.env` de produção (segurança):
  - [ ] `SESSION_SECURE_COOKIE=true` (requer HTTPS)
  - [ ] `APP_DEBUG=false`
  - [ ] `MAIL_*` apontando para o SMTP real (envio de e-mails/relatórios)
- [ ] Atualizar o e-mail do DPO na Política de Privacidade (`resources/views/lgpd/politica.blade.php`)
