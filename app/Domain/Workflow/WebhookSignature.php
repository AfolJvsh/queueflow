<?php
namespace App\Domain\Workflow;
final class WebhookSignature {public function sign(string $payload,string $secret,int $timestamp):string{return hash_hmac('sha256',$timestamp.'.'.$payload,$secret);}public function verify(string $payload,string $secret,int $timestamp,string $signature,int $tolerance=300):bool{if(abs(time()-$timestamp)>$tolerance)return false;return hash_equals($this->sign($payload,$secret,$timestamp),$signature);}}
