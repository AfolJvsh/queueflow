<?php
namespace App\Services;
final class TemplateRenderer
{
 public function render(mixed $value,array $context):mixed{if(is_array($value))return array_map(fn($v)=>$this->render($v,$context),$value);if(!is_string($value))return $value;return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/',fn($m)=>(string)data_get($context,$m[1],''),$value)??$value;}
}
