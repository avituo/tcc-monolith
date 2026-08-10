# TCC experiment protocol

## Evidence boundary

This protocol records only facts supported by the seven audited repositories. It does not claim that a benchmark has been run.

- **Verified** means directly supported by tracked source, lock files, migrations, or container definitions.
- **Unresolved** means the repositories do not determine the value or procedure.
- **Manual record** identifies information that must be captured with each experiment run.

The audited repositories are siblings under `/Users/devnit/Documents/projects`.

| Repository                   | Branch   | Audited commit                             | Pre-existing working-tree state                  |
|------------------------------|----------|--------------------------------------------|--------------------------------------------------|
| `tcc-monolith`               | `main`   | `937b0352243987b738a7274433b90ae0c3283fc7` | modified `.codex/config.toml`                    |
| `api-gateway-core`           | `master` | `613aa59137b386c3264f7bbdc745cf22f14433c7` | untracked `.DS_Store`                            |
| `tcc-auth-service`           | `master` | `df23d58ecb03e82f648b92e90333e8a81fdb69db` | clean                                            |
| `tcc-product-service`        | `master` | `bd152f8feefba7f2016df39517404994744f7e56` | clean                                            |
| `tcc-order-service`          | `master` | `767c5d4f2d929a8eb296c4e61ee7debd81c47ea8` | clean                                            |
| `tcc-container`              | `master` | `3ace4b451e060083063ee5b4f2367d700f451e71` | untracked `.DS_Store`                            |
| `tcc-frontend-microservices` | `master` | `00e2158cda68fb45ae317e0fc83fbcd77240d07b` | two modified source files and one untracked test |

The frontend changes are `src/api/orders.ts`, `src/views/orders/OrderShowView.vue`, and `src/api/__tests__/orders.test.ts`. A commit ID alone does not describe that working tree. Record fresh revisions, status, and patches for the actual experiment.

## 1. Exact monolith architecture

**Verified.** `tcc-monolith` is one Laravel application containing authentication, commerce, persistence, and the Inertia/Vue UI.

```text
Browser or HTTP client
        |
        v
Laravel CLI development server
        |
        +-- web routes -> Fortify + Inertia -> Vue pages/assets
        |
        +-- /api/v1 -> API controllers -> ProductService / OrderService
                                             |
                                             v
                          one configured database containing
                          users, products, orders, order_product,
                          sessions, cache, queues, and passkeys
```

- `routes/web.php` serves Inertia product/order pages under `auth` and `verified`.
- `routes/api.php` serves JSON commerce endpoints under `auth` and `throttle:api`.
- Product and order operations execute in one process. Order writes update stock in the same database using transactions and row locks.
- Fortify uses the `web` session guard. The example session, cache, and queue stores are database-backed.
- Vue assets are built by Vite and served through Laravel/Inertia; there is no separately deployed monolith frontend.
- `composer run dev` also starts a database queue listener, Pail, and Vite, although no queued commerce work was found.
- **Unresolved:** no monolith Dockerfile or Compose file is committed. OS, PHP patch/extensions, server concurrency, resource limits, and database server are not fixed.

## 2. Exact microservices architecture

**Verified topology:** `tcc-container/docker-compose.yml` defines six services on one bridge network.

```text
Browser -> Vue/Vite :5173 -> Spring Cloud Gateway :8080
                              |          |          |
                              v          v          v
                         Auth :8003 Product :8001 Order :8002
                                         ^          |
                                         +----------+
                                         synchronous HTTP
```

