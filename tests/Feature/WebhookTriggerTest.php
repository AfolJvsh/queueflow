<?php

namespace Tests\Feature;

use App\Domain\Workflow\WebhookSignature;
use App\Jobs\ExecuteWorkflowStep;
use App\Models\WebhookEndpoint;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

final class WebhookTriggerTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    public function test_signed_webhook_accepts_once_and_rejects_tampering(): void
    {
        Queue::fake();
        [$user, $organization] = $this->tenant('hook');
        $this->actingAsTenant($user);
        $workflow = Workflow::create(['organization_id' => $organization->id, 'name' => 'Hook', 'slug' => 'hook', 'status' => 'draft']);
        $this->postJson("/api/workflows/{$workflow->id}/publish", [
            'steps' => [['key' => 'store', 'type' => 'store_value', 'config' => ['key' => 'received', 'value' => true]]],
        ])->assertCreated();

        $created = $this->postJson("/api/workflows/{$workflow->id}/webhook")->assertCreated();
        $endpoint = WebhookEndpoint::findOrFail($created->json('endpoint_id'));
        $secret = $created->json('secret');
        $payload = '{"order_id":42}'; $timestamp = time();
        $signature = (new WebhookSignature())->sign($payload, $secret, $timestamp);

        $headers = ['X-QueueFlow-Timestamp' => (string) $timestamp, 'X-QueueFlow-Signature' => $signature, 'X-QueueFlow-Delivery' => 'delivery-42', 'Content-Type' => 'application/json'];
        $this->call('POST', '/api/hooks/'.$endpoint->id, [], [], [], $this->transformHeadersToServerVars($headers), $payload)->assertStatus(202);
        $this->call('POST', '/api/hooks/'.$endpoint->id, [], [], [], $this->transformHeadersToServerVars($headers), $payload)->assertStatus(202);
        Queue::assertPushed(ExecuteWorkflowStep::class, 1);

        $bad = [...$headers, 'X-QueueFlow-Signature' => str_repeat('0', 64)];
        $this->call('POST', '/api/hooks/'.$endpoint->id, [], [], [], $this->transformHeadersToServerVars($bad), $payload)->assertUnauthorized();
    }
}
