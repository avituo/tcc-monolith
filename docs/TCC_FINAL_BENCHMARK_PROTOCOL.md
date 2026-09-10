# Final TCC Benchmark Protocol

Protocol version: 2

Frozen on: 2026-09-10

Status: second definitive result set pending after Laravel version alignment.

## 1. Experimental question and scope

This protocol compares the existing monolith and microservices implementations under the same deterministic dataset and controlled container runtime. It does not test the frontend and does not introduce or change business behavior.

To remove framework version as a confounding factor, the monolith, Auth Service, Product Service, and Order Service pin and resolve exactly Laravel `13.20.0`. The selected version was already used by all three services; therefore, only the monolith framework package changed (`13.14.0` to `13.20.0`), while its other locked dependencies were retained. The automated parity check runs before builds and conditions. Runtime versions and the SHA-256 of each complete `composer.lock` are checked against the source and stored with the experimental evidence, preventing stale images from entering the result set.

The definitive HTTP experiment has 160 timed architecture runs:

`4 scenarios × 4 concurrency levels × 5 repetitions × 2 architectures = 160 runs`.

Each architecture/scenario/concurrency/repetition tuple is one explicit condition. Scenarios are never mixed in one JMeter run.

## 2. Frozen targets and request path

| Architecture | Base URL | Commerce path |
|---|---|---|
| Monolith | `http://localhost:18000` | JMeter → monolith Laravel container |
| Microservices | `http://localhost:18080` | JMeter → API Gateway → service |

All requests use plain HTTP/1.1. The frontend is stopped and outside the request and measurement path. Direct requests from JMeter to the auth, product, or order service are prohibited.

Logical order 1 maps to:

- monolith: physical order ID `1`;
- microservices: physical order ID `20000000-0000-4000-8000-000000000001`.

## 3. Primary scenarios and versioned plans

| Scenario | Request | JMeter plan |
|---|---|---|
| A / `products` | `GET /api/v1/products` | `benchmark/jmeter/tcc-products-v1.jmx` |
| B / `orders` | `GET /api/v1/orders` | `benchmark/jmeter/tcc-orders-v1.jmx` |
| C / `order-show` | `GET /api/v1/orders/{logical-order-1}` | `benchmark/jmeter/tcc-order-show-v1.jmx` |
| D / `order-create` | `POST /api/v1/orders` | `benchmark/jmeter/tcc-order-create-v1.jmx` |

The POST body is exactly:

```json
{
  "items": [
    {"product_id": 1, "quantity": 1},
    {"product_id": 2, "quantity": 2}
  ]
}
```

The plans are generated deterministically by `benchmark/scripts/generate-jmeter-plans.py`. Before definitive execution, verify that the committed plans match the generator:

```sh
cd /Users/devnit/Documents/projects/tcc-container
python3 benchmark/scripts/generate-jmeter-plans.py --check
```

## 4. Load-generator configuration

Apache JMeter 5.6.3 is required and enforced by the condition runner. The frozen properties are in `benchmark/jmeter/benchmark.properties`.

- HTTP implementation: HttpClient4 over HTTP/1.1;
- TLS: disabled;
- keep-alive: enabled;
- redirects: disabled;
- connection timeout: 5,000 ms;
- response timeout: 30,000 ms;
- load-generator retry count: zero;
- retry of a request already sent: disabled;
- request and response headers: not saved, to prevent credential disclosure;
- response bodies: not saved;
- failed assertion messages: saved in the raw JTL;
- workload model: closed, with each virtual user starting the next request after the previous request completes;
- pacing/think time: none;
- ramp-up: zero seconds.

The four concurrency levels are 10, 25, 50, and 100 concurrent virtual users.

The existing order-service connection-only retries toward Product Service are application behavior, not load-generator retries. They remain unchanged and are part of the measured microservices implementation.

## 5. Timing for one condition

The definitive timing is:

1. Start the selected already-built architecture.
2. Reset and validate its deterministic dataset.
3. Prepare authentication outside timing.
4. Stabilize for exactly 15 seconds.
5. Run a 30-second warm-up. Warm-up JTL is retained but excluded from definitive HTTP statistics.
6. Prime the Docker stats stream. This excluded step waits for one complete resource-boundary refresh.
7. Start the resource sampling clock and timed JMeter process together.
8. Run the timed workload for 120 seconds.
9. Sample container CPU and RAM at one-second scheduled intervals during those 120 seconds.
10. Cool down for exactly 15 seconds.
11. Stop the selected architecture.

The warm-up and timed workloads use separate JMeter processes. Server, application, and database state stays warm; the timed JMeter process establishes its own HTTP connections equally for both architectures.

The definitive runner does not clear the host OS page cache or the MySQL buffer pool. Database volumes remain allocated, but the logical schema is recreated and reseeded before every condition.

## 6. Repetitions and counterbalanced order

For every scenario and concurrency level, execute the architecture halves in this order:

| Repetition | First | Second |
|---|---|---|
| R1 | monolith | microservices |
| R2 | microservices | monolith |
| R3 | monolith | microservices |
| R4 | microservices | monolith |
| R5 | monolith | microservices |

Complete both architecture halves of a repetition before moving to the next repetition. Each half performs its own reset, validation, and authentication preparation. Do not run other workloads on the machine during a condition.

## 7. Dataset reset and validation

The condition runner invokes the following commands automatically. They may also be run manually from their repository roots.

Monolith:

```sh
cd /Users/devnit/Documents/projects/tcc-monolith
./bin/tcc-experiment reset
./bin/tcc-experiment validate
```

Microservices:

```sh
cd /Users/devnit/Documents/projects/tcc-container
./bin/tcc-experiment reset
./bin/tcc-experiment validate
```

Every successful validation must report:

```text
user_count=100
product_count=1000
order_count=5000
order_item_count=10000
status_distribution={"pending":2500,"paid":1500,"cancelled":1000}
orders_per_user_min=50
orders_per_user_max=50
logical_fingerprint=44cb257fd1b0fbd23dde712b5250ca3d002eded4c2a9e911c74c80c94eb073a7
```

The three microservice validators print their service-owned portions separately. The order UUID mapping is deterministic. Product reservations start at zero. A condition aborts if reset, validation, or authentication preparation fails.

## 8. Authentication outside timing

The benchmark user is logical user 1, `benchmark-user-001@example.test`. Its benchmark-only password is defined by the deterministic fixture procedure. Secrets generated for application signing, JWT signing, database access, and gateway trust remain in protected `.env.experiment` files and are never copied into results.

Monolith preparation:

```sh
cd /Users/devnit/Documents/projects/tcc-monolith
./bin/tcc-experiment auth /private/tmp/tcc-auth-monolith
```

The command obtains CSRF state, performs Fortify session login, follows the regenerated session, obtains a post-login CSRF token, and validates an authenticated commerce request. The harness parses the protected Netscape cookie jar once and supplies the resulting `Cookie` header to every virtual user. POST additionally supplies `X-CSRF-TOKEN`.

Microservices preparation:

```sh
cd /Users/devnit/Documents/projects/tcc-container
./bin/tcc-experiment auth /private/tmp/tcc-auth-microservices
```

The command obtains the JWT through the API Gateway and validates a bearer-authenticated commerce request through that gateway. The token has a 15-minute lifetime, so it is acquired immediately before stabilization/warm-up. Authentication acquisition is not included in commerce latency.

The condition runner writes credentials only into a mode-0700 temporary directory, loads them through a mode-0600 JMeter properties file rather than process arguments, disables JTL header capture, and removes the temporary directory at exit. Results must contain no `.jwt`, `.cookies`, `.csrf`, or `auth.properties` files.

## 9. Idempotency keys

Every POST uses:

```text
tcc-c{concurrency}-r{repetition}-t{thread}-n{request}
```

Thread and request numbers are one-based. Warm-up starts each thread counter at zero after the database reset. `benchmark/analysis/extract-thread-counters.py` counts the warm-up requests per thread and writes non-secret counter properties so the timed process continues at the next value. Therefore warm-up and timed POST requests cannot collide, and a new reset/repetition starts the sequence again.