- The Vue SPA proxies `/api` to `http://api-gateway-core:8080` by default and stores bearer tokens in browser storage.
- The reactive Spring Cloud Gateway validates HS256 JWTs, removes spoofable identity headers, injects trusted `X-User-*` plus `X-Gateway-Key`, and routes by path.
- Auth owns users and JWT issuance. Product owns products and stock reservations. Order owns orders/items and synchronously calls product for snapshots and reservation create/replace/release/confirm operations.
- There is no broker, event bus, service registry, distributed cache, or load balancer.
- `laravel-base` is a build-helper image/service and is not on the request path.
- Compose defines `microservices-net` with the `bridge` driver. Its generated Docker name depends on the Compose project name.
- Ports 8001, 8002, and 8003 are published directly, but an architecture benchmark must enter commerce traffic through gateway port 8080.
- Compose passes `DB_HOST`, `DB_PORT`, credentials, and three database names, but defines no database container and does not pass `DB_CONNECTION`. Each Laravel `.env.example` defaults to SQLite. Local ignored `.env` files can be copied by the Dockerfiles because no `.dockerignore` exists. Therefore the effective database driver is **unresolved from tracked files**.
- Order creation makes two sequential product calls (snapshot then reserve). Update makes snapshot then replace. Delete/cancel releases; paid confirms.

## 3. Technology and dependency versions

Lock files are authoritative for Composer and npm. Principal locked versions are below; archive full dependency listings for each run.

### Monolith

| Technology                                   | Locked/declarative version                |
|----------------------------------------------|-------------------------------------------|
| PHP                                          | constraint `^8.3`; runtime patch unpinned |
| Laravel                                      | `13.14.0`                                 |
| Inertia Laravel / Vue adapter / Vite adapter | `3.1.0` / `3.4.0` / `3.4.0`               |
| Fortify / Wayfinder                          | `1.37.2` / `0.1.20`                       |
| Vue / Vite / Tailwind CSS                    | `3.5.38` / `8.0.16` / `4.3.1`             |
| Axios                                        | `1.17.0`                                  |
| PHPUnit                                      | `12.5.29`                                 |
| PHPMetrics / PHP Insights                    | `2.9.1` / `2.14.2`                        |

### Laravel services

| Technology       |                                 Auth |   Product |     Order |
|------------------|-------------------------------------:|----------:|----------:|
| PHP constraint   |                               `^8.3` |    `^8.3` |    `^8.3` |
| Container tag    | `php:8.4-cli` via `base-laravel:8.4` |      same |      same |
| Laravel          |                            `13.20.0` | `13.20.0` | `13.20.0` |
| Firebase PHP-JWT |                              `7.1.0` |   `7.1.0` |   `7.1.0` |
| Sanctum          |                               absent |   `4.3.2` |   `4.3.2` |
| PHPUnit          |                            `12.5.31` | `12.5.31` | `12.5.31` |

The base image installs PDO MySQL, Zip, and unpinned PECL Xdebug. Composer comes from mutable `composer:latest`.

### Gateway and microservices frontend

| Component                                | Version                                                              |
| ---------------------------------------- | -------------------------------------------------------------------- | --- | ---------- |
| Java level / build image / runtime image | 21 / `maven:3.9-eclipse-temurin-21` / `eclipse-temurin:21-jre-jammy` |
| Spring Boot / Spring Cloud BOM           | `3.4.3` / `2024.0.0`                                                 |
| Spring Cloud Gateway / Spring Security   | `4.2.0` / `6.4.3`                                                    |
| Reactor Netty / Netty                    | `1.2.3` / `4.1.118.Final`                                            |
| Frontend Docker tag                      | `node:20-alpine`                                                     |
| Declared frontend Node engine            | `^22.18.0                                                            |     | >=24.12.0` |
| Vue / Router / Pinia                     | `3.5.40` / `5.2.0` / `4.0.2`                                         |
| Vite / Tailwind / TypeScript             | `8.1.5` / `4.3.3` / `6.0.3`                                          |

The Node image does not satisfy the package's engine declaration. npm behavior/build warnings must be recorded. Maven has no dependency lock file, although the parent/BOM pin the observed runtime tree. Base image tags and OS packages are not digest-pinned.

```bash
export TCC_PROJECTS=/Users/devnit/Documents/projects

cd "$TCC_PROJECTS/tcc-monolith" && composer show --locked --direct
cd "$TCC_PROJECTS/tcc-monolith" && npm list --depth=0
for service in tcc-auth-service tcc-product-service tcc-order-service; do
  cd "$TCC_PROJECTS/$service" && composer show --locked --direct
done
cd "$TCC_PROJECTS/tcc-frontend-microservices" && npm list --depth=0
cd "$TCC_PROJECTS/api-gateway-core" && ./mvnw dependency:tree -Dscope=runtime
```

