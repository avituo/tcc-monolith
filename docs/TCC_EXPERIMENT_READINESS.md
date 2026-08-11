# TCC experiment readiness

## Status and evidence boundary

This document records the experiment preparation implemented across the seven thesis repositories. It does not claim that a performance benchmark has been executed. The functional smoke requests described below were untimed readiness checks.

The application business contracts were not expanded. Experiment-only behavior is disabled by default and is enabled only by the dedicated Docker Compose files.

## Changes made

### Deterministic equivalent dataset

The same canonical fixture implementation is present in:

- `tcc-monolith/database/seeders/BenchmarkDataset.php`
- `tcc-auth-service/database/seeders/BenchmarkDataset.php`
- `tcc-product-service/database/seeders/BenchmarkDataset.php`
- `tcc-order-service/database/seeders/BenchmarkDataset.php`

The four files are identical. Each repository's `DatabaseSeeder` now inserts its owned part of this fixture directly, in chunks, without Faker, factories, random UUIDs, `rand()`, `now()`, or implicit model timestamps.

| Logical entity     | Exact definition                                                                                      |
|--------------------|-------------------------------------------------------------------------------------------------------|
| Users              | 100, logical and physical IDs 1 through 100                                                           |
| Products           | 1,000, logical and physical IDs 1 through 1,000                                                       |
| Orders             | 5,000                                                                                                 |
| Order items        | exactly two per order; 10,000 total                                                                   |
| Ownership          | `((order ordinal - 1) % 100) + 1`; exactly 50 orders per user                                         |
| Statuses           | 2,500 `pending`, 1,500 `paid`, 1,000 `cancelled`; each user owns 25/15/10                             |
| Product assignment | two distinct products from a fixed ordinal formula                                                    |
| Prices/totals      | calculated in integer cents and stored as fixed two-decimal strings                                   |
| Product stock      | `100000 + product ordinal` in both architectures                                                      |
| Product state      | all active, version 1; identical SKU, slug, name, description, price, discount, stock, and timestamps |
| Timestamps         | fixed UTC epoch plus deterministic ordinal offsets                                                    |
| Password           | fixed benchmark-only bcrypt hash; logical password `benchmark-password`                               |

The stable logical fixture fingerprint is:

```text
44cb257fd1b0fbd23dde712b5250ca3d002eded4c2a9e911c74c80c94eb073a7
```

Physical order IDs are mapped explicitly:

| Logical order | Monolith ID | Microservice ID                                        |
|--------------:|------------:|--------------------------------------------------------|
|             1 |         `1` | `20000000-0000-4000-8000-000000000001`                 |
|             n |         `n` | `20000000-0000-4000-8000-` plus n as 12 decimal digits |
|         5,000 |      `5000` | `20000000-0000-4000-8000-000000005000`                 |

Order idempotency keys also have a deterministic mapping: `10000000-0000-4000-8000-` plus the logical ordinal as 12 decimal digits.

`tcc-monolith/database/factories/OrderFactory.php` no longer declares `user_id => User::factory()`. A caller must now supply a user explicitly, so an order factory cannot silently create additional users. The two existing bare factory uses were updated to associate an explicit user.

### Dataset validation

Each Laravel repository now provides `php artisan experiment:dataset:validate`. The command prints owned table counts, the logical fingerprint, selected ID mappings, and relevant status/ownership distributions, and returns a failure exit code when its invariants differ.

Focused PHPUnit coverage was added in each Laravel repository. The monolith also has an experiment authentication feature test. No migration or index was added or changed.

### Experiment-only runtime

`tcc-container` now contains the common Laravel experiment runtime:

- `Dockerfile.experiment-base`
- `docker/php/experiment.ini`
- `docker/mysql/experiment.cnf`
- `docker/mysql/init-microservices.sql`
- `docker-compose.experiment.yml`
- `.env.experiment.example`
- `bin/tcc-experiment`

The monolith now contains:

- `Dockerfile.experiment`
- `docker-compose.experiment.yml`
- `.dockerignore`
- `.env.experiment.example`
- `bin/tcc-experiment`

Auth, product, and order each have an experiment Dockerfile and `.dockerignore`. The gateway has `.dockerignore` and a dedicated `Dockerfile.experiment`. Build contexts exclude `.env`, `.env.*`, `vendor`, tests, generated caches, logs, and editor/Git metadata as applicable, preventing local secrets or ignored configuration from entering runtime images.

The experiment-only base images are content-pinned:

