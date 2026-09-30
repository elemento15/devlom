<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCollaborator extends Model
{
    public $timestamps = false;

    protected $fillable = ['task_id', 'collaborator_id', 'hours', 'total'];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }
}