## 4. Containers, ports, networks, and databases

| Service               | Container name               | Host:container | Role                               |
|-----------------------|------------------------------|----------------|------------------------------------|
| `tcc-frontend`        | `tcc-frontend-microservices` | `5173:5173`    | Vite development server            |
| `api-gateway-core`    | `api-gateway-core`           | `8080:8080`    | authentication boundary and router |
| `tcc-auth-service`    | `tcc-auth-service`           | `8003:8003`    | token/user service                 |
| `tcc-product-service` | `tcc-product-service`        | `8001:8001`    | catalog/inventory service          |
| `tcc-order-service`   | `tcc-order-service`          | `8002:8002`    | order service                      |
| `laravel-base`        | generated                    | none           | base-image builder                 |

The example database names are `tcc_auth_db`, `tcc_products_db`, and `tcc_orders_db`, with `host.docker.internal:3306`. These are only effective as MySQL settings when `DB_CONNECTION=mysql` is supplied by the ignored environment. MySQL version, storage, tuning, and limits are not committed.

The order Dockerfile says `EXPOSE 8003` but starts and publishes 8002. The declaration is inconsistent but does not override the actual command/mapping.

The monolith has no committed container/port mapping. `composer run dev` normally uses Laravel port 8000 and Vite port 5173; record the actual printed addresses.

## 5. Equivalent endpoints used by the experiment

Use the monolith HTTP server as one entry point and the microservices gateway on port 8080 as the other.

| Method        | Equivalent path                 | Contract notes                                                                                   |
|---------------|---------------------------------|--------------------------------------------------------------------------------------------------|
| `GET`         | `/api/v1/products`              | optional `name`, `page`, `per_page`                                                              |
| `POST`        | `/api/v1/products`              | full product payload                                                                             |
| `GET`         | `/api/v1/products/{product}`    | integer product ID                                                                               |
| `PUT`/`PATCH` | `/api/v1/products/{product}`    | full product payload                                                                             |
| `DELETE`      | `/api/v1/products/{product}`    | no body                                                                                          |
| `GET`         | `/api/v1/orders`                | optional `name`, `status`, `page`, `per_page`                                                    |
| `POST`        | `/api/v1/orders`                | unique `Idempotency-Key`; optional `name`; `items[]` containing integer `product_id`, `quantity` |
| `GET`         | `/api/v1/orders/{order}`        | monolith ID is bigint; microservice ID is UUID                                                   |
| `PUT`/`PATCH` | `/api/v1/orders/{order}`        | required `name` and `items[]`                                                                    |
| `DELETE`      | `/api/v1/orders/{order}`        | no body                                                                                          |
| `POST`        | `/api/v1/orders/{order}/cancel` | no body                                                                                          |

Qualifications:

- Microservices also expose `/api/v1/orders/{order}/paid`; the monolith paid route is `/api/v1/internal/orders/{order}/paid`. Do not call these externally equivalent.
- The gateway does not route `/api/v1/internal/**`; internal product/order endpoints are excluded unless measured as a separately defined internal workload.
- `/api/v1/auth/token` exists only in microservices and is setup, not equivalent commerce functionality.
- Direct calls to 8001/8002 bypass gateway cost and are not the microservices architecture entry point.
- Generate deterministic unique idempotency keys and map valid architecture-specific order IDs.

## 6. Database initialization and seed procedure

All Laravel applications use migrations plus `DatabaseSeeder`:

```bash
php artisan migrate:fresh --seed --force
```

This drops all tables in the selected database but cannot create a missing MySQL database.

| Database | Native seed result                                                                                                                                                             |
|----------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Monolith | 100 users, then 1,000 products and 5,000 orders with 1-5 products. Each order factory also creates its own user, so total users are 5,100. Order statuses and data are random. |
| Auth     | one admin: `admin@example.com`, role `admin`, factory password `password`                                                                                                      |
| Product  | 1,000 products; no reservations                                                                                                                                                |
| Order    | 25 pending orders and exactly 50 items                                                                                                                                         |

