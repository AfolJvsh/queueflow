# ADR 0002 — PostgreSQL is the workflow state machine
**Status:** Accepted

Redis queue messages are intentionally disposable delivery instructions. Every logical workflow, step, attempt, transition, and terminal outcome is durable in PostgreSQL. This lets QueueFlow recover from Redis loss and makes duplicate message delivery a tested condition rather than a correctness failure.
