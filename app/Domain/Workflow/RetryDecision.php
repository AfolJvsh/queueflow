<?php
namespace App\Domain\Workflow;
final readonly class RetryDecision {public function __construct(public bool $retry,public int $delaySeconds,public string $classification) {}}