**Experiment-critical unresolved facts:** Faker and `rand()` have no committed seed; datasets are not equivalent; no dump/fixture/hash exists. Seeded microservice orders contain random user/product/reservation identifiers and are not coordinated with auth/product rows, so state-changing operations may not be valid.

A controlled comparison requires manually prepared, logically equivalent snapshots with row counts, checksums, user mapping, product mapping, and order-ID mapping. The repositories do not supply that procedure. If native unequal seeds are used, the comparison must be labeled confounded.

## 7. Authentication flow

### Monolith

1. Fortify handles `GET/POST /login` using email/password and the `web` session guard.
2. Login is limited to 5/minute by normalized email plus IP.
3. The example persists sessions in the `sessions` database table.
4. Commerce APIs use `auth` plus `throttle:api`, limited to 120/minute by user ID or IP.

**Unresolved:** `api.php` is in Laravel's stateless API middleware group, while the only guard is session-based. No Sanctum stateful middleware or token endpoint is committed, and API tests use `actingAs()`. The real external authentication/cookie/CSRF procedure for monolith JSON APIs must be validated and recorded before timing.

### Microservices

1. `POST /api/v1/auth/token` verifies email/password and returns a 15-minute HS256 JWT.
2. Claims are `iss`, `aud`, `sub`, `email`, `name`, `roles`, `jti`, `iat`, `nbf`, `exp`.
3. Issuance is limited to 5/minute by email plus IP.
4. The gateway validates signature, issuer, audience, timestamps, and required identity claims.
5. It strips incoming `X-User-Id`, `X-User-Email`, `X-User-Name`, `X-User-Roles`, `X-User-Permissions`, and `X-Gateway-Key`, then sets the first four identity headers and the shared gateway key from validated claims/configuration.
6. Product mutations require role `admin`; order visibility is identity-scoped unless admin.
7. The SPA stores the token in local or session storage. There is no refresh-token flow.

JWT secret/issuer/audience must match auth and gateway. `GATEWAY_INTERNAL_KEY` must match gateway/product/order. Compose does not pass the gateway key; gateway has a source default while service examples use a different value. Effective equality depends on ignored `.env` files or manual configuration and must be recorded without exposing the secret.

## 8. Build procedure for every component

Use a clean checkout of every audited revision. Preserve the lock files and archive command output. The commands below install/build the repository state; they do not resolve the environment gaps identified elsewhere.

### Monolith

```bash
export TCC_PROJECTS=/Users/devnit/Documents/projects
cd "$TCC_PROJECTS/tcc-monolith"
composer install --no-interaction --prefer-dist
npm ci
npm run build
```

`composer run setup` is a repository shortcut, but it copies `.env`, generates an application key, uses `npm install` rather than `npm ci`, and runs migrations. Keeping dependency build and database initialization separate is easier to audit.

### Microservices

```bash
export TCC_PROJECTS=/Users/devnit/Documents/projects
cd "$TCC_PROJECTS/tcc-container"
docker compose build laravel-base
docker compose build
docker compose images
docker image inspect base-laravel:8.4 --format '{{json .RepoDigests}} {{.Id}}'
```

The component builds performed by Compose are:

- Auth, product, and order: derive from `base-laravel:8.4`, copy the entire repository, run `composer install --no-interaction --optimize-autoloader`, then run `php artisan serve` at startup.
- Gateway: system Maven runs `mvn clean package`, including the Maven test phase, in a Java 21 builder, followed by a Java 21 JRE image.
- Frontend: `npm install`, then Vite's development server at startup; there is no production asset build in the Compose path.

Builds are not bit-reproducible because image tags, `composer:latest`, apt repositories, PECL Xdebug, and Maven artifacts are not all content-addressed or locked. The Laravel Dockerfiles have no `.dockerignore`; ignored local `.env`, `vendor`, or other files can enter the image. Before building, record `git status --short`, the build context contents, image IDs/digests, and whether the build cache was used. Do not publish secrets from image inspection.

## 9. Startup procedure for every component

### Monolith

The repository-native full development stack is:

