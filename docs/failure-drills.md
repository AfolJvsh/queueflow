# Failure drills

## HTTP 5xx → retry → dead letter

Run `tools/seed_demo.py`. Trigger `HTTP retry and dead-letter drill`. Its first step calls a deterministic 503 endpoint, enters `retry_wait`, retries according to the published policy, then creates a terminal dead letter. The following step is skipped. Manual retry reopens the same execution and preserves prior attempt history.

## Duplicate trigger

Run `tools/load_trigger.py --duplicate-ratio 1`. Every accepted response in the cohort must reference one execution ID because `(organization_id, workflow_version_id, idempotency_key)` is unique.

## Duplicate queue delivery

Dispatch `ExecuteWorkflowStep` twice for one execution from Tinker or a test. The claim lock and transactional step status allow one transition from pending to running; the duplicate observes no claimable logical step.

## Worker dies mid-step

Terminate a worker after a step enters `running`. After the stale lease threshold, `RecoverStalledSteps` closes the attempt as `worker_lost`, then either requeues or dead-letters according to the step's max-attempt policy.

## Delayed Redis delivery is lost

Delete a delayed retry job from Redis while its step is `retry_wait`. PostgreSQL still holds `retry_at`; the minute sweep releases overdue retry rows.

## Rate limit

Set a small `requests_per_minute` on an HTTP action, fan out triggers, and observe retry classification `rate_limit`. Organization and workflow concurrent execution limits are separate admission controls and return 429 before execution creation.
