# Workflow state machines

PostgreSQL owns execution truth. Redis jobs may be duplicated, delayed, or lost and later repaired; they never define the durable state.

## Workflow execution

`pending → running → succeeded | failed | cancelled`

A terminal failed execution can be explicitly reopened by the manual-retry operation. The retry starts from the first failed step and does not rewrite historical `execution_attempts`.

## Step execution

`pending → running → succeeded`

`running → retry_wait → pending`

`running → failed`

`pending → skipped`

Terminal failure creates a `dead_letters` row. A delayed retry stores `retry_at` in PostgreSQL before a delayed queue message is emitted. `DispatchDueRetrySteps` sweeps overdue rows, so losing the delayed Redis delivery does not strand the workflow.

## Worker-loss repair

A step left `running` beyond the stale threshold is assumed to have lost its worker lease. `RecoverStalledSteps` closes the in-flight attempt as `worker_lost`; if attempts remain, the step returns to `pending`, otherwise it is dead-lettered.

## Concurrency

Step claim is guarded by a short Redis execution lock plus a database transaction/row lock. Organization and workflow admission limits are checked under the organization admission lock and durable monthly usage is incremented transactionally.