```bash
cd /Users/devnit/Documents/projects/tcc-monolith
composer run dev
```

It concurrently runs the Laravel server, queue listener, Pail, and Vite. For an API-only benchmark, a narrower repository-supported command is:

```bash
cd /Users/devnit/Documents/projects/tcc-monolith
php artisan serve --host=127.0.0.1 --port=8000
```

Choose one mode before the experiment and use it for every monolith repetition. Record all companion processes and the actual URL. Do not compare a full `composer run dev` process group against only selected microservice containers without reporting that measurement boundary.

### Microservices

```bash
cd /Users/devnit/Documents/projects/tcc-container
docker compose up -d
docker compose ps
curl --fail http://localhost:8080/actuator/health
curl --fail http://localhost:8001/up
curl --fail http://localhost:8002/up
curl --fail http://localhost:8003/up
```

Compose `depends_on` controls start order, not readiness; there are no health checks. `laravel-base` is not a persistent application component and may exit. Wait for every request-path component and the external database to be ready before warm-up. The frontend is required only for a browser workload, not a direct HTTP API workload.

## 10. Environment variables relevant to performance

Record effective values inside each process/container, not only `.env.example`. Redact secret values while recording a cryptographic hash when equality matters.

| Area               | Relevant variables/settings                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| Laravel runtime    | `APP_ENV`, `APP_DEBUG`, `APP_URL`, `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`                                               |
| Persistence        | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, database user, `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` |
| PHP                | PHP patch version, loaded extensions, `php.ini`, OPcache/JIT, Xdebug mode and start policy, memory limit                 |
| Microservice calls | `PRODUCT_SERVICE_URL`, `PRODUCT_SERVICE_CONNECT_TIMEOUT`, `PRODUCT_SERVICE_TIMEOUT`, `PRODUCT_RESERVATION_TTL_MINUTES`   |
| Authentication     | `JWT_TTL_MINUTES`, issuer/audience, equality of JWT secrets, equality of `GATEWAY_INTERNAL_KEY`                          |
| Gateway/JVM        | `SERVER_PORT`, Java patch, JVM arguments, heap limits, GC, processor count, Reactor Netty settings if overridden         |
| Frontend           | `VITE_API_BASE_URL`, `VITE_API_PROXY_TARGET`; include the frontend only when it is in the timed path                     |
| Containers/host    | CPU and memory limits, Docker Desktop VM resources, architecture, filesystem/storage driver, network mode                |

Compose enables Xdebug `debug` mode with `xdebug.start_with_request=yes` for all three Laravel services. That adds request overhead. The monolith's Xdebug state is not repository-controlled. Disable or enable it consistently as a manually declared experimental choice; archive `php -v`, `php -m`, and `php --ini` from every PHP runtime.

The product client uses a 2-second connect timeout and 5-second request timeout by default. Snapshot and reserve retry connection failures after 100 and 300 ms; not all calls retry. Reservation TTL defaults to 15 minutes. Compose does not pass the product URL, timeout, TTL, or gateway-key variables, so source defaults or files copied into the images determine them. These values affect failed and stateful order workloads.

## 11. Logging configuration

- All four Laravel applications default through `.env.example` to `LOG_CHANNEL=stack`, `LOG_STACK=single`, and `LOG_LEVEL=debug`. The single channel writes `storage/logs/laravel.log`.
- No explicit application `Log`, `logger`, or custom request-logging calls were found in the Laravel commerce code.
- Laravel container storage is not mounted, so file logs are ephemeral and are not necessarily present in `docker compose logs`.
- The gateway has an INFO request filter that logs receipt and completion/duration for each request to the container console. This is extra measured work relative to the monolith.
- `composer run dev` includes Pail, which tails Laravel logs as a separate process.

Record log levels, drivers, retention, rotation, disk destination, and whether stdout/file collection is active. If changing logging for the benchmark, apply one declared policy consistently and clear old logs before each repetition.

