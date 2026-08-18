<?php

namespace App\Services;

use App\Domain\Workflow\ActionRegistry;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\WorkflowVersion;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class WorkflowPublisher
{
    public function __construct(private ActionRegistry $actions) {}

    public function publish(Workflow $workflow, array $definition): WorkflowVersion
    {
        return DB::transaction(function () use ($workflow, $definition) {
            $lockedWorkflow = Workflow::lockForUpdate()->findOrFail($workflow->id);
            $steps = array_values($definition['steps'] ?? []);
            $keys = array_map(fn (array $step, int $index) => $step['key'] ?? "step_{$index}", $steps, array_keys($steps));
            if (count($keys) !== count(array_unique($keys))) {
                throw new InvalidArgumentException('Workflow step keys must be unique.');
            }
            foreach ($steps as $step) {
                $this->actions->get($step['type'])->validateConfig($step['config'] ?? []);
            }

            $versionNo = (int) $lockedWorkflow->versions()->max('version_number') + 1;
            $version = WorkflowVersion::create([
                'workflow_id' => $lockedWorkflow->id,
                'version_number' => $versionNo,
                'definition_json' => $definition,
                'published_at' => now(),
            ]);
            foreach ($steps as $index => $step) {
                WorkflowStep::create([
                    'workflow_version_id' => $version->id,
                    'key' => $step['key'] ?? "step_{$index}",
                    'type' => $step['type'],
                    'position' => $index,
                    'config_json' => $step['config'] ?? [],
                    'retry_policy_json' => $step['retry'] ?? [
                        'max_attempts' => 3,
                        'mode' => 'exponential',
                        'base_delay_seconds' => 5,
                        'jitter' => true,
                    ],
                ]);
            }
            $lockedWorkflow->update(['current_version_id' => $version->id, 'status' => 'active']);
            return $version;
        }, 3);
    }
}
