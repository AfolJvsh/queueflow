<?php
namespace Tests\Unit;use App\Domain\Workflow\RetryPolicy;use PHPUnit\Framework\TestCase;
final class RetryPolicyTest extends TestCase {public function test_exponential_backoff_is_bounded():void{$p=new RetryPolicy;$policy=['max_attempts'=>5,'base_delay_seconds'=>10,'max_delay_seconds'=>25,'mode'=>'exponential','jitter'=>false];$this->assertSame(10,$p->decide(1,'transient',$policy)->delaySeconds);$this->assertSame(20,$p->decide(2,'transient',$policy)->delaySeconds);$this->assertSame(25,$p->decide(3,'transient',$policy)->delaySeconds);}}
