<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Task::query()
            ->with(['project:id,name,alias', 'status:id,code,name'])
            ->withSum('timeEntries as total_hours', 'hours')
            ->withSum('timeEntries as total_cost', 'total')
            ->withCount('timeEntries')
            ->orderByDesc('folio')
            ->paginate(10));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:100'],
            'project_id' => ['required', Rule::exists('projects', 'id')->where('active', true)],
        ]);

        $task = DB::transaction(function () use ($data): Task {
            $project = Project::query()->whereKey($data['project_id'])->lockForUpdate()->firstOrFail();
            $folio = Folio::firstOrCreate(['project_id' => $project->id], ['folio' => 1]);
            $number = $folio->folio;
            $taskFolio = $project->alias.'-'.$number;
            if (strlen($taskFolio) > 10) {
                throw ValidationException::withMessages([
                    'project_id' => ['This project has reached the maximum number of task folios.'],
                ]);
            }
            $folio->increment('folio');

            $status = Status::query()->where('code', 'PRC')->firstOrFail();

            return Task::create([
                'project_id' => $project->id,
                'folio' => $taskFolio,
                'description' => $data['description'],
                'status_id' => $status->id,
            ]);
        });

        return response()->json($task->load(['project:id,name,alias', 'status:id,code,name']), 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $task->update($request->validate([
            'description' => ['required', 'string', 'max:100'],
        ]));

        return response()->json($task->refresh()->load(['project:id,name,alias', 'status:id,code,name']));
    }

    public function destroy(Task $task): JsonResponse
    {
        $deleted = DB::transaction(function () use ($task): bool {
            $task = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($task->timeEntries()->exists()) {
                return false;
            }

            $task->delete();

            return true;
        });
        if (! $deleted) {
            return response()->json(['message' => 'Tasks with time entries cannot be deleted.'], 409);
        }

        return response()->json(['message' => 'Task deleted.']);
    }

    public function finish(Task $task): JsonResponse
    {
        $task = DB::transaction(function () use ($task): Task {
            $task = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $task->update(['status_id' => Status::query()->where('code', 'FIN')->firstOrFail()->id]);

            return $task;
        });

        return response()->json($task->refresh()->load(['project:id,name,alias', 'status:id,code,name']));
    }
}
