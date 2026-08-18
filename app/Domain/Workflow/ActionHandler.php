<?php
namespace App\Domain\Workflow;
interface ActionHandler {public function type():string;public function validateConfig(array $config):void;public function execute(array $config,array $context,string $idempotencyKey):ActionResult;}
