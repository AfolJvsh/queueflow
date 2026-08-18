<?php

namespace App\Domain\Workflow;

use DomainException;

final class ExecutionStateMachine
{
    private const STEP = [
        'pending' => ['running', 'skipped'],
        'running' => ['succeeded', 'retry_wait', 'failed'],
        'retry_wait' => ['pending', 'failed'],
        'succeeded' => [],
        'failed' => ['pending'],
        'skipped' => [],
    ];

    private const FLOW = [
        'pending' => ['running', 'cancelled'],
        'running' => ['succeeded', 'failed', 'cancelled'],
        'failed' => ['running'],
        'succeeded' => [],
        'cancelled' => [],
    ];

    public function assertStep(StepExecutionStatus $from, StepExecutionStatus $to): void
    {
        if (! in_array($to->value, self::STEP[$from->value] ?? [], true)) {
            throw new DomainException("Invalid step transition {$from->value} -> {$to->value}");
        }
    }

    public function assertWorkflow(WorkflowExecutionStatus $from, WorkflowExecutionStatus $to): void
    {
        if (! in_array($to->value, self::FLOW[$from->value] ?? [], true)) {
            throw new DomainException("Invalid workflow transition {$from->value} -> {$to->value}");
        }
    }
}
