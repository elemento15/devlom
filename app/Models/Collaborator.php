<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collaborator extends Model
{
    protected $fillable = ['name', 'price', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function taskEntries(): HasMany
    {
        return $this->hasMany(TaskCollaborator::class);
    }
}
