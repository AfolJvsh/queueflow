<?php
namespace App\Domain\Workflow;use RuntimeException;final class ActionFailure extends RuntimeException{public function __construct(string $message,public readonly string $classification='transient',public readonly ?int $retryAfterSeconds=null){parent::__construct($message);}}
