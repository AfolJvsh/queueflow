# QueueFlow — Background Job & Workflow SaaS

## 1. Project objective

Build a multi-tenant workflow execution platform where users define a trigger and a sequence of background actions. A workflow execution must be durable, retryable, observable, and safe under duplicate queue delivery.

This is not primarily a visual Zapier clone. The engineering centerpiece is the workflow engine.

## 2. Engineering signals

QueueFlow should prove competence in:
- Redis queues and worker scaling.
- Workflow state machines.
- Delayed and scheduled jobs.
- Retry classification and exponential backoff.
- Idempotency.
- Duplicate-delivery tolerance.
- Distributed locks and concurrency limits.
- Queue priorities.
- Dead-letter/terminal-failure handling.
- Rate limiting and downstream throttling.
- Execution observability.

## 3. Recommended stack

- Laravel for application/domain orchestration.
- React + Inertia for UI.
- PostgreSQL as durable execution truth.
- Redis for queues, locks, throttles, and ephemeral counters.
- Horizon for queue visibility.
- Docker Compose for local development.
- S3-compatible storage later for large step payloads/artifacts.

Keep it a modular monolith first. Microservices are not an MVP requirement.

## 4. MVP product surface

### Triggers
- Manual.
- Signed inbound webhook.
- Schedule/cron.

### Actions
- HTTP request.
- JSON/data transform.
- Conditional branch.
- Delay.
- Email notification.
- Outbound webhook.
- Store a result/value.

All action types must implement a common handler contract. Do not put action-specific execution logic in controllers.

## 5. Core execution flow

```text
Trigger
  ↓
Authenticate + validate + quota check
  ↓
Create WorkflowExecution with idempotency key
  ↓
Dispatch first StepExecution
  ↓
Redis queue
  ↓
Worker claims step
  ↓
Action handler executes
  ↓
Persist result/attempt
  ↓
Dispatch next eligible step
  ↓
Finalize workflow
```

PostgreSQL is the source of truth. Queue messages are delivery mechanisms, not the durable state machine.

## 6. Domain model

### organizations
`id, name, slug, plan, status`

### organization_user
`organization_id, user_id, role`

### workflows
`id, organization_id, name, slug, status, current_version_id`

### workflow_versions
`id, workflow_id, version_number, definition_json, published_at`

Published versions should be immutable.

### workflow_steps
`id, workflow_version_id, key, type, position, config_json, retry_policy_json`

### workflow_executions
`id(UUID), organization_id, workflow_version_id, trigger_type, idempotency_key, status, context_json, started_at, completed_at, failure_code`

### step_executions
`id, workflow_execution_id, workflow_step_id, status, attempt_count, input_json, output_json, started_at, completed_at, error_class, error_message`

### execution_attempts
`id, step_execution_id, attempt_number, worker_identifier, started_at, finished_at, status, error_metadata_json`

### webhook_endpoints
`id, workflow_id, secret_hash, enabled, last_received_at`

### scheduled_triggers
`id, workflow_id, cron_expression, timezone, next_run_at`

## 7. State model

Workflow execution:

```text
pending → running → succeeded
                  ↘ failed
                  ↘ cancelled
```

Step execution:

```text
pending → running → succeeded
                  ↘ retry_wait → pending
                  ↘ failed
                  ↘ skipped
```

State transitions must be explicit and tested.

## 8. Idempotency requirements

Treat idempotency as a headline feature.

- Inbound webhooks accept or derive an idempotency key.
- Unique constraint on tenant + workflow/version + idempotency key.
- Duplicate trigger returns/references the existing execution.
- Step execution gets a deterministic logical key.
- A duplicate queue job must not produce two logical completions.
- Where external APIs support it, pass an idempotency key downstream.
- Document which effects are at-least-once, best-effort, or externally idempotent. Do not claim universal exactly-once behavior.

## 9. Concurrency and locking

Implement:
- Redis lock for step claiming/critical execution paths.
- DB transaction for state transition + attempt bookkeeping.
- Organization concurrent execution cap.
- Workflow-level max concurrent executions.
- Duplicate job delivery test.
- Protection against two workers completing the same logical step.

A strong test intentionally dispatches the same job twice and proves there is only one final step result.

## 10. Retry strategy

Classify errors into:
- Transient infrastructure/network.
- HTTP 429/rate limit.
- HTTP 5xx.
- Invalid workflow/action configuration.
- Authentication failure.
- Validation/business-rule failure.

Support:
- Fixed delay.
- Exponential backoff.
- Exponential backoff with jitter.
- Maximum attempts.

