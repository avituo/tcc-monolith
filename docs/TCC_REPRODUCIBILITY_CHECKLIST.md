# TCC reproducibility checklist

Use this checklist with [`TCC_EXPERIMENT_PROTOCOL.md`](TCC_EXPERIMENT_PROTOCOL.md). It is intentionally strict: an unchecked **stop condition** means the run cannot yet support a reproducible monolith-versus-microservices comparison.

## A. Freeze the experiment definition

- [ ] Assign a unique run-set ID, operator, date/time, and results directory outside all repositories.
- [ ] Record the research question, response variables, hypotheses, and acceptance/error criteria.
- [ ] Freeze one workload manifest containing every method, endpoint, header, payload, request weight, concurrency or arrival rate, duration, warm-up, cool-down, timeout, retry, think time, and repetition count.
- [ ] Pin and record the load-generator name/version and the host on which it runs.
- [ ] Decide whether the load generator uses HTTP keep-alive, HTTP/1.1 or HTTP/2, and TLS. Apply the same transport policy to both targets.
- [ ] Decide whether UI/frontend work is measured or the client calls the JSON API directly.
- [ ] If API-only, target the monolith server and microservices gateway; do not call product/order ports directly.
- [ ] Define the resource boundary: server only, complete process/container group, database, frontend, and load generator. Use the same conceptual boundary for both architectures.
- [ ] Define CPU/RAM sampling tool, interval, units, aggregation, treatment of child processes, idle baseline, and missing samples.
- [ ] Define latency statistics, throughput unit, error classification, response-validation rules, and treatment of rate-limit/timeout responses.
- [ ] Decide whether caches and database buffers start cold or warm and whether application optimization caches are enabled.
- [ ] Decide whether Xdebug and debug logging are enabled. Use one declared policy consistently.
- [ ] Randomize or counterbalance architecture run order and record the schedule.
- [ ] **Stop condition:** confirm that the same logical request sequence and success criteria can be applied to both implementations.

## B. Freeze repository and tool state

Set the common repository root:

```bash
export TCC_PROJECTS=/Users/devnit/Documents/projects
```

- [ ] For all seven repositories, archive `git rev-parse HEAD`, `git branch --show-current`, `git status --short`, and `git diff --binary`.
- [ ] Archive hashes of every `composer.lock`, `package-lock.json`, and `pom.xml` used.
- [ ] Either use clean checkouts or archive and explicitly include every dirty/untracked file in the experiment identity.
- [ ] Confirm that the working revisions match the intended audited revisions or record the replacement revisions.
- [ ] Record host hardware, OS/kernel, architecture, power mode, available RAM/disk, thermal conditions, and significant background processes.
- [ ] Archive output from `php -v`, `php -m`, `php --ini`, `composer --version`, `node --version`, `npm --version`, `java -version`, `./mvnw --version`, `docker version`, and `docker compose version` where applicable.
- [ ] Record Docker VM CPU, RAM, swap, disk, storage driver, network driver, and daemon settings.
- [ ] Record whether dependency and Docker build caches are reused.
- [ ] **Stop condition:** record content IDs/digests for every built/pulled image; mutable image tags alone are insufficient.

## C. Resolve configuration before building

- [ ] Create the required environment files without committing or publishing secrets.
- [ ] Record effective `APP_ENV`, `APP_DEBUG`, logging, cache, queue, session, and database settings for the monolith and every Laravel service.
- [ ] Explicitly set and verify `DB_CONNECTION`; Compose does not supply it.
- [ ] Verify that auth, product, and order resolve to three intended databases and that the monolith resolves to its intended database.
- [ ] Record database engine/version, server host, resource allocation, storage, connection limits, isolation level, SQL mode, and material tuning values.
- [ ] Verify equal JWT secret/issuer/audience between auth and gateway. Record redacted hashes, not secrets.
- [ ] Explicitly set the same `GATEWAY_INTERNAL_KEY` for gateway, product, and order. Record a redacted hash.
- [ ] Record `JWT_TTL_MINUTES`, product-service URL, timeouts, retry behavior, and reservation TTL.
- [ ] Record PHP Xdebug/OPcache/JIT state in the monolith and all service containers.
- [ ] Record Java patch, JVM arguments, heap/GC policy, and effective processor count.
- [ ] Record Laravel optimization-cache state and logging destinations/levels.
- [ ] **Stop condition:** validate and document an external HTTP authentication flow for the monolith API. Repository tests using `actingAs()` are not a load-generator login procedure.
- [ ] **Stop condition:** make the monolith and microservices deployment/accounting boundary explicit. The repository supplies native monolith startup but Dockerized microservices.

## D. Build and archive artifacts

### Monolith

```bash
cd "$TCC_PROJECTS/tcc-monolith"
composer install --no-interaction --prefer-dist
npm ci
npm run build
```

