# TCC Final Harness Pilot Report

Pilot date: 2026-08-11  
Protocol version: 1  
Pilot purpose: harness validation only; these measurements are not thesis results.

## Executed condition

Only the researcher-authorized pilot was executed:

- scenario: `GET /api/v1/products`;
- concurrency: 10 virtual users;
- stabilization: 15 seconds;
- excluded warm-up: 10 seconds;
- timed measurement: 30 seconds;
- repetition: R1;
- order: monolith, then microservices;
- targets: `http://localhost:18000` and `http://localhost:18080`;
- microservice requests entered only through the API Gateway;
- JMeter: Apache JMeter 5.6.3, HttpClient4/HTTP 1.1, keep-alive, no retries;
- load-generator Java observed: OpenJDK 21.0.9.

No definitive 120-second condition, POST condition, build benchmark, startup benchmark, or 160-run sequence was executed.

## Validation results

Both canonical pilot runs completed reset, dataset validation, authentication preparation, warm-up, timed workload, semantic assertions, resource collection, analysis, cool-down, and stack shutdown.

| Validation                      | Monolith |             Microservices |
|---------------------------------|---------:|--------------------------:|
| Dataset fingerprint valid       |      yes | yes, in all three schemas |
| Authenticated warm-up samples   |      505 |                       412 |
| Timed HTTP samples              |    1,533 |                     1,620 |
| Timed HTTP/semantic errors      |        0 |                         0 |
| Scheduled resource samples      |       30 |                        30 |
| Individual container rows       |       60 |                       150 |
| Architecture-total rows         |       30 |                        30 |
| Expected resource services only |      yes |                       yes |
| Credential artifact archived    |       no |                        no |

Observed deterministic validation output in both architectures was logically equivalent:

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

Authentication validation passed because every warm-up and timed commerce request returned HTTP 200 and passed the JSON contract. Monolith used the externally prepared Fortify session cookie. Microservices used a JWT acquired through the gateway before timing.

## Pilot-only measurements

The following values demonstrate that raw-data generation and analysis work. They must not be cited as final experimental results.

| HTTP metric                                    | Monolith | Microservices |
|------------------------------------------------|---------:|--------------:|
| Samples                                        |    1,533 |         1,620 |
| Average (ms)                                   |  189.845 |       172.173 |
| Median (ms)                                    |    181.0 |         161.5 |
| P90 (ms)                                       |    233.0 |         226.0 |
| P95 (ms)                                       |    256.0 |         257.0 |
| P99 (ms)                                       |    367.0 |         364.0 |
| Minimum (ms)                                   |     29.0 |          66.0 |
| Maximum (ms)                                   |    476.0 |         451.0 |
| Population standard deviation (ms)             |   40.620 |        45.730 |
| Throughput (requests/s, 30-second denominator) |     51.1 |          54.0 |
| Error percentage                               |     0.0% |          0.0% |

| Architecture resource metric |          Monolith |       Microservices |
|------------------------------|------------------:|--------------------:|
| Average CPU                  |           96.039% |            131.511% |
| Maximum CPU                  |          115.970% |            176.210% |
| Average RAM                  | 604,345,379 bytes | 1,092,498,402 bytes |
| Maximum RAM                  | 605,468,754 bytes | 1,139,844,056 bytes |

Individual-container raw samples and summaries were generated for monolith/MySQL and gateway/auth/product/order/MySQL. Architecture totals equal the per-timestamp sum of the required member containers.

## Resource timing and Docker refresh quality

The accepted canonical archives contain 30 scheduler timestamps spanning approximately 29 seconds between the first and thirtieth sample, which represents one sample at each second of the 30-second interval.

- Monolith timestamp gaps: 0.996 to 1.004 seconds; 30 distinct complete Docker refreshes; zero reused refreshes.
- Microservices timestamp gaps: 0.920 to 1.081 seconds; 26 distinct complete Docker refreshes; four scheduled samples reused the latest complete refresh.

Docker Desktop emitted 19 transient unavailable metric markers while streaming the five-container microservice boundary. The collector did not coerce them to zero. It retained the latest complete refresh at four scheduled points and recorded the refresh sequence, refresh timestamp, reuse flag, and cumulative invalid-marker count in raw JSONL/CSV. This limitation is visible and reproducible rather than silently corrected.

## Preserved harness-validation attempts

No raw pilot artifact was deleted. Earlier validation attempts are retained beside the canonical results:

- `monolith-attempt-1-plan-load-failed`: JMeter rejected an incorrect generated header element type before workload start;
- `monolith-attempt-2-resource-parser-failed`: HTTP samples passed, but Docker Desktop ANSI control output was not yet handled;
- `monolith-attempt-3-transient-docker-metric-failed`: HTTP samples passed, but a transient `--` Docker metric correctly stopped numeric conversion;
- `monolith-attempt-4-superseded-resource-window`: HTTP and resource generation passed, but sample-count-driven collection extended beyond the strict timed boundary;
- `microservices-attempt-1-superseded-resource-window`: HTTP and resource generation passed, but the same timing audit showed a 31.5-second resource span.

The canonical `monolith/` and `microservices/` directories were created only after correcting these issues and performing a fresh database reset. Superseded attempts are not eligible for analysis.

## Raw archive locations

Canonical monolith:

```text
/Users/devnit/Documents/projects/tcc-container/benchmark/results/pilot/products/c10/r1/monolith
```

Canonical microservices:

```text
/Users/devnit/Documents/projects/tcc-container/benchmark/results/pilot/products/c10/r1/microservices
```

Each contains warm-up and timed JTL, JMeter logs, reset/validation/authentication logs, individual and total resource CSV, normalized raw Docker JSONL, condition/repository metadata, and derived JSON summaries. No authentication token, cookie jar, CSRF token, request header, or response body is archived.

## Remaining non-critical limitations and prerequisites

- POST was not pilot-run, as required. The versioned POST plan is statically generated and checked, but its definitive behavior will include genuine microservice `409 product_version_conflict` responses under contention. These must be counted as errors and not retried.
- Product stock is consumed during POST. A condition can report stock-related errors if throughput exhausts the deterministic stock; reset restores it for the next condition.
- Four microservice resource timestamps used the last complete Docker refresh. The raw reuse markers allow sensitivity analysis; final runs must retain and report the reuse count for every condition.
- The researcher must commit/tag the final experiment changes and freeze the machine, Docker Desktop resource allocation, power mode, background-process policy, and execution calendar before definitive acquisition. The runner records commit/status and runtime environment for each run.
- Pilot values are diagnostic only and must not be pooled with the five definitive repetitions.

The pilot demonstrated deterministic reset, external authentication, JMeter execution, semantic validation, raw result preservation, one-second scheduled resource collection, exact resource-boundary membership, calculated architecture totals, and credential exclusion. There is no remaining critical harness reproducibility blocker.

FINAL BENCHMARK HARNESS READY: YES