After maximum retries:
- Persist terminal error metadata.
- Mark the step failed.
- Mark workflow failed or invoke explicit failure path.
- Allow manual retry from the failed step.

## 11. Rate limits

Implement three levels:
1. Public/API rate limit per API key/user.
2. Organization workflow execution quota.
3. Downstream integration throttle, e.g. N requests/minute per target/provider.

Use Redis for hot counters but keep durable plan/usage records where needed.

## 12. UI/screens

- Workflow list.
- Create/edit workflow.
- Trigger configuration.
- Step configuration.
- Published-version history.
- Execution list.
- Execution detail/timeline.
- Failed executions.
- Schedules.
- Webhook/API credentials.
- Usage/limits.
- Organization settings.

The execution detail page is the demo centerpiece: each step, state, duration, attempt history, input/output preview, retry delay, and failure reason.

## 13. Milestones

### M0 — Repository foundation
- Docker environment.
- PostgreSQL + Redis.
- Auth + organizations.
- CI.
- README architecture overview.

**Exit:** clone → one documented command → working app.

### M1 — Workflow definitions
- Workflows.
- Versioning.
- Step schema.
- Draft/publish lifecycle.
- Manual trigger.

**Exit:** published definitions are immutable and inspectable.

### M2 — Async execution engine
- Execution records.
- Step dispatcher.
- Queue worker.
- Handler registry.
- At least three action types.
- Execution timeline.

**Exit:** execution completes after original HTTP request ends.

### M3 — Reliability
- Retry policy.
- Backoff.
- Idempotency.
- Locks.
- Duplicate delivery tests.
- Failed execution handling.
- Manual retry.

**Exit:** retries/redelivery do not duplicate logical state.

### M4 — External/scheduled triggers
- Signed webhook trigger.
- Schedule trigger.
- Delayed step.

**Exit:** workflows execute without an active logged-in browser session.

### M5 — Operational controls
- Queue priorities.
- Tenant/workflow concurrency limits.
- Downstream throttles.
- Structured logs.
- Metrics.
- Admin-only Horizon exposure.

### M6 — Portfolio polish
- Architecture diagram.
- Workflow state-machine document.
- Idempotency document.
- Failure matrix.
- Load test.
- Seed workflows.
- Demo media.
- ADRs.

## 14. Testing plan

### Unit
- Action handlers.
- Config validators.
- Retry policy.
- State transitions.

### Feature
- Publish lifecycle.
- Tenant authorization.
- Manual/webhook triggers.
- Signed webhook verification.

### Queue/integration
- 429 → delayed retry.
- 5xx → retry/fallback behavior.
- Duplicate queue delivery.
- Worker failure mid-step.
- Lock contention.
- Manual retry after terminal failure.

### Performance
Enqueue several thousand executions and report:
- Throughput/minute.
- Queue lag.
- p50/p95 workflow duration.
- Impact of worker-count changes.

## 15. Observability

Metrics:
- Queue depth.
- Oldest queued-job age.
- Executions/minute.
- Success rate.
- Step failure rate.
- Retry count.
- p50/p95 execution time.
- Downstream latency.

Structured log context:
`organization_id, workflow_id, execution_id, step_execution_id, job_id`.

## 16. Security requirements

- Hash non-retrievable secrets.
- Encrypt retrievable connector secrets.
- Verify inbound webhook signatures.
- Sign outbound webhooks.
- Redact secrets from logs/output previews.
- Enforce tenant authorization on every workflow/execution query.
- Add SSRF defenses to generic HTTP-request steps.
- Block private-network/metadata endpoints unless explicitly safe.

## 17. Repository documentation

Required:
- `README.md`
- `docs/architecture.md`
- `docs/workflow-state-machine.md`
- `docs/idempotency.md`
- `docs/failure-handling.md`
- `docs/load-testing.md`
- `docs/adr/`

## 18. Portfolio definition of done

A reviewer can:
1. Build a 4+ step workflow.
2. Trigger it by webhook or schedule.
3. Watch background execution.
4. Force an external failure.
5. See automatic backoff/retry.
6. Replay a duplicate trigger and see no duplicate logical workflow.
7. Retry a terminal failure manually.
8. Inspect execution/queue metrics.
9. Run the full stack locally from the README.

## 19. Do not build yet

- Complex drag-and-drop canvas.
- 30+ integrations.
- Arbitrary user code execution.
- Integration marketplace.
- Kubernetes.
- Microservice decomposition.
- Complex billing.
- AI-generated workflows.

Those features dilute the real signal: a reliable asynchronous workflow engine.
