# Action catalog and external-effect safety

All actions implement `ActionHandler`; controllers never contain action-specific execution logic.

| Type | External effect | Idempotency / safety |
|---|---|---|
| `transform` | none | deterministic context transform |
| `store_value` | none | deterministic context write |
| `conditional` | none | may skip the immediately following step |
| `delay` | none | queue delay, no sleeping worker |
| `http_request` | HTTP | SSRF guard, per-target throttle, forwards `Idempotency-Key` |
| `outbound_webhook` | HTTP | SSRF guard, throttle, HMAC signature and `Idempotency-Key` |
| `email_notification` | email | at-least-once transport; downstream mail provider may duplicate after an ambiguous failure |

Generic HTTP actions reject localhost, `.local`, metadata IPs, RFC1918/private and reserved DNS resolutions. Connector secret values are encrypted in PostgreSQL and redacted when execution details are serialized.

Do not describe QueueFlow as universally exactly-once. Database state is duplicate-safe, while third-party effects depend on the target's idempotency contract.