- PHP `8.4.19-cli-bookworm`: `sha256:3ddb5a91b44a1c922538576d73a6e808fb2438d9d2d65b1fc3ffd55619fac2e3`
- Composer `2.9.2`: `sha256:c4f6b39889c396b86fd3603c047bb73929f05fcaa887e78f172d366e0891062b`
- MySQL `8.4.8`: `sha256:2952e3be7807f06fc18de50b3ea1a632d5c70d63482ff7d7376fe3aa8999babf`
- Maven/Temurin 21 builder: `sha256:c07f7ccfb8ca6c9fa29ee523f00afa7d2ca6132c92f8652c4aebb5ee3491f502`
- Temurin 21 JRE: `sha256:3097cbbebb7d490494a98aed2301f284b38f79eba158eef098c6fc8c8af11c23`

The frontend is intentionally absent from the API-only experiment path. Microservice requests enter through the gateway, not through direct service ports.

### Controlled configuration

| Setting          | Monolith                                         | Laravel microservices                        | Gateway/MySQL                        |
|------------------|--------------------------------------------------|----------------------------------------------|--------------------------------------|
| Runtime          | PHP 8.4.19 CLI                                   | same PHP 8.4.19 CLI base                     | Java 21.0.11 / MySQL 8.4.8           |
| Laravel          | 13.14.0                                          | 13.20.0                                      | Spring Boot 3.4.3                    |
| Environment      | `production`                                     | `production`                                 | UTC                                  |
| Debug            | off                                              | off                                          | root log level `WARN`                |
| Xdebug           | not installed; startup verifies absent           | same                                         | not applicable                       |
| OPcache          | enabled, including CLI; timestamp validation off | same                                         | not applicable                       |
| PHP memory       | 512 MiB                                          | 512 MiB                                      | Java heap fixed at 256 MiB           |
| Container memory | PHP 1 GiB; MySQL 1 GiB                           | each PHP 1 GiB; gateway 512 MiB; MySQL 1 GiB | G1GC; MySQL buffer pool 512 MiB      |
| Database         | explicit `mysql`                                 | explicit `mysql`                             | UTF-8 MB4, UTC, 200 connections      |
| Cache            | `array`                                          | `array`                                      | no distributed cache                 |
| Session          | `database` for Fortify cookie auth               | `array`; commerce auth is JWT                | JWT TTL 15 minutes                   |
| Queue            | `sync`                                           | `sync`                                       | not applicable                       |
| Application logs | `stderr`, level `warning`                        | `stderr`, level `warning`                    | gateway root `WARN`                  |
| Rate limiting    | disabled by experiment flag                      | product/order disabled by experiment flag    | token acquisition remains setup-only |

All PHP request-serving components run `php artisan serve`, with cached configuration/routes, under the same common PHP filesystem layers and PHP configuration. The ordinary repository Dockerfiles and default application behavior are unchanged.

MySQL databases are isolated from non-experiment databases:

- monolith: `tcc_monolith_experiment`
- microservices: `tcc_auth_experiment`, `tcc_products_experiment`, `tcc_orders_experiment`

The two architectures use separate MySQL containers and persistent volumes. Do not run both architectures during a timed repetition.

Secrets are generated locally by `init`, written with restrictive permissions to ignored `.env.experiment` files, omitted from logs, and never committed. Generated cookies/JWTs are stored under ignored `.experiment-auth` directories with restrictive permissions.

### Experiment-only monolith authentication

The monolith now has `config/experiment.php`. When, and only when, `TCC_EXPERIMENT_SESSION_AUTH=true`:

- API routes receive the `web` middleware before the existing `auth` middleware.
- `GET /experiment/auth/csrf` exposes the current session CSRF token.
- the external client can log in through the existing Fortify `POST /login` route and use the resulting session cookie for the API.

The experiment also gates destructive production resets and rate-limit disabling behind explicit environment flags. All flags default to false, so the application's normal production route/middleware policy is unchanged.

The product and order services have an equivalent experiment-only rate-limit flag, also false by default.

## Exact build, start, reset, validate, and stop procedures

Run `init` once per checkout. It creates local secrets; it does not print them.

### Monolith

```bash
cd /Users/devnit/Documents/projects/tcc-monolith
./bin/tcc-experiment init
./bin/tcc-experiment start
./bin/tcc-experiment reset
./bin/tcc-experiment validate
./bin/tcc-experiment auth
```

