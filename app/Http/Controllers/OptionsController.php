<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Collaborator;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class OptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'clients' => Client::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'collaborators' => Collaborator::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'price']),
        ]);
    }
}
