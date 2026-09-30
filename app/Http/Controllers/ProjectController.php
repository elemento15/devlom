<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Project::query()
            ->with('client:id,name')
            ->orderBy('name')
            ->paginate(10));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:projects,name'],
            'alias' => ['required', 'string', 'max:4', 'unique:projects,alias'],
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('active', true),
            ],
        ]);

        return response()->json(Project::create($data)->load('client:id,name'), 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $project->update($request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('projects', 'name')->ignore($project->id)],
        ]));

        return response()->json($project->refresh()->load('client:id,name'));
    }

    public function toggle(Project $project): JsonResponse
    {
        $project->update(['active' => ! $project->active]);

        return response()->json($project->load('client:id,name'));
    }
}
