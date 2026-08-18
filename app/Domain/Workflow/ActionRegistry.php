<?php
namespace App\Domain\Workflow;
use InvalidArgumentException;
final class ActionRegistry {/** @var array<string,ActionHandler> */private array $handlers=[];public function __construct(iterable $handlers=[]){foreach($handlers as $h)$this->register($h);}public function register(ActionHandler $h):void{$this->handlers[$h->type()]=$h;}public function get(string $type):ActionHandler{return $this->handlers[$type]??throw new InvalidArgumentException("Unknown action type: {$type}");}public function types():array{return array_keys($this->handlers);}}
