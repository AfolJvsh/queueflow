<?php
namespace App\Domain\Workflow\Handlers;
use App\Domain\Workflow\{ActionHandler,ActionResult};use InvalidArgumentException;
final class DelayAction implements ActionHandler {public function type():string{return 'delay';}public function validateConfig(array $config):void{if(($config['seconds']??0)<1||$config['seconds']>86400)throw new InvalidArgumentException('seconds must be 1..86400');}public function execute(array $config,array $context,string $idempotencyKey):ActionResult{$this->validateConfig($config);return new ActionResult([], (int)$config['seconds']);}}
