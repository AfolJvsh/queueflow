<?php

namespace Tests\Feature;

use App\Jobs\ExecuteWorkflowStep;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\WorkflowVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use LogicException;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

final class WorkflowLifecycleTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    public function test_publish_creates_immutable_version_and_materialized_steps(): void
    {
        [$user, $organization] = $this->tenant('publish');
        $this->actingAsTenant($user);

        $workflowId = $this->postJson('/api/workflows', [
            'organization_id' => $organization->id,
            'name' => 'Welcome customer',
            'queue_priority' => 'high',
        ])->assertCreated()->json('id');

        $versionId = $this->postJson("/api/workflows/{$workflowId}/publish", [
            'steps' => [
                ['key' => 'remember', 'type' => 'store_value', 'config' => ['key' => 'customer_id', 'from' => 'id']],
                ['key' => 'wait', 'type' => 'delay', 'config' => ['seconds' => 1]],
            ],
        ])->assertCreated()->assertJsonPath('version_number', 1)->json('id');

        $workflow = Workflow::findOrFail($workflowId);
        self::assertSame($versionId, $workflow->current_version_id);
        self::assertSame('active', $workflow->status);
        self::assertSame(2, WorkflowVersion::findOrFail($versionId)->steps()->count());

        $this->expectException(LogicException::class);
        WorkflowVersion::findOrFail($versionId)->update(['definition_json' => ['steps' => []]]);
    }

    public function test_duplicate_manual_trigger_returns_same_execution_and_dispatches_once(): void
    {
        Queue::fake();
        [$user, $organization] = $this->tenant('idem');
        $this->actingAsTenant($user);
        $workflow = Workflow::create(['organization_id' => $organization->id, 'name' => 'Idempotent', 'slug' => 'idempotent', 'status' => 'draft']);

        $this->postJson("/api/workflows/{$workflow->id}/publish", [
            'steps' => [['key' => 'store', 'type' => 'store_value', 'config' => ['key' => 'done', 'value' => true]]],
        ])->assertCreated();

        $first = $this->withHeader('Idempotency-Key', 'same-delivery')->postJson("/api/workflows/{$workflow->id}/trigger", ['context' => ['id' => 7]])->assertStatus(202);
        $second = $this->withHeader('Idempotency-Key', 'same-delivery')->postJson("/api/workflows/{$workflow->id}/trigger", ['context' => ['id' => 7]])->assertStatus(202);

        self::assertSame($first->json('id'), $second->json('id'));
        self::assertSame(1, WorkflowExecution::count());
        Queue::assertPushed(ExecuteWorkflowStep::class, 1);
    }

    public function test_tenant_isolation_blocks_workflow_access(): void
    {
        [$user] = $this->tenant('owner');
        [, $other] = $this->tenant('other');
        $workflow = Workflow::create(['organization_id' => $other->id, 'name' => 'Private', 'slug' => 'private', 'status' => 'draft']);
        $this->actingAsTenant($user);
        $this->getJson('/api/workflows/'.$workflow->id)->assertForbidden();
    }
}
