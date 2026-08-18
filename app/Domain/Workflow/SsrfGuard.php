<?php
namespace App\Domain\Workflow;
use InvalidArgumentException;
final class SsrfGuard
{
 public function assertPublicHttpUrl(string $url):void { $p=parse_url($url);if(!$p||!in_array($p['scheme']??'',['http','https'],true)||empty($p['host']))throw new InvalidArgumentException('Only absolute HTTP(S) URLs are allowed');$host=strtolower($p['host']);if($host==='localhost'||str_ends_with($host,'.local')||$host==='169.254.169.254')throw new InvalidArgumentException('Private or metadata endpoint blocked');$ips=gethostbynamel($host)?:[];foreach($ips as $ip)if(!$this->isPublic($ip))throw new InvalidArgumentException('Resolved address is not public'); }
 private function isPublic(string $ip):bool{return filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)!==false;}
}