## 10. Success and semantic assertions

A sample succeeds only if both its HTTP assertion and Groovy JSON semantic assertion pass. Invalid JSON, a semantic mismatch, a 4xx/5xx response, or a timeout marks the JTL sample unsuccessful. Failed samples are never retried.

- Products: HTTP 200; object root; `data`, `links`, and `meta`; 10 default-page products; `meta.total=1000`; descending IDs 1000 through 991; required product fields.
- Orders: HTTP 200; object root; `data`, `links`, and `meta`; 10 rows; `meta.total=50`; all rows belong to logical user 1; required order fields.
- Logical order 1: HTTP 200; correct architecture-specific physical ID; logical user 1; `pending`; total `120.50`; exactly product 1 quantity 1 and product 38 quantity 2.
- Create order: HTTP 201; nonempty order ID; logical user 1; `pending`; total `31.50`; exactly product 1 quantity 1 and product 2 quantity 2.

## 11. Run one definitive condition

From `tcc-container`, run exactly one command. The second result set is isolated with `--result-set laravel-13.20.0`, preserving the earlier mixed-version measurements. Example for scenario A, concurrency 10, R1 monolith:

```sh
cd /Users/devnit/Documents/projects/tcc-container
./benchmark/scripts/run-condition.sh \
  --architecture monolith \
  --scenario products \
  --concurrency 10 \
  --repetition 1 \
  --result-set laravel-13.20.0
```

Then run the counterbalanced second half:

```sh
./benchmark/scripts/run-condition.sh \
  --architecture microservices \
  --scenario products \
  --concurrency 10 \
  --repetition 1 \
  --result-set laravel-13.20.0
```

Replace `scenario`, `concurrency`, and `repetition` only according to the frozen matrix. The runner rejects unsupported values, refuses to run while the opposite architecture is active, and refuses to overwrite any result directory. It does not provide a command that automatically launches all 160 runs.

## 12. Raw results and calculations

Definitive results use this hierarchy:

```text
benchmark/results/laravel-13.20.0/final/{scenario}/c{concurrency}/r{repetition}/{architecture}/
```

Every successful condition retains:

- `warmup.jtl` and `warmup-jmeter.log`;
- `timed.jtl` and `timed-jmeter.log`;
- raw individual Docker rows in `resources/docker-stats-individual.csv`;
- raw architecture totals in `resources/docker-stats-totals.csv`;
- normalized raw Docker refreshes and provenance in `resources/docker-stats.raw.jsonl`;
- reset, validation, and authentication logs without credentials;
- frozen condition values, plan SHA-256, repository commit/status, and runtime environment;
- derived `http-summary.json`, `warmup-summary.json`, and `resource-summary.json`.

`benchmark/analysis/analyze-run.py` calculates:

- sample count;
- arithmetic average latency;
- median;
- P90, P95, and P99 using nearest-rank percentiles;
- minimum and maximum;
- population standard deviation;
- throughput as timed sample count divided by exactly 120 seconds;
- error percentage as unsuccessful samples divided by all timed samples;
- per-container and architecture-total average/maximum CPU;
- per-container and architecture-total average/maximum RAM.

Architecture CPU is the sum of member-container Docker CPU percentages at each scheduled sample and may exceed 100% on a multicore host. Architecture RAM is the sum of member-container memory-used bytes. Raw samples are never deleted or replaced by summaries.

Docker Desktop can transiently publish `--` for a container metric. The sampler never converts that marker to zero. It keeps the latest complete boundary refresh for the scheduled sample and records `docker_refresh_reused`, refresh sequence/time, cumulative invalid refreshes, and an aggregate reused-refresh count. This makes any reuse auditable.

## 13. Resource boundary

Monolith total:

- `monolith` Laravel container;
- monolith `mysql` container.

Microservices total:

- `gateway`;
- `auth`;
- `product`;
- `order`;
- microservices `mysql`.

