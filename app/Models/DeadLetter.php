<?php
namespace App\Models;use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;final class DeadLetter extends Model{use HasUuids;public $timestamps=false;protected $guarded=[];protected function casts():array{return ['error_metadata_json'=>'array','created_at'=>'datetime','resolved_at'=>'datetime'];}}
