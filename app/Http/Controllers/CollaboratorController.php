<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaboratorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Collaborator::query()->orderBy('name')->paginate(10));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:1000'],
        ]);

        return response()->json(Collaborator::create($data), 201);
    }

    public function update(Request $request, Collaborator $collaborator): JsonResponse
    {
        $collaborator->update($request->validate([
            'name' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:1000'],
        ]));

        return response()->json($collaborator->refresh());
    }

    public function toggle(Collaborator $collaborator): JsonResponse
    {
        $collaborator->update(['active' => ! $collaborator->active]);

        return response()->json($collaborator);
    }
}