`start` builds the pinned common PHP base and monolith image, starts MySQL and Laravel, waits for `/up`, verifies that Xdebug is absent, and prints runtime/environment versions. The external API base URL is `http://localhost:18000`.

Stop without deleting the prepared database volume:

```bash
cd /Users/devnit/Documents/projects/tcc-monolith
./bin/tcc-experiment stop
```

Destroy the isolated database volume only when a complete teardown is intended:

```bash
cd /Users/devnit/Documents/projects/tcc-monolith
./bin/tcc-experiment destroy
```

### Microservices

```bash
cd /Users/devnit/Documents/projects/tcc-container
./bin/tcc-experiment init
./bin/tcc-experiment start
./bin/tcc-experiment reset
./bin/tcc-experiment validate
./bin/tcc-experiment auth
```

`start` builds the pinned common PHP base, three Laravel services, and pinned gateway image; starts MySQL, auth, product, order, and gateway; waits for gateway health; verifies Xdebug absence and runtime versions. The external API base URL is `http://localhost:18080`.

Stop without deleting the prepared database volume:

```bash
cd /Users/devnit/Documents/projects/tcc-container
./bin/tcc-experiment stop
```

Complete isolated teardown:

```bash
cd /Users/devnit/Documents/projects/tcc-container
./bin/tcc-experiment destroy
```

`reset` is intentionally destructive only to the hard-coded experiment databases. It runs `optimize:clear`, `migrate:fresh --seed --force`, then caches configuration/routes and invokes validation. Run it before each independent repetition whose initial state must be identical. In particular, reset before repeating the write scenario because `POST /orders` changes stock, orders, items, and, in microservices, reservations.

## Expected dataset validation output

Monolith:

```text
database=tcc_monolith_experiment
user_count=100
product_count=1000
order_count=5000
order_item_count=10000
status_distribution={"pending":2500,"paid":1500,"cancelled":1000}
orders_per_user_min=50
orders_per_user_max=50
logical_fingerprint=44cb257fd1b0fbd23dde712b5250ca3d002eded4c2a9e911c74c80c94eb073a7
order_mapping_1=1
order_mapping_5000=5000
benchmark_dataset=VALID
```

Microservices, combined output from the three owned schemas:

```text
database=tcc_auth_experiment
user_count=100
logical_fingerprint=44cb257fd1b0fbd23dde712b5250ca3d002eded4c2a9e911c74c80c94eb073a7
benchmark_users=VALID
database=tcc_products_experiment
product_count=1000
stock_reservation_count=0
logical_fingerprint=44cb257fd1b0fbd23dde712b5250ca3d002eded4c2a9e911c74c80c94eb073a7
benchmark_products=VALID
database=tcc_orders_experiment
order_count=5000
order_item_count=10000
status_distribution={"pending":2500,"paid":1500,"cancelled":1000}
orders_per_user_min=50
orders_per_user_max=50
logical_fingerprint=44cb257fd1b0fbd23dde712b5250ca3d002eded4c2a9e911c74c80c94eb073a7
order_mapping_1=20000000-0000-4000-8000-000000000001
order_mapping_5000=20000000-0000-4000-8000-000000005000
benchmark_orders=VALID
```

## Authentication preparation outside timing

The benchmark user in both architectures is:

```text
email: benchmark-user-001@example.test
password: benchmark-password
logical user ID: 1
```

This is an isolated, benchmark-only credential, not an application secret.

For the monolith, run `./bin/tcc-experiment auth` after every reset and before warm-up. It performs CSRF setup and Fortify login, validates an authenticated API request, and creates:

- `.experiment-auth/monolith.cookies`
- `.experiment-auth/monolith.csrf`

GET requests use the cookie jar. The timed POST also uses the token file as `X-CSRF-TOKEN`. Login and CSRF acquisition must not be included in commerce latency.

For microservices, run `./bin/tcc-experiment auth` after reset and immediately before warm-up. It acquires the JWT through the gateway and writes `.experiment-auth/microservices.jwt`. Token acquisition must not be timed. The JWT TTL is 15 minutes, so the frozen warm-up plus timed interval must finish before expiry or the TTL must be explicitly revised and recorded before the run set.

## Primary benchmark URLs and logical requests

