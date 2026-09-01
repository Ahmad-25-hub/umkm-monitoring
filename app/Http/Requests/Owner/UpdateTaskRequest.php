<?php

namespace App\Http\Requests\Owner;

use App\Models\Task;
use Illuminate\Support\Facades\Gate;

class UpdateTaskRequest extends StoreTaskRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        if (! $task instanceof Task) {
            return false;
        }

        Gate::forUser($this->user())->authorize('update', $task);

        return true;
    }
}
