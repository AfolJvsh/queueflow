<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class WorkflowVersion extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['definition_json' => 'array', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if ($version->getOriginal('published_at') !== null) {
                throw new LogicException('Published workflow versions are immutable. Create a new version instead.');
            }
        });

        static::deleting(function (self $version): void {
            if ($version->published_at !== null) {
                throw new LogicException('Published workflow versions cannot be deleted.');
            }
        });
    }

    public function workflow() { return $this->belongsTo(Workflow::class); }

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('position');
    }
}
