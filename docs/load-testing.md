# Load testing

The load harness sends concurrent manual triggers through the real API and deliberately reuses one idempotency key for a configurable cohort.

```bash
python tools/load_trigger.py \
  --base-url http://localhost:8000 \
  --token "$QUEUEFLOW_TOKEN" \
  --workflow-id "$WORKFLOW_ID" \
  --requests 5000 --concurrency 100 --duplicate-ratio 0.10
```

Report:
- accepted requests/second;
- HTTP status distribution (admission limits are expected to create 429s under pressure);
- trigger p50/p95 latency;
- duplicate-cohort unique execution IDs (must be exactly one);
- `/api/operations/metrics` queue depth, oldest queued-job age, success/failure rate, retry attempts, execution p50/p95 and step failure rate.

For a worker-scaling comparison, run the same input at fixed API concurrency while changing Horizon worker counts. Preserve the raw JSON outputs in `docs/benchmarks/` rather than publishing invented numbers.