```bash
export TCC_RESULTS=/absolute/path/to/run-results
mkdir -p "$TCC_RESULTS"
cd /Users/devnit/Documents/projects/tcc-container
docker compose logs --no-color --timestamps api-gateway-core > "$TCC_RESULTS/gateway.log"
docker cp tcc-auth-service:/var/www/storage/logs/laravel.log "$TCC_RESULTS/auth-laravel.log"
docker cp tcc-product-service:/var/www/storage/logs/laravel.log "$TCC_RESULTS/product-laravel.log"
docker cp tcc-order-service:/var/www/storage/logs/laravel.log "$TCC_RESULTS/order-laravel.log"
```

The redirection destinations are examples and must be placed in a run-specific results directory outside the repositories.

## 12. Caching configuration

No explicit application-level `Cache::`, `cache()`, Redis, or HTTP response cache was found. Every Laravel `.env.example` selects `CACHE_STORE=database`. Laravel rate limiters use the configured default cache, so authenticated requests may add cache-table database activity even though the business code has no cache calls.

No build/start script runs `config:cache`, `route:cache`, or `optimize`. Record whether caches are cold or warm and whether optimization is enabled. Use the same policy for every repetition:

```bash
php artisan optimize:clear
# Only if the declared protocol uses cached production configuration:
php artisan optimize
```

Database cache rows expire logically but may remain physically present. Reset or count the `cache` and `cache_locks` tables as part of dataset validation.

## 13. Database indexes that can affect measurements

The following are explicit in migrations or present in the audited monolith schema. Database engines may create additional supporting indexes for foreign keys; capture the live schema for every run.

| Architecture/schema       | Measurement-relevant indexes                                                                                                                           |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------|
| Monolith products         | primary `id`; unique `sku`; unique `slug`; no explicit `name` or `is_active` index                                                                     |
| Monolith orders           | primary integer `id`; unique nullable `idempotency_key`; `user_id` has FK-supporting index; no explicit `status`, `name`, or `created_at` index        |
| Monolith order pivot      | primary `id`; unique `(product_id, order_id)`; FK-supporting indexes                                                                                   |
| Product products          | primary `id`; unique `sku`; unique `slug`; no explicit `name` or `is_active` index                                                                     |
| Product reservations      | UUID primary key; unique `idempotency_key`; indexes on `order_id` and `status`                                                                         |
| Product reservation items | primary key; unique `(stock_reservation_id, product_id)`; FK-supporting indexes                                                                        |
| Order orders              | UUID primary key; unique `idempotency_key`; separate indexes on `user_id` and `status`; unique `stock_reservation_id`; no `name` or `created_at` index |
| Order items               | primary key; index on `product_id`; unique `(order_id, product_id)`; FK on `order_id`                                                                  |

Users have unique email indexes. Database cache expiration, sessions, jobs, and passkey tables have their framework-defined indexes and can influence shared database load.

Both product listings filter `is_active`, optionally use a leading-wildcard name search, and order by descending ID; the relevant filters are unindexed. Order listings filter identity, optional status/name, and newest order. The microservice explicitly indexes status while the monolith does not. Different primary-key types and index layouts are part of the implementations and must not be silently altered.

For MySQL, archive live evidence for every schema:

```bash
mysql --host=HOST --port=PORT --user=USER --password DATABASE \
  --execute='SHOW CREATE TABLE products; SHOW INDEX FROM products;'
mysql --host=HOST --port=PORT --user=USER --password DATABASE \
  --execute='SHOW CREATE TABLE orders; SHOW INDEX FROM orders;'
```

Avoid putting the password directly on the command line in the final experiment script; use a protected option file or interactive prompt.

## 14. Commands required to reset the environment

These are destructive to the selected databases. Verify database names before executing them and retain a snapshot if recovery is required.

### Monolith reset

```bash
cd /Users/devnit/Documents/projects/tcc-monolith
php artisan optimize:clear
php artisan migrate:fresh --seed --force
```

### Microservices reset

```bash
cd /Users/devnit/Documents/projects/tcc-container
docker compose down --volumes --remove-orphans
docker compose up -d
docker compose exec tcc-auth-service php artisan optimize:clear
docker compose exec tcc-auth-service php artisan migrate:fresh --seed --force
docker compose exec tcc-product-service php artisan optimize:clear
docker compose exec tcc-product-service php artisan migrate:fresh --seed --force
docker compose exec tcc-order-service php artisan optimize:clear
docker compose exec tcc-order-service php artisan migrate:fresh --seed --force
```

