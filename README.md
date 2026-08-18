# QueueFlow

QueueFlow is a multi-tenant background workflow SaaS built to demonstrate durable asynchronous systems engineering rather than CRUD automation screens.

A workflow is published as an **immutable version**, triggered asynchronously, and executed step-by-step by Redis/Horizon workers while PostgreSQL remains the source of truth for execution state, retry deadlines, attempts, quotas and dead letters.

## Engineering focus

- Laravel + React/Inertia control plane.
- PostgreSQL durable workflow and retry state.
- Redis queues, locks, downstream throttles and hot queue metrics.
- Horizon worker supervision with high/default/low workflow queues.
- Explicit workflow and step state machines.
- Trigger/step idempotency and duplicate-delivery tolerance.
- Fixed/exponential/jitter retry policies and `Retry-After` handling.
- Durable retry deadlines plus stale-worker recovery.
- Organization/workflow concurrency admission and monthly execution quota.
- Signed inbound/outbound webhooks, encrypted connector secrets and SSRF defenses.
- Execution timeline, attempt history, dead letters, schedules and operations metrics.

## Supported triggers

- Manual API/UI trigger.
- HMAC-signed inbound webhook.
- Cron schedule with timezone and deterministic scheduled-run idempotency.

## Supported actions

`transform`, `store_value`, `conditional`, `delay`, `http_request`, `outbound_webhook`, `email_notification`.

Every action implements the same `ActionHandler` contract. External HTTP effects receive deterministic idempotency keys where possible. QueueFlow does **not** claim universal exactly-once delivery to third parties.

## Quick start

Requirements: Docker with Compose.

```bash
cp .env.example .env
docker compose up --build -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Open `http://localhost:8000`, create a workspace, then build/publish a workflow. Horizon runs in its own service and the scheduler runs `schedule:work` continuously.

To seed two representative workflows through the real API:

```bash
python tools/seed_demo.py --register
```

The command prints a bearer token, workflow IDs and a one-time webhook secret. Send a correctly signed webhook with:

```bash
python tools/send_signed_webhook.py 'http://localhost:8000/api/hooks/<endpoint-id>' '<secret>'
```

## Architecture

```text
Browser / API / Webhook / Scheduler
              |
              v
         Laravel API
              |
    +---------+----------+
    |                    |
PostgreSQL              Redis
workflow truth      queues / locks /
retries / attempts  throttles / depth
    |                    |
    +---------+----------+
              v
          Horizon workers
              |
              v
         Action handlers
              |
        external systems
```

The queue is a transport. A missing Redis delayed job cannot erase a retry because `retry_at` is persisted and swept from PostgreSQL. A dead worker cannot permanently strand `running` state because stale running steps are repaired from durable attempt state.

## Reliability demo

`tools/seed_demo.py` creates:

1. **Reliable order pipeline** — transform → conditional → delayed step → stored result, with manual, webhook and scheduled triggers.
2. **HTTP retry and dead-letter drill** — deterministic HTTP 503, exponential retries, terminal dead letter and manual retry.

The execution detail UI shows step status, retry deadline, worker attempt IDs, errors and durable context.

## Load and idempotency test

```bash
python tools/load_trigger.py \
  --token "$QUEUEFLOW_TOKEN" \
  --workflow-id "$WORKFLOW_ID" \
  --requests 5000 \
  --concurrency 100 \
  --duplicate-ratio 0.10
```

The duplicate cohort intentionally reuses one idempotency key. It must collapse to one logical execution ID. Do not commit made-up performance numbers; capture benchmark JSON from the hardware/worker configuration actually used.

## Validation

Fast dependency-free domain checks:

```bash
php tests/standalone.php
```

Full CI runs Composer dependencies, migrations, PHPUnit and the frontend build. Local PHP syntax can be checked with:

```bash
find app database routes config tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Documentation

- [`docs/architecture.md`](docs/architecture.md)
- [`docs/workflow-state-machine.md`](docs/workflow-state-machine.md)
- [`docs/idempotency.md`](docs/idempotency.md)
- [`docs/failure-handling.md`](docs/failure-handling.md)
- [`docs/triggers-and-scheduling.md`](docs/triggers-and-scheduling.md)
- [`docs/action-catalog.md`](docs/action-catalog.md)
- [`docs/load-testing.md`](docs/load-testing.md)
- [`docs/failure-drills.md`](docs/failure-drills.md)
- [`docs/adr/`](docs/adr/)

## Scope boundary

This repository deliberately does not include a drag-and-drop canvas, arbitrary user code, dozens of integrations, Kubernetes or microservice decomposition. The portfolio signal is the workflow engine: durable state, idempotency, retries, scheduling, concurrency and observable failure recovery.
