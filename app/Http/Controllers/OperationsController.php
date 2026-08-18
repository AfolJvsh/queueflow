<?php

namespace App\Http\Controllers;

use App\Models\{ConnectorSecret, Organization, WorkflowExecution};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Redis};

final class OperationsController
{
    public function saveSecret(Request $request): JsonResponse
    {
        $data = $request->validate(['organization_id' => 'required|uuid', 'name' => 'required|string|max:80', 'value' => 'required|string|max:10000']);
        $this->auth($request, $data['organization_id']);
        $secret = ConnectorSecret::updateOrCreate(['organization_id' => $data['organization_id'], 'name' => $data['name']], ['secret_encrypted' => $data['value']]);
        return response()->json(['id' => $secret->id, 'name' => $secret->name], 201);
    }

    public function secrets(Request $request): JsonResponse
    {
        $ids = $request->user()->organizations()->pluck('organizations.id');
        return response()->json(ConnectorSecret::whereIn('organization_id', $ids)->get(['id', 'organization_id', 'name', 'updated_at']));
    }

    public function deleteSecret(Request $request, ConnectorSecret $secret): JsonResponse
    {
        $this->auth($request, $secret->organization_id);
        $secret->delete();
        return response()->json(['deleted' => true]);
    }

    public function organization(Request $request, Organization $organization): JsonResponse
    {
        $this->auth($request, $organization->id);
        $period = now('UTC')->startOfMonth()->toDateString();
        $usage = (int) DB::table('organization_execution_usage')->where('organization_id', $organization->id)->where('period_start', $period)->value('execution_count');
        return response()->json(['organization' => $organization, 'usage' => ['period_start' => $period, 'execution_count' => $usage]]);
    }

    public function updateOrganization(Request $request, Organization $organization): JsonResponse
    {
        $this->owner($request, $organization->id);
        $data = $request->validate([
            'max_concurrent_executions' => 'sometimes|integer|min:1|max:10000',
            'monthly_execution_limit' => 'sometimes|integer|min:0|max:1000000000',
        ]);
        $organization->update($data);
        return $this->organization($request, $organization->fresh());
    }

    public function metrics(Request $request): JsonResponse
    {
        $ids = $request->user()->organizations()->pluck('organizations.id');
        $since = now()->subHours(24);
        $query = WorkflowExecution::whereIn('organization_id', $ids)->where('created_at', '>=', $since);
        $total = (clone $query)->count();
        $success = (clone $query)->where('status', 'succeeded')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $retry = (int) DB::table('execution_attempts as a')
            ->join('step_executions as s', 's.id', '=', 'a.step_execution_id')
            ->join('workflow_executions as e', 'e.id', '=', 's.workflow_execution_id')
            ->whereIn('e.organization_id', $ids)->where('a.created_at', '>=', $since)->where('a.attempt_number', '>', 1)->count();
        $stepTotal = (int) DB::table('step_executions as s')->join('workflow_executions as e', 'e.id', '=', 's.workflow_execution_id')->whereIn('e.organization_id', $ids)->where('s.created_at', '>=', $since)->count();
        $stepFailed = (int) DB::table('step_executions as s')->join('workflow_executions as e', 'e.id', '=', 's.workflow_execution_id')->whereIn('e.organization_id', $ids)->where('s.created_at', '>=', $since)->where('s.status', 'failed')->count();
        $durations = (clone $query)->whereNotNull('started_at')->whereNotNull('completed_at')->get(['started_at', 'completed_at'])->map(fn ($e) => $e->started_at->diffInMilliseconds($e->completed_at))->sort()->values();
        $attemptDurations = DB::table('execution_attempts as a')->join('step_executions as s', 's.id', '=', 'a.step_execution_id')->join('workflow_executions as e', 'e.id', '=', 's.workflow_execution_id')->whereIn('e.organization_id', $ids)->where('a.created_at', '>=', $since)->whereNotNull('a.finished_at')->selectRaw('EXTRACT(EPOCH FROM (a.finished_at - a.started_at)) * 1000 AS duration_ms')->pluck('duration_ms')->map(fn ($v) => (int) $v)->sort()->values();
        $downstreamDurations = DB::table('execution_attempts as a')
            ->join('step_executions as s', 's.id', '=', 'a.step_execution_id')
            ->join('workflow_executions as e', 'e.id', '=', 's.workflow_execution_id')
            ->join('workflow_steps as ws', 'ws.id', '=', 's.workflow_step_id')
            ->whereIn('e.organization_id', $ids)->where('a.created_at', '>=', $since)->whereNotNull('a.finished_at')
            ->whereIn('ws.type', ['http_request', 'outbound_webhook', 'email_notification'])
            ->selectRaw('EXTRACT(EPOCH FROM (a.finished_at - a.started_at)) * 1000 AS duration_ms')
            ->pluck('duration_ms')->map(fn ($v) => (int) $v)->sort()->values();
        $percentile = fn ($collection, $p) => $collection->isEmpty() ? null : $collection[(int) floor(($collection->count() - 1) * $p)];
        $queueDepth = []; $oldestAge = [];
        foreach (['workflows-high', 'workflows', 'workflows-low'] as $name) {
            $queueDepth[$name] = (int) Redis::llen('queues:'.$name);
            $oldestAge[$name] = $this->oldestQueueAge($name);
        }
        return response()->json([
            'window' => '24h',
            'executions' => $total,
            'executions_per_minute' => round($total / (24 * 60), 3),
            'success_rate' => $total ? $success / $total : 0,
            'failure_rate' => $total ? $failed / $total : 0,
            'step_failure_rate' => $stepTotal ? $stepFailed / $stepTotal : 0,
            'retry_attempts' => $retry,
            'duration_ms' => ['p50' => $percentile($durations, .5), 'p95' => $percentile($durations, .95)],
            'attempt_duration_ms' => ['p50' => $percentile($attemptDurations, .5), 'p95' => $percentile($attemptDurations, .95)],
            'downstream_latency_ms' => ['p50' => $percentile($downstreamDurations, .5), 'p95' => $percentile($downstreamDurations, .95)],
            'queue_depth' => $queueDepth,
            'oldest_queued_job_age_seconds' => $oldestAge,
        ]);
    }

    private function oldestQueueAge(string $name): ?int
    {
        $raw = Redis::lindex('queues:'.$name, 0);
        if (! $raw) return null;
        $payload = json_decode((string) $raw, true);
        $pushedAt = $payload['pushedAt'] ?? $payload['data']['pushedAt'] ?? null;
        if (! is_numeric($pushedAt)) return null;
        return max(0, time() - (int) $pushedAt);
    }

    private function auth(Request $request, string $organization): void
    {
        abort_unless($request->user()->organizations()->whereKey($organization)->exists(), 403);
    }

    private function owner(Request $request, string $organization): void
    {
        abort_unless($request->user()->organizations()->whereKey($organization)->wherePivot('role', 'owner')->exists(), 403);
    }
}
