<?php

namespace App\Models;

use App\TaskOccurrenceStatus;
use App\TaskPriority;
use Database\Factories\TaskOccurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'user_id',
    'occurrence_date',
    'due_at',
    'title',
    'description',
    'priority',
    'status',
    'started_at',
    'completed_at',
    'employee_note',
    'viewed_at',
])]
class TaskOccurrence extends Model
{
    /** @use HasFactory<TaskOccurrenceFactory> */
    use HasFactory;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('due_at', '<', now())
            ->whereNotIn('status', [
                TaskOccurrenceStatus::Completed->value,
                TaskOccurrenceStatus::Cancelled->value,
            ]);
    }

    public function isOverdue(): bool
    {
        return $this->due_at->isPast()
            && ! in_array($this->status, [
                TaskOccurrenceStatus::Completed,
                TaskOccurrenceStatus::Cancelled,
            ], true);
    }

    protected function casts(): array
    {
        return [
            'occurrence_date' => 'date',
            'due_at' => 'datetime',
            'priority' => TaskPriority::class,
            'status' => TaskOccurrenceStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'viewed_at' => 'datetime',
        ];
    }
}