The collector resolves the exact Compose project and service labels and aborts on missing or extra boundary services. JMeter, the frontend, unrelated containers, and host/Docker Desktop overhead are excluded from calculated totals. The load generator must run on the same otherwise-idle host for every condition unless the researcher records a controlled alternative.

## 14. Clean image-build benchmark

Build time is a separate experiment and is never combined with HTTP results. Use five repetitions per architecture, one command at a time:

```sh
cd /Users/devnit/Documents/projects/tcc-container
./benchmark/scripts/run-build-condition.sh --architecture monolith --repetition 1 --result-set laravel-13.20.0
./benchmark/scripts/run-build-condition.sh --architecture microservices --repetition 1 --result-set laravel-13.20.0
```

Repeat with repetitions 2 through 5. The script stops both architectures before timing, uses Docker build cache disabled, includes the common PHP experiment-base build in each applicable architecture condition, preserves the complete build log, records nanosecond wall-clock timestamps and exit status, and refuses overwrite. Do not run the HTTP or startup benchmark concurrently.

After the ten build measurements, freeze and validate the exact image set before any definitive HTTP condition:

```sh
./benchmark/scripts/freeze-final-images.sh --result-set laravel-13.20.0
```

## 15. Already-built startup benchmark

Startup time is also separate. Build all images before these measurements. Run five repetitions per architecture:

```sh
cd /Users/devnit/Documents/projects/tcc-container
./benchmark/scripts/run-startup-condition.sh --architecture monolith --repetition 1 --result-set laravel-13.20.0
./benchmark/scripts/run-startup-condition.sh --architecture microservices --repetition 1 --result-set laravel-13.20.0
```

Repeat with repetitions 2 through 5. The script starts from a stopped architecture with existing images and volumes. Timing begins immediately before `docker compose up --detach --no-build` and ends only after readiness succeeds.

- Monolith readiness: MySQL health through Compose dependency plus `http://localhost:18000/up`.
- Microservices readiness: MySQL health, `/up` inside auth/product/order containers, and gateway `/actuator/health` through port 18080.

The script preserves timing, startup/Compose logs, final container state, and then stops the architecture.

## 16. Frozen limitations and interpretation rules

- The pilot does not validate POST under load; this is intentional because the researcher requested only GET-products for the pilot.
- Framework version is controlled rather than treated as an architectural characteristic: every request-serving PHP application runs Laravel `13.20.0`, verified in source locks, frozen images, and per-condition metadata.
- Concurrent microservice POST requests can legitimately return `409 product_version_conflict` because snapshots and reservations are separate distributed steps. Such responses are errors under this protocol and must remain in the results; they must not be retried or removed.
- Repeated POST successes consume stock. Product 2 can eventually exhaust its deterministic stock during a high-throughput condition. Any resulting non-201 response remains an error. Reset occurs before the next condition.
- Monolith authentication uses database-backed sessions inside the measured monolith/MySQL boundary; microservices use JWT validation in the measured gateway. This architectural difference is part of the systems being compared.
- JMeter's scheduled duration stops new iterations at the boundary; already-started requests may complete immediately afterward and remain in the JTL. Throughput always uses the frozen 120-second denominator. Resource scheduling ends at the 120-second boundary.
- Repository commits and dirty status are archived per condition. Before definitive measurements, the researcher should commit/tag all intended experiment changes and record Docker Desktop resource settings, host power mode, background-process policy, machine model, and the final run calendar. These are operational freeze steps, not values inferred from application code.

## 17. Pilot-only command

The shorter pilot flag is deliberately restricted to GET products, concurrency 10, repetition 1. It sets warm-up to 10 seconds and measurement to 30 seconds:

```sh
./benchmark/scripts/run-condition.sh --architecture monolith --scenario products --concurrency 10 --repetition 1 --pilot --result-set laravel-13.20.0
./benchmark/scripts/run-condition.sh --architecture microservices --scenario products --concurrency 10 --repetition 1 --pilot --result-set laravel-13.20.0
```

Pilot output is stored under `benchmark/results/laravel-13.20.0/pilot/` and must never be included in thesis statistics.
