# QueueFlow Planner Implementation Status

This matrix maps `docs/PLANNER.md` to the current executable repository.

| Milestone | Status | Implementation evidence |
|---|---|---|
| M0 — Repository foundation | Complete | Docker environment, PostgreSQL/Redis, auth/organizations, CI, Horizon supervisors |
| M1 — Workflow definitions | Complete | `Workflow`, immutable `WorkflowVersion`, `WorkflowStep`, draft/publish flow in `WorkflowPublisher`, manual trigger |
| M2 — Async execution engine | Complete | `ExecuteWorkflowStep`, handler registry/catalog, execution/attempt timeline, priority queues |
| M3 — Reliability | Complete | durable retry deadlines, exponential backoff, idempotency/admission controls, stale-running recovery, dead letters, manual retry/cancel |
| M4 — External/scheduled triggers | Complete | signed webhook endpoint, cron scheduler, delayed action/retry release jobs |
| M5 — Operational controls | Complete | tenant/workflow concurrency, monthly quota, downstream throttling, high/default/low Horizon queues, metrics, owner-only Horizon provider |
| M6 — Portfolio polish | Complete | architecture/state/idempotency/failure docs, ADRs, seed workflows, webhook/load tools and failure drill runbook |

## Action catalog

The runtime supports all planned action families through `ActionRegistry` and `app/Domain/Workflow/Handlers/`: HTTP request, outbound webhook, transform, conditional branch, store value, delay and email notification.

## Critical invariants covered

- Published workflow versions are immutable snapshots.
- Stable idempotency keys survive queue redelivery/retry.
- Retry state is durable in PostgreSQL (`retry_at`); delayed Redis delivery loss can be repaired by scheduled sweeps.
- Stale `running` steps are recovered or dead-lettered instead of remaining stranded forever.
- Connector secrets are encrypted at rest and redacted from execution output exposed to users.
- Webhook triggers are signature verified and duplicate request IDs are admitted idempotently.
- External requests pass SSRF validation and downstream rate throttles.

## Validation evidence

- Domain suite: `tests/standalone.php`.
- Feature tests: `tests/Feature/`.
- Failure/load tools: `tools/send_signed_webhook.py`, `tools/load_trigger.py`, `tools/seed_demo.py`.
