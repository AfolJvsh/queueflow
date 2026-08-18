# ADR 0004: external effects are not universally exactly-once

QueueFlow guarantees one durable logical completion per step, but cannot guarantee that an arbitrary third party did not process a request whose response was lost.

HTTP/webhook handlers propagate deterministic idempotency keys and webhook signatures. Integrations without downstream idempotency remain at-least-once/best-effort. The UI and documentation expose attempt history instead of hiding this ambiguity.
