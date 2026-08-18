# Triggers and scheduling

QueueFlow supports manual, signed webhook, and cron triggers. All three converge on `TriggerWorkflow`, so idempotency, quota and execution creation semantics are shared.

## Signed inbound webhook

1. Create/rotate a webhook from the workflow operations API.
2. QueueFlow returns the secret once and stores it encrypted at rest.
3. Send `X-QueueFlow-Timestamp`, `X-QueueFlow-Signature = HMAC_SHA256(timestamp + "." + rawBody, secret)`, and preferably `X-QueueFlow-Delivery`.
4. Signatures older than five minutes are rejected.
5. `X-QueueFlow-Delivery` becomes the execution idempotency key. Replaying the same delivery references the same logical execution.

`tools/send_signed_webhook.py` implements the signing contract.

## Schedules

Schedules store cron expression, timezone and the next due instant. The scheduler claims due rows, advances `next_run_at`, and uses `schedule:{schedule_id}:{scheduled_for}` as the deterministic execution key. Scheduler re-delivery therefore does not duplicate the logical run.
