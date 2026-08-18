# Idempotency contract
- Trigger uniqueness: `(organization_id, workflow_version_id, idempotency_key)`.
- Logical step uniqueness: `(workflow_execution_id, workflow_step_id)`.
- Duplicate queue jobs race through a Redis claim lock **and** row-level database transitions.
- External side effects receive a deterministic idempotency key where the downstream supports it.
- QueueFlow promises durable at-least-once delivery handling, not magical universal exactly-once side effects.
