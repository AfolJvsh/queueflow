# ADR 0003: retry deadlines are durable PostgreSQL state

## Decision

Persist `step_executions.retry_at` before scheduling the Redis delayed job. Periodically sweep due retry rows and release them again if necessary.

## Why

A Redis delayed job is a delivery optimization, not durable workflow truth. If a queue message disappears, a workflow must remain recoverable from database state alone.

## Consequence

The system is intentionally at-least-once at the delivery layer. `ReleaseRetryStep` and step claiming therefore need to be idempotent.
