<?php
namespace App\Domain\Workflow;
final readonly class ActionResult {public function __construct(public array $output=[],public ?int $delaySeconds=null,public bool $skipNext=false) {}}
