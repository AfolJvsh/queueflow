<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;
final class ScheduledTrigger extends Model{use HasUuids;protected $guarded=[];protected function casts():array{return ['next_run_at'=>'datetime'];}public function workflow(){return $this->belongsTo(Workflow::class);}}
