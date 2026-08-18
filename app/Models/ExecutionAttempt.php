<?php
namespace App\Models;use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;
final class ExecutionAttempt extends Model {use HasUuids;protected $guarded=[];protected function casts():array{return ['error_metadata_json'=>'array','started_at'=>'datetime','finished_at'=>'datetime'];}}
