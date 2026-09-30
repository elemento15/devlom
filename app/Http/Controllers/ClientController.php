<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Client::query()->orderBy('name')->paginate(10));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'rfc' => ['required', 'string', 'max:13'],
        ]);

        return response()->json(Client::create($data), 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $client->update($request->validate([
            'name' => ['required', 'string', 'max:50'],
            'rfc' => ['required', 'string', 'max:13'],
        ]));

        return response()->json($client->refresh());
    }

    public function toggle(Client $client): JsonResponse
    {
        $client->update(['active' => ! $client->active]);

        return response()->json($client);
    }
}
