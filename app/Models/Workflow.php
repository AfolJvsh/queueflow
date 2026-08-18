<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Workflow extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function versions() { return $this->hasMany(WorkflowVersion::class); }
    public function currentVersion() { return $this->belongsTo(WorkflowVersion::class, 'current_version_id'); }
    public function webhookEndpoint() { return $this->hasOne(WebhookEndpoint::class); }
    public function schedules() { return $this->hasMany(ScheduledTrigger::class); }

    public function executions()
    {
        return WorkflowExecution::query()->whereIn('workflow_version_id', $this->versions()->select('id'));
    }
}
