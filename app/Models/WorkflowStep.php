<?php
namespace App\Models;use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;
final class WorkflowStep extends Model {use HasUuids;protected $guarded=[];protected function casts():array{return ['config_json'=>'array','retry_policy_json'=>'array'];}}
