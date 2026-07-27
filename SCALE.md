# Escalabilidade — SNR FIT

Guia para o app aguentar muitos usuários simultâneos. Marcado com ✅ o que já
está resolvido no código; 👉 o que depende de infraestrutura (você provisiona).

## Diagnóstico (teste de carga real)

Medido contra a API em `php artisan serve` (servidor de **desenvolvimento**,
single-thread):

| Cenário | Concorrência | Throughput | Latência p50 |
|---|---|---|---|
| `GET /me` | 20 | ~6,4 req/s | 3,1 s |
| `GET /me` | 50 | ~6,4 req/s | 7,7 s |
| Vídeo (streaming) | 15 | ~7 req/s | 2,1 s |
| Vídeo (streaming) | 40 | ~7 req/s | 4,3 s (estourou o limite) |

**Leitura:** o throughput trava em ~6–7 req/s por mais que a concorrência suba —
assinatura de servidor single-thread. Não representa produção. Os gargalos reais
para escala são, em ordem de impacto:

1. 🔴 **Vídeo servido pelo PHP** — cada stream prendia um worker do backend.
2. 🟠 **Servidor de app de desenvolvimento** — sem workers/paralelismo.
3. 🟡 **Listas "explorar" sem paginação/busca no servidor.**

Índices de banco: ✅ **já adequados** — as tabelas quentes (`registros_exercicio`,
`treinos_concluidos`, `fichas_treino`, `metas`, `mensagens`, `notificacoes`) já
têm índices nas colunas de busca (inclusive o composto
`registros_exercicio(cliente_id, nome_exercicio, data_treino)`). Nada a fazer aqui.

---

## 1. Mídia via CDN — ✅ pronto no código (o de maior impacto)

O código já resolve as URLs de vídeo/imagem por uma base configurável. Basta
apontar para um CDN e a mídia deixa de passar pelo PHP.

- ✅ `config/media.php` + helper `App\Support\Media::videoUrl()` — vídeos usam o
  CDN quando `MEDIA_URL` está definido; senão, caem no streaming do backend (dev).
- ✅ `config/filesystems.php` — o disco `public` usa `MEDIA_URL` como host, então
  `Storage::url()` (imagens, fotos, logos) também sai do CDN.

**Passos (você):**

1. Crie um bucket/CDN que sirva o conteúdo de `storage/app/public` **na raiz**.
   Opções: Cloudflare R2, Bunny.net, AWS S3 + CloudFront, DigitalOcean Spaces.
2. Sincronize os arquivos (e mantenha em sincronia a cada upload):
   ```bash
   # exemplo com aws-cli (S3/R2/Spaces compatível)
   aws s3 sync storage/app/public s3://SEU-BUCKET --acl public-read
   ```
   > Para produção “de verdade”, troque o disco `public` por um driver `s3` e faça
   > o upload ir direto pro bucket (sem sync manual). O helper de URL já funciona
   > com ambos.
3. No `.env`:
   ```
   MEDIA_URL=https://cdn.snrfit.com.br
   ```
4. `php artisan config:clear` (ou `config:cache`).

Verificação: um `video_url` retornado pela API deve começar com `https://cdn...`
em vez de `.../api/v1/media/exercicio-video/...`.

---

## 2. Servidor de aplicação — 👉 nginx + PHP-FPM

Nunca use `php artisan serve` em produção. Use PHP-FPM com vários workers.

`/etc/nginx/sites-available/snrfit`:
```nginx
server {
    listen 443 ssl http2;
    server_name api.snrfit.com.br;
    root /var/www/academia3/public;
    index index.php;

    client_max_body_size 20M;         # uploads de foto/vídeo

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60s;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

PHP-FPM (`/etc/php/8.2/fpm/pool.d/www.conf`) — dimensione pelos núcleos/RAM:
```
pm = dynamic
pm.max_children = 40      # ~ (RAM disponível / ~40MB por processo)
pm.start_servers = 8
pm.min_spare_servers = 6
pm.max_spare_servers = 12
```

Em produção, rode uma vez:
```bash
php artisan config:cache && php artisan route:cache && php artisan event:cache
composer install --no-dev --optimize-autoloader
php artisan storage:link
php artisan migrate --force
```

---

## 3. Redis — 👉 cache, sessão, fila e rate-limit

`.env` de produção:
```
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```
Isso tira sessão/cache do disco e permite **rodar vários servidores** de app com
o rate-limit e a sessão compartilhados. Suba um worker de fila para tarefas
pesadas (e-mail, webhooks Asaas, notificações):
```bash
php artisan queue:work redis --tries=3 --timeout=90
# em produção use supervisor/systemd para manter vivo
```

---

## 4. Paginação + busca no servidor da tela "Serviços" (explorar) — 👉 recomendado

Hoje `GET /explorar/{personais|academias|studios|lojas}` retorna a **lista
inteira**, e o app faz busca/filtro (nome, cidade, bairro) no cliente. Funciona
bem com poucos cadastros; com dezenas de milhares de profissionais, o payload e a
consulta crescem.

Como a busca é client-side, **paginar exige mover a busca para o servidor** — é
uma mudança de UX (não dá pra montar os chips de cidade/bairro a partir do dataset
completo). Plano sugerido:

- Backend: aceitar `?q=&cidade=&uf=&limit=&offset=` (ou cursor) e paginar.
- App: rolagem infinita ("carregar mais") + busca com debounce que consulta o
  servidor; filtros de cidade/UF viram lista fixa (endpoint de facetas) em vez de
  derivada da lista.

Enquanto a base for pequena (até alguns milhares), não é urgente — os índices já
existem e a consulta é barata.

---

## 5. Validar com um teste de carga de verdade — 👉 k6

Depois de subir a infra acima, rode um teste **contra o ambiente de produção**
(não contra `artisan serve`). Exemplo com [k6](https://k6.io):

```js
// load.js
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE = 'https://api.snrfit.com.br';
const TOKEN = __ENV.TOKEN;              // token de um usuário de teste

export const options = {
  scenarios: {
    ramp: { executor: 'ramping-vus', startVUs: 0,
      stages: [
        { duration: '1m', target: 500 },
        { duration: '3m', target: 2000 },
        { duration: '2m', target: 0 },
      ] },
  },
  thresholds: { http_req_duration: ['p(95)<800'] },  // p95 < 800ms
};

export default function () {
  const h = { headers: { Authorization: `Bearer ${TOKEN}`, Accept: 'application/json' } };
  check(http.get(`${BASE}/api/v1/desempenho`, h), { '200': (r) => r.status === 200 });
  sleep(1);  // usuários reais têm pausa entre ações
}
```
```bash
TOKEN=xxxx k6 run load.js
```
> Dica: 10 mil usuários **simultâneos** geram muito menos que 10 mil req/s (há
> pausa entre ações). Meça req/s reais e escale horizontalmente (mais instâncias
> de app atrás de um load balancer) até bater a meta de p95.

---

## Resumo do que priorizar

1. **CDN (`MEDIA_URL`)** — já pronto no código; só apontar. Maior impacto no vídeo.
2. **nginx + PHP-FPM** — sai do single-thread.
3. **Redis** — cache/sessão/fila e permite escalar horizontalmente.
4. **Paginação server-side do explorar** — quando a base de cadastros crescer.
5. **k6 contra produção** — para validar os números de verdade.