- [ ] Archive dependency-install/build output and lock-file hashes.
- [ ] Record generated asset hashes and effective PHP runtime details.

### Microservices

```bash
cd "$TCC_PROJECTS/tcc-container"
docker compose build laravel-base
docker compose build
docker compose images
```

- [ ] Confirm the frontend's Node engine mismatch and record whether npm warned, failed, or proceeded.
- [ ] Check that ignored `.env`, `vendor`, or other local files did not silently alter Docker build contexts; record any that did.
- [ ] Archive image IDs/digests and complete build logs.
- [ ] Record base-image IDs, PECL Xdebug version, Composer version, apt package versions, and Maven dependency tree.
- [ ] Do not treat the `laravel-base` helper as a request-serving component.

## E. Prepare an equivalent dataset

- [ ] Freeze one deterministic dataset specification or a set of database snapshots.
- [ ] Record fixture-generation code/version, RNG/Faker seeds, database dump hashes, encoding/collation, and restore commands.
- [ ] Establish equivalent benchmark users and roles in both architectures.
- [ ] Establish product mappings with equivalent IDs/attributes/stock or document architecture-specific IDs in the workload manifest.
- [ ] Establish order ownership and architecture-specific order-ID mappings; monolith IDs are integers and microservice IDs are UUIDs.
- [ ] Ensure microservice orders refer to real products and valid stock reservations before testing update/cancel/paid flows.
- [ ] Define deterministic, unique `Idempotency-Key` values and whether they are reset between repetitions.
- [ ] Record expected row counts and checksums for every business table.
- [ ] Record and compare live indexes/schema definitions from every database.
- [ ] Clear or restore cache, cache-lock, session, rate-limit, reservation, stock, and order state consistently.
- [ ] **Stop condition:** do not claim dataset equivalence from native seeders. The monolith seeds 5,100 users/1,000 products/5,000 random orders, while the services seed one auth user/1,000 products/25 disconnected pending orders.

If the purpose is instead to measure each repository's native seeded state, mark the experiment as such and record the unequal datasets as a confounder.

## F. Reset before each repetition

### Monolith native-seed condition

```bash
cd "$TCC_PROJECTS/tcc-monolith"
php artisan optimize:clear
php artisan migrate:fresh --seed --force
```

### Microservices native-seed condition

```bash
cd "$TCC_PROJECTS/tcc-container"
docker compose down --volumes --remove-orphans
docker compose up -d
docker compose exec tcc-auth-service php artisan optimize:clear
docker compose exec tcc-auth-service php artisan migrate:fresh --seed --force
docker compose exec tcc-product-service php artisan optimize:clear
docker compose exec tcc-product-service php artisan migrate:fresh --seed --force
docker compose exec tcc-order-service php artisan optimize:clear
docker compose exec tcc-order-service php artisan migrate:fresh --seed --force
```

- [ ] For an equivalent-snapshot experiment, run the separately frozen restore procedure instead of the unequal native seeders.
- [ ] Verify effective database names before any destructive migration/restore.
- [ ] Remember that `docker compose down --volumes` does not reset the external database.
- [ ] Recheck table counts, checksums, indexes, stock, reservation, cache/session, and idempotency state after reset.
- [ ] Apply the declared cold/warm database-buffer and OS-page-cache policy; record how it was achieved.
- [ ] Clear or retain logs according to the frozen logging policy.
- [ ] Confirm that no server or load-generator process from the previous repetition remains.

## G. Start and validate the monolith run

Use the preselected mode. API-only mode:

```bash
cd "$TCC_PROJECTS/tcc-monolith"
php artisan serve --host=127.0.0.1 --port=8000
```

Full repository development mode, only if declared:

```bash
cd "$TCC_PROJECTS/tcc-monolith"
composer run dev
```

- [ ] Record start time, PID and child PIDs, bound address/port, and every companion process included.
- [ ] Verify database connectivity and application readiness without consuming timed fixture state.
- [ ] Acquire authentication using the previously validated external procedure.
- [ ] Perform one response-contract check for every workload endpoint.
- [ ] Confirm that rate limits will not invalidate the intended arrival rate, or explicitly make throttling part of the workload.
- [ ] Wait the frozen stabilization interval.
- [ ] Record idle CPU/RAM baseline using the same accounting method as the timed run.
- [ ] Run the frozen warm-up and exclude/include it exactly as declared.
- [ ] Start timestamped CPU/RAM/database/log collection.
- [ ] Execute the frozen workload without changing payloads or client settings.
- [ ] Stop measurement after the declared cool-down.
- [ ] Archive raw load-generator output, metrics, logs, environment manifest, and response-validation results.
- [ ] Stop the foreground process with `Ctrl-C`; verify no Laravel/Vite/Pail/queue processes remain.

