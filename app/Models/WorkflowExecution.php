<?php
namespace App\Models;use App\Domain\Workflow\WorkflowExecutionStatus;use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;
final class WorkflowExecution extends Model {use HasUuids;protected $guarded=[];protected function casts():array{return ['status'=>WorkflowExecutionStatus::class,'context_json'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];}public function steps(){return $this->hasMany(StepExecution::class);}public function version(){return $this->belongsTo(WorkflowVersion::class,'workflow_version_id');}}