`docker compose down --volumes` does **not** reset the external database because Compose declares no database volume or server. The migration commands reset only whatever databases the containers actually resolve. Confirm the three effective connections first. Native seeds produce unequal, random datasets; use these commands only if that is the explicitly declared experiment condition.

For equivalent prepared snapshots, repository commands are **unresolved**. Record the exact restore commands, dump hashes, table counts, auto-increment state, and cleanup of cache/session/reservation/idempotency state.

## 15. Commands required to start each architecture

```bash
# Monolith, API-only experiment mode
cd /Users/devnit/Documents/projects/tcc-monolith
php artisan serve --host=127.0.0.1 --port=8000

# Microservices
cd /Users/devnit/Documents/projects/tcc-container
docker compose up -d
```

If the full UIs are part of the experiment, use `composer run dev` for the monolith and include `tcc-frontend` in the microservices measurement. Do not change mode between repetitions.

## 16. Commands required to stop each architecture

Stop a foreground monolith command with `Ctrl-C`. If `composer run dev` was used, stop its parent process so all four child commands terminate, then verify no relevant process remains.

```bash
ps -Ao pid,ppid,command | grep -E '[a]rtisan serve|[q]ueue:listen|[v]ite|[p]ail'

cd /Users/devnit/Documents/projects/tcc-container
docker compose down --remove-orphans
```

Use `down --volumes` only when intentionally discarding Compose-managed volumes; it still does not touch the external database.

## 17. Commands to collect CPU and RAM metrics

For microservice container totals:

```bash
docker stats --no-stream \
  api-gateway-core tcc-auth-service tcc-product-service tcc-order-service

docker stats --format '{{json .}}' \
  api-gateway-core tcc-auth-service tcc-product-service tcc-order-service
```

The second command samples continuously until interrupted and should be redirected by the experiment harness to a timestamped results file. Include `tcc-frontend-microservices` only for browser workloads. Docker stats excludes the external database and host-side load generator.

For a native monolith on macOS/Linux, capture the server PID and its descendants, then sample them with an OS tool, for example:

```bash
pgrep -af 'artisan serve|queue:listen|pail|vite'
ps -o pid,ppid,%cpu,rss,vsz,etime,command -p PID
top -pid PID -stats pid,cpu,mem,rsize,vsize,time,command
```

`top` syntax varies by OS. Define whether measurements are per-process, process-tree totals, container totals, or whole-system totals; define sampling interval and aggregation. Measure the external database separately using the same declared resource boundary. Record idle baselines. Do not compare native host metrics and Docker cgroup metrics without documenting the different accounting domains.

## 18. Commands for PHPMetrics and PHP Insights

Only the monolith lock file includes PHPMetrics (`2.9.1`) and PHP Insights (`2.14.2`). The three PHP service repositories contain neither dependency, so there is no repository-native microservice command. Adding dependencies is outside this documentation-only task.

Monolith commands:

```bash
cd /Users/devnit/Documents/projects/tcc-monolith
composer install --no-interaction --prefer-dist
./vendor/bin/phpmetrics --report-html=reports/phpmetrics-run app
./vendor/bin/phpinsights analyse app --summary --no-interaction --disable-security-check
```

For a cross-PHP comparison, a separately version-pinned analyzer environment may invoke the exact same PHPMetrics and PHP Insights versions against each service's `app` directory. This is a **manual experiment decision**, not a command guaranteed by those repositories. Record analyzer versions, command flags, scope, exclusions, exit code, and output hash. `--disable-security-check` is required when using the monolith installation to analyze a sibling repository whose lock file does not contain the analyzer packages.

PHPMetrics and PHP Insights do not analyze the Java gateway or TypeScript/Vue frontend. Use language-appropriate static measures separately if those components are within the thesis metric boundary.

## 19. Commands to measure lines of code