## H. Start and validate the microservices run

```bash
cd "$TCC_PROJECTS/tcc-container"
docker compose up -d
docker compose ps
curl --fail http://localhost:8080/actuator/health
curl --fail http://localhost:8001/up
curl --fail http://localhost:8002/up
curl --fail http://localhost:8003/up
```

- [ ] Wait for actual readiness; Compose `depends_on` is not a health check.
- [ ] Verify the external database and all synchronous order-to-product calls.
- [ ] Obtain a JWT through gateway `POST /api/v1/auth/token` and retain it outside timed commerce requests unless authentication is explicitly part of the workload.
- [ ] Perform one gateway response-contract check for every workload endpoint.
- [ ] Confirm that traffic targets gateway port 8080, not ports 8001/8002.
- [ ] Confirm JWT roles and gateway internal-key propagation without logging secrets.
- [ ] Wait the same frozen stabilization interval.
- [ ] Record idle CPU/RAM baseline for the declared containers and external database.
- [ ] Run the same frozen warm-up and inclusion policy.
- [ ] Start timestamped container, database, and log collection.
- [ ] Execute the identical logical workload with only documented target URL, token, and ID mappings changed.
- [ ] Stop measurement after the same cool-down.
- [ ] Archive load-generator output, `docker stats`, database metrics, gateway console logs, Laravel file logs, effective configuration, and response-validation results.
- [ ] Stop the stack:

```bash
cd "$TCC_PROJECTS/tcc-container"
docker compose down --remove-orphans
```

- [ ] Verify that no request-serving container remains.

## I. Endpoint-by-endpoint parity checks

For each row, use the same query/payload cardinality and validate semantic success before including timing:

- [ ] `GET /api/v1/products`
- [ ] `POST /api/v1/products`
- [ ] `GET /api/v1/products/{product}`
- [ ] `PUT` or `PATCH /api/v1/products/{product}`
- [ ] `DELETE /api/v1/products/{product}`
- [ ] `GET /api/v1/orders`
- [ ] `POST /api/v1/orders` with unique `Idempotency-Key`
- [ ] `GET /api/v1/orders/{order}`
- [ ] `PUT` or `PATCH /api/v1/orders/{order}`
- [ ] `DELETE /api/v1/orders/{order}`
- [ ] `POST /api/v1/orders/{order}/cancel`

Do not silently add these to the equivalence set:

- [ ] Exclude or separately classify microservice `/api/v1/orders/{order}/paid`, because the monolith's corresponding route is internal.
- [ ] Exclude setup-only microservice `/api/v1/auth/token` from commerce timings unless authentication is an explicit measured scenario.
- [ ] Exclude `/api/v1/internal/**` and direct service ports from the public architecture comparison.

## J. Static-analysis and LOC artifacts

- [ ] Run monolith PHPMetrics 2.9.1 and PHP Insights 2.14.2 with the commands in the protocol.
- [ ] Record that the service repositories do not include these tools; do not report a native service result unless a pinned external analyzer environment is supplied.
- [ ] If using an external analyzer, apply the same versions, paths, flags, and exclusions to all PHP code and archive output hashes.
- [ ] Record that PHP tools omit the Java gateway and TypeScript/Vue frontend.
- [ ] Freeze whether LOC means PHP backend only or the whole deployable architecture.
- [ ] Archive the exact tracked file list used for each LOC total.
- [ ] Record treatment of blank, comment, test, migration, configuration, generated, vendor, and `node_modules` lines.
- [ ] Pin and record the LOC tool/version, or use the physical-line commands in the protocol.

## K. Validate and seal the result set

- [ ] Confirm all planned repetitions completed and preserve run order.
- [ ] Confirm no undocumented code, environment, fixture, workload, or hardware change occurred between architectures.
- [ ] Compare request counts, status distributions, semantic validations, and error classes before comparing performance.
- [ ] Flag all 401, 403, 404, 409, 422, 429, 5xx, timeout, retry, and reservation-expiry events.
- [ ] Confirm resource totals include the declared process/container/database boundary and use consistent units/aggregation.
- [ ] Confirm logs were captured under the same policy; note gateway per-request logging as implementation overhead.
- [ ] Record cache/buffer/JVM/PHP warm state and any restarts or thermal/background interference.
- [ ] Preserve raw samples; derive summary statistics with a versioned script or notebook and record its hash.
- [ ] Generate a manifest containing every manual item from protocol section 20.
- [ ] Hash every raw result, log, snapshot, manifest, analysis output, and summary artifact.
- [ ] Document every deviation and excluded repetition with a reason; never overwrite raw data.
- [ ] **Final stop condition:** if any architecture used an unknown database driver, unrecorded fixture, unvalidated authentication flow, different workload, or incomparable resource boundary, label the result exploratory/confounded rather than reproducible.
