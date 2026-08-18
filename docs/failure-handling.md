# Failure matrix
| Class | Retry? | Policy |
|---|---:|---|
| transient/network | yes | exponential + jitter |
| HTTP 429 | yes | Retry-After when available |
| HTTP 5xx | yes | exponential + jitter |
| configuration | no | terminal |
| authentication | no | terminal until credentials change |
| validation/business | no | terminal |

Attempts are append-only evidence. A terminal failure leaves the workflow inspectable and eligible for an explicit manual retry path.
