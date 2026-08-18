<?php
namespace App\Domain\Workflow\Handlers;
use App\Domain\Workflow\{ActionHandler,ActionResult};use InvalidArgumentException;
final class StoreValueAction implements ActionHandler {public function type():string{return 'store_value';}public function validateConfig(array $config):void{if(empty($config['key']))throw new InvalidArgumentException('key is required');}public function execute(array $config,array $context,string $idempotencyKey):ActionResult{$this->validateConfig($config);return new ActionResult([$config['key']=>$config['value']??($context[$config['from']??'']??null)]);}}