| Scenario          | Monolith                                     | Microservices                                                                   |
|-------------------|----------------------------------------------|---------------------------------------------------------------------------------|
| Product list      | `GET http://localhost:18000/api/v1/products` | `GET http://localhost:18080/api/v1/products`                                    |
| User-1 order list | `GET http://localhost:18000/api/v1/orders`   | `GET http://localhost:18080/api/v1/orders`                                      |
| Logical order 1   | `GET http://localhost:18000/api/v1/orders/1` | `GET http://localhost:18080/api/v1/orders/20000000-0000-4000-8000-000000000001` |
| Create order      | `POST http://localhost:18000/api/v1/orders`  | `POST http://localhost:18080/api/v1/orders`                                     |

Use the same JSON payload in both architectures, for example:

```json
{
  "items": [
    {"product_id": 1, "quantity": 1},
    {"product_id": 2, "quantity": 2}
  ]
}
```

Each create request requires an `Idempotency-Key`. Freeze a deterministic per-request key sequence in the workload manifest and use the same logical keys for both targets. Never reuse one key for a different payload within the same reset.

Seeded orders are intended for the three GET scenarios. Seeded microservice orders deliberately have no product-service reservation rows because no cross-database foreign key exists; lifecycle mutation of seeded orders is outside the primary endpoint set. A new POST creates a coordinated product reservation and exercises the required distributed Order to Product path.

## Validation evidence collected on 2026-08-11

- Monolith reset/validation produced exactly 100 users, 1,000 products, 5,000 orders, 10,000 items, the expected status distribution, and `benchmark_dataset=VALID`.
- The three microservice schemas produced the same counts/distribution/fingerprint and all three `VALID` results.
- External monolith CSRF plus Fortify login succeeded, and a cookie-authenticated commerce request succeeded.
- Gateway JWT acquisition succeeded outside timing.
- Untimed smoke results for both architectures were: product list 200, order list 200, logical order show 200, order create 201.
- The microservice POST passed through the gateway and completed the synchronous Order to Product path.
- Focused MySQL PHPUnit results: monolith dataset 1 test/13 assertions; auth dataset 1/6; product dataset 1/7; order dataset 1/15; monolith experiment authentication 1/6.
- The two monolith suites affected by explicit factory ownership passed 16 tests/111 assertions.
- Gateway image build ran its Maven tests successfully.
- Pint passed in all four modified Laravel repositories.
- The host-only PHPUnit attempt was not used as evidence because the host PHP installation lacks `pdo_sqlite`; the reported tests above ran in the controlled PHP 8.4.19 container against the isolated MySQL databases.

Final locally observed runtime image IDs were:

```text
tcc-laravel-experiment  sha256:02299fc8e7b09e4610b1745875cc69b364a95719a224c77f71edb38e4a33330e
tcc-monolith-experiment sha256:053653d8d8dcc3fe4221fd5a39218c074fac206aa4c75a4ec2aae9cf9de9479e
tcc-auth-experiment     sha256:caba07bb01b38bff159a01e4a92bd8edff65dcf64ccadedf002911d9f8107919
tcc-product-experiment  sha256:eb714b3ce4c1b36b325674c930c9129e470e9c16d66186535acc7e868353d4db
tcc-order-experiment    sha256:7dd09886d1b3bf46be48269db59467face1709896139b4fe6e6ac7d1d9347968
tcc-gateway-experiment  sha256:e48aab2b795c5a05cd0914c8853cd4690776285ab2a53a05453a30975c50f321
mysql:8.4.8             sha256:7791889374e752a470ed491c5c7684bf91d64c2472721539bef3a64f5276e074
```

Re-record IDs for the actual final run and archive/export the images; locally built IDs are not registry digests.

## Remaining unresolved issues

### Critical blockers

None. The researcher-defined workload, timing, counterbalancing, authentication, success criteria, resource boundary, one-second sampling, build benchmark, and startup benchmark are now frozen in `docs/TCC_FINAL_BENCHMARK_PROTOCOL.md`. The short validation run is documented in `docs/TCC_PILOT_REPORT.md` and is excluded from thesis results.

### Required final-run records that are not application blockers

- Full commit IDs, working-tree status, and patches for all seven repositories. Pre-existing unrelated frontend and `.DS_Store` changes were not modified by this task.
- Host hardware, OS, power/thermal state, background processes, Docker versions, and Docker VM allocation.
- Effective image IDs, build logs, lock-file hashes, and whether build cache was used. Although base images are digest-pinned, apt packages and Maven artifacts should be preserved by archiving/exporting the built images.
- Database buffer and OS cache warm/cold policy, stabilization delay, and idle baseline.
- Raw metrics, logs, request results, and hashes of all experiment artifacts.
- The exact idempotency-key sequence and reset point for every POST repetition.

FINAL EXPERIMENT READY: YES
