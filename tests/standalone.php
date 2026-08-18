<?php
foreach (['WorkflowExecutionStatus','StepExecutionStatus','RetryDecision','RetryPolicy','ExecutionStateMachine','ActionResult','ActionHandler','ActionRegistry','WebhookSignature','SsrfGuard'] as $f) require __DIR__."/../app/Domain/Workflow/{$f}.php";
foreach (['TransformAction','StoreValueAction','DelayAction','ConditionalAction'] as $f) require __DIR__."/../app/Domain/Workflow/Handlers/{$f}.php";

use App\Domain\Workflow\{ActionRegistry,ExecutionStateMachine,RetryPolicy,SsrfGuard,StepExecutionStatus,WebhookSignature};
use App\Domain\Workflow\Handlers\{ConditionalAction,DelayAction,StoreValueAction,TransformAction};

function ok(bool $value, string $message): void { if (! $value) throw new RuntimeException($message); }

$retry = new RetryPolicy;
$d = $retry->decide(1, 'transient', ['max_attempts'=>4,'mode'=>'exponential','base_delay_seconds'=>5,'jitter'=>false]); ok($d->retry && $d->delaySeconds===5, 'first retry');
$d = $retry->decide(2, 'transient', ['max_attempts'=>4,'mode'=>'exponential','base_delay_seconds'=>5,'jitter'=>false]); ok($d->delaySeconds===10, 'exponential retry');
$d = $retry->decide(1, 'rate_limit', ['max_attempts'=>4], 17); ok($d->retry && $d->delaySeconds===17, 'retry-after honored');
foreach (['configuration','authentication','validation'] as $classification) ok(! $retry->decide(1, $classification, ['max_attempts'=>4])->retry, "{$classification} terminal");
ok(! $retry->decide(4, 'transient', ['max_attempts'=>4])->retry, 'max attempt terminal');

$sm = new ExecutionStateMachine; $sm->assertStep(StepExecutionStatus::Pending, StepExecutionStatus::Running);
try { $sm->assertStep(StepExecutionStatus::Succeeded, StepExecutionStatus::Running); ok(false, 'illegal transition'); } catch (DomainException) {}

$registry = new ActionRegistry([new TransformAction, new StoreValueAction, new DelayAction, new ConditionalAction]);
$result = $registry->get('transform')->execute(['operations'=>[['op'=>'upper','key'=>'name']]],[ 'name'=>'josh'],'x'); ok($result->output['name']==='JOSH','transform');
$result = $registry->get('conditional')->execute(['field'=>'amount','operator'=>'gte','value'=>10], ['amount'=>5], 'x'); ok($result->skipNext, 'conditional skips next on false');
$result = $registry->get('delay')->execute(['seconds'=>3], [], 'x'); ok($result->delaySeconds===3, 'delay does not sleep');

$signature = new WebhookSignature; $timestamp=time(); $sig=$signature->sign('abc','secret',$timestamp); ok($signature->verify('abc','secret',$timestamp,$sig),'signature'); ok(! $signature->verify('tampered','secret',$timestamp,$sig),'tamper rejected');
$guard = new SsrfGuard; try { $guard->assertPublicHttpUrl('http://localhost/admin'); ok(false,'localhost SSRF'); } catch (InvalidArgumentException) {}

echo "QueueFlow standalone domain tests passed\n";