No LOC tool or canonical scope is committed. The following reproducible physical-line method counts tracked source files while excluding dependencies, generated assets, reports, and tests. Archive the exact file list as well as the total.

```bash
export TCC_PROJECTS=/Users/devnit/Documents/projects

cd "$TCC_PROJECTS/tcc-monolith"
git ls-files -z -- 'app/*.php' 'app/**/*.php' 'routes/*.php' \
  'resources/js/*.ts' 'resources/js/*.vue' 'resources/js/**/*.ts' 'resources/js/**/*.vue' \
  | xargs -0 wc -l

for service in tcc-auth-service tcc-product-service tcc-order-service; do
  cd "$TCC_PROJECTS/$service"
  git ls-files -z -- 'app/*.php' 'app/**/*.php' 'routes/*.php' | xargs -0 wc -l
done

cd "$TCC_PROJECTS/api-gateway-core"
git ls-files -z -- 'src/main/**/*.java' | xargs -0 wc -l

cd "$TCC_PROJECTS/tcc-frontend-microservices"
git ls-files -z -- 'src/*.ts' 'src/*.vue' 'src/**/*.ts' 'src/**/*.vue' | xargs -0 wc -l
```

Shell glob pathspec behavior must be validated from the archived file list. Decide whether the comparison is backend commerce code only or whole deployable architecture. A fair whole-architecture microservice total includes three PHP services, gateway, and frontend, while a PHP-only metric does not. Record whether blank/comment/generated/config/migration/test lines are included. If using `cloc`, pin and record its version and run it against the archived file list.

## 20. Information that must be manually recorded

The repositories do not determine the following. Each run manifest must record them:

1. UTC/local timestamp, operator, run ID, architecture order, repetition number, warm-up, cool-down, and randomization strategy.
2. Full commit IDs, `git status --short`, patches for dirty files, submodule state, lock-file hashes, and build-context hashes.
3. Host model, CPU topology, RAM, OS/kernel, CPU governor/power mode, thermal state, background processes, and clock synchronization.
4. Native PHP/Composer/Node/npm/Java/Maven versions and modules; Docker engine/Compose versions, VM CPU/RAM/swap/disk allocation, storage/network drivers, image IDs/digests, and build-cache policy.
5. Effective sanitized environment/config from every process, including Xdebug, OPcache/JIT, JVM heap/GC, Laravel optimization state, logging, cache, queue, session, and resource limits.
6. Database engine/version/edition, host location, CPU/RAM/storage, connection limits, buffer/cache settings, isolation level, SQL mode, schema/index definitions, table statistics, dataset row counts/checksums, and whether its CPU/RAM is included.
7. Exact deterministic fixture or snapshot creation/restore procedure, Faker/RNG seed if any, benchmark user credentials/roles, ownership mappings, valid product/order/reservation identifiers, and idempotency-key policy.
8. Validated monolith API authentication procedure and the microservice JWT/key configuration. Store only redacted values or hashes in the manifest.
9. Load generator name/version/host, target URLs, HTTP version/keep-alive/TLS behavior, request mix/payloads/headers, concurrency/arrival model, duration, timeouts, retries, think time, and whether redirects/errors count.
10. Dataset state between requests and repetitions: cache/session/rate-limit rows, reservations, stock, order statuses, logs, database buffer cache, OS page cache, JVM warm-up, and PHP process lifetime.
11. Readiness checks, stabilization time, frontend inclusion, direct-versus-gateway routing, server process mode, and all processes/containers included in resource totals.
12. Raw latency/throughput/error results, response validation, status-code distribution, CPU/RAM sampling interval/tool/aggregation, idle baseline, database metrics, logs, and hashes of every raw artifact.
13. Static-analysis and LOC scope, tool versions, flags, exclusions, file lists, and treatment of PHP, Java, TypeScript, Vue, tests, migrations, generated code, and dependencies.
14. Any deviations, failures, restarts, throttling responses, timeouts, reservation expiry, and environmental interference.

No workload generator, benchmark script, fixed workload, result schema, deterministic cross-schema fixture, or resource-accounting definition is committed. Those items must be frozen in an experiment manifest before results can be called reproducible.
