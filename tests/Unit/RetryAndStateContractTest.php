<?php
namespace Tests\Unit;

use App\Domain\Workflow\ExecutionStateMachine;
use App\Domain\Workflow\RetryPolicy;
use App\Domain\Workflow\StepExecutionStatus;
use App\Domain\Workflow\WorkflowExecutionStatus;
use DomainException;
use PHPUnit\Framework\TestCase;

final class RetryAndStateContractTest extends TestCase
{
    public function test_rate_limit_honors_retry_after(): void
    {
        $decision = (new RetryPolicy)->decide(1, 'rate_limit', [
            'max_attempts' => 4,
            'base_delay_seconds' => 2,
            'max_delay_seconds' => 60,
            'jitter' => false,
        ], retryAfter: 17);

        $this->assertTrue($decision->retry);
        $this->assertSame(17, $decision->delaySeconds);
        $this->assertSame('rate_limit', $decision->classification);
    }

    public function test_authentication_and_configuration_errors_are_terminal(): void
    {
        $policy = ['max_attempts' => 5, 'base_delay_seconds' => 1, 'jitter' => false];
        $retry = new RetryPolicy;

        $this->assertFalse($retry->decide(1, 'authentication', $policy)->retry);
        $this->assertFalse($retry->decide(1, 'configuration', $policy)->retry);
        $this->assertFalse($retry->decide(1, 'validation', $policy)->retry);
    }

    public function test_final_attempt_is_terminal_even_for_transient_failures(): void
    {
        $decision = (new RetryPolicy)->decide(3, 'transient', [
            'max_attempts' => 3,
            'base_delay_seconds' => 1,
            'jitter' => false,
        ]);

        $this->assertFalse($decision->retry);
        $this->assertSame(0, $decision->delaySeconds);
    }

    public function test_execution_state_machine_accepts_valid_and_rejects_invalid_transitions(): void
    {
        $machine = new ExecutionStateMachine;
        $machine->assertStep(StepExecutionStatus::Pending, StepExecutionStatus::Running);
        $machine->assertWorkflow(WorkflowExecutionStatus::Running, WorkflowExecutionStatus::Succeeded);

        $this->expectException(DomainException::class);
        $machine->assertWorkflow(WorkflowExecutionStatus::Succeeded, WorkflowExecutionStatus::Running);
    }
}
