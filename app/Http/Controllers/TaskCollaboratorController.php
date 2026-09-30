<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskCollaboratorController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'collaborator_id' => ['required', 'exists:collaborators,id'],
            'hours' => ['required', 'numeric', 'gt:0', 'max:120'],
        ]);

        $entry = DB::transaction(function () use ($data) {
            $task = Task::query()->whereKey($data['task_id'])->lockForUpdate()->firstOrFail();
            if ($task->status()->where('code', 'FIN')->exists()) {
                throw ValidationException::withMessages([
                    'task_id' => ['Finished tasks cannot receive more time entries.'],
                ]);
            }

            $collaborator = Collaborator::query()
                ->whereKey($data['collaborator_id'])
                ->where('active', true)
                ->first();

            if (! $collaborator) {
                throw ValidationException::withMessages([
                    'collaborator_id' => ['Choose an active collaborator.'],
                ]);
            }

            $hours = (float) $data['hours'];

            return $task->timeEntries()->create([
                'collaborator_id' => $collaborator->id,
                'hours' => $hours,
                'total' => round($hours * (float) $collaborator->price, 2),
            ]);
        });

        return response()->json($entry->load('collaborator:id,name'), 201);
    }
}
