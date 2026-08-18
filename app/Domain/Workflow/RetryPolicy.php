<?php
namespace App\Domain\Workflow;

final class RetryPolicy
{
    /** @param array<string,mixed> $policy */
    public function decide(int $attempt, string $classification, array $policy, ?int $retryAfter=null, ?int $jitterSeed=null): RetryDecision
    {
        $max=max(1,(int)($policy['max_attempts']??3));
        if($attempt >= $max || in_array($classification,['configuration','authentication','validation'],true)) return new RetryDecision(false,0,$classification);
        if($classification==='rate_limit' && $retryAfter!==null) return new RetryDecision(true,max(1,$retryAfter),$classification);
        $base=max(1,(int)($policy['base_delay_seconds']??5)); $mode=$policy['mode']??'exponential';
        $delay=$mode==='fixed'?$base:min((int)($policy['max_delay_seconds']??300),$base*(2**max(0,$attempt-1)));
        if(($policy['jitter']??true) && $delay>1){$seed=$jitterSeed??random_int(0,PHP_INT_MAX);$spread=max(1,(int)floor($delay*.2));$delay=max(1,$delay + (($seed % ($spread*2+1))-$spread));}
        return new RetryDecision(true,$delay,$classification);
    }
}
