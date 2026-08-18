<?php

namespace App\Http\Controllers;

use App\Models\{DeadLetter, Workflow, WorkflowExecution};
use App\Services\{ActionCatalog, TriggerWorkflow, WorkflowPublisher};
use DomainException;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Str;

final class WorkflowController
{
    public function index(Request $request): JsonResponse
    {
        $ids = $request->user()->organizations()->pluck('organizations.id');
        return response()->json(
            Workflow::whereIn('organization_id', $ids)
                ->with('currentVersion')
                ->withCount(['versions', 'schedules'])
                ->latest()
                ->paginate(50)
        );
    }

    public function show(Request $request, Workflow $workflow): JsonResponse
    {
        $this->auth($request, $workflow->organization_id);
        $workflow->load('versions.steps', 'currentVersion.steps', 'schedules', 'webhookEndpoint');
        $data = $workflow->toArray();
        if (isset($data['webhook_endpoint'])) {
            unset($data['webhook_endpoint']['secret_encrypted']);
            $data['webhook_endpoint']['url'] = url('/api/hooks/'.$workflow->webhookEndpoint->id);
        }
        $data['recent_executions'] = WorkflowExecution::query()
            ->whereIn('workflow_version_id', $workflow->versions()->select('id'))
            ->latest()->limit(25)->get();
        return response()->json($data);
    }

    public function executions(Request $request, Workflow $workflow): JsonResponse
    {
        $this->auth($request, $workflow->organization_id);
        return response()->json(
            WorkflowExecution::query()
                ->whereIn('workflow_version_id', $workflow->versions()->select('id'))
                ->latest()
                ->paginate(min(100, max(1, (int) $request->integer('per_page', 50))))
        );
    }

    public function deadLetters(Request $request, Workflow $workflow): JsonResponse
    {
        $this->auth($request, $workflow->organization_id);
        $executionIds = WorkflowExecution::query()->whereIn('workflow_version_id', $workflow->versions()->select('id'))->select('id');
        return response()->json(
            DeadLetter::query()->whereIn('workflow_execution_id', $executionIds)
                ->when($request->boolean('unresolved', true), fn ($q) => $q->whereNull('resolved_at'))
                ->latest('created_at')->paginate(50)
        );
    }

    public function catalog(ActionCatalog $catalog): JsonResponse
    {
        return response()->json(['actions' => $catalog->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'organization_id' => 'required|uuid',
            'name' => 'required|string|max:120',
            'max_concurrent_executions' => 'integer|min:1|max:1000',
            'queue_priority' => 'in:high,default,low',
        ]);
        $this->auth($request, $data['organization_id']);
        $workflow = Workflow::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'max_concurrent_executions' => $data['max_concurrent_executions'] ?? 5,
            'queue_priority' => $data['queue_priority'] ?? 'default',
        ]);
        return response()->json($workflow, 201);
    }

    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $this->auth($request, $workflow->organization_id);
        $data = $request->validate([
            'name' => 'string|max:120',
            'status' => 'in:draft,active,disabled',
            'max_concurrent_executions' => 'integer|min:1|max:1000',
            'queue_priority' => 'in:high,default,low',
        ]);
        $workflow->update($data);
        return response()->json($workflow->fresh());
    }

    public function publish(Request $request, Workflow $workflow, WorkflowPublisher $publisher): JsonResponse
    {
        $this->auth($request, $workflow->organization_id);
        $data = $request->validate([
            'steps' => 'required|array|min:1|max:100',
            'steps.*.type' => 'required|string',
            'steps.*.key' => 'required|string|max:80',
            'steps.*.config' => 'array',
            'steps.*.retry' => 'array',
        ]);
        return response()->json($publisher->publish($workflow, $data), 201);
    }

    public function trigger(Request $request, Workflow $workflow, TriggerWorkflow $trigger): JsonResponse
    {
        $this->auth($request, $workflow->organization_id);
        $key = $request->header('Idempotency-Key') ?: Str::uuid()->toString();
        try {
            $execution = $trigger->trigger($workflow, 'manual', $key, $request->input('context', []));
            return response()->json($execution, 202);
        } catch (DomainException $e) {
            return response()->json(['error' => ['code' => $e->getMessage()]], 429);
        }
    }

    private function auth(Request $request, string $organization): void
    {
        abort_unless($request->user()->organizations()->whereKey($organization)->exists(), 403);
    }
}
