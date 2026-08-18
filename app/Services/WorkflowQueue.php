<?php
namespace App\Services;use App\Models\Workflow;final class WorkflowQueue{public function name(Workflow $workflow):string{return match($workflow->queue_priority){'high'=>'workflows-high','low'=>'workflows-low',default=>'workflows'};}}
