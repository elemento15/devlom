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

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Task::query()
            ->with(['project:id,name,alias', 'status:id,code,name'])
            ->withSum('timeEntries as total_hours', 'hours')
            ->withSum('timeEntries as total_cost', 'total')
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
            $folio->increment('folio');

            $status = Status::query()->where('code', 'PRC')->firstOrFail();

            return Task::create([
                'project_id' => $project->id,
                'folio' => $project->alias.'-'.$number,
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
        if ($task->timeEntries()->exists()) {
            return response()->json(['message' => 'Tasks with time entries cannot be deleted.'], 409);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    public function finish(Task $task): JsonResponse
    {
        $task->update(['status_id' => Status::query()->where('code', 'FIN')->firstOrFail()->id]);

        return response()->json($task->refresh()->load(['project:id,name,alias', 'status:id,code,name']));
    }
}
