<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\OptionsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskCollaboratorController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app');

Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/options', OptionsController::class);

        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::patch('/clients/{client}/toggle', [ClientController::class, 'toggle']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::patch('/projects/{project}/toggle', [ProjectController::class, 'toggle']);

        Route::get('/collaborators', [CollaboratorController::class, 'index']);
        Route::post('/collaborators', [CollaboratorController::class, 'store']);
        Route::put('/collaborators/{collaborator}', [CollaboratorController::class, 'update']);
        Route::patch('/collaborators/{collaborator}/toggle', [CollaboratorController::class, 'toggle']);

        Route::get('/tasks', [TaskController::class, 'index']);
        Route::post('/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::patch('/tasks/{task}/finish', [TaskController::class, 'finish']);
        Route::post('/time-entries', [TaskCollaboratorController::class, 'store']);
    });
});

Route::view('/{any}', 'app')->where('any', '^(?!api).*$');
