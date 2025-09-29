<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ProjectAgentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication routes (for SPA)
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Projects
    Route::get('/projects', [App\Http\Controllers\Api\ProjectController::class, 'index']);
    Route::get('/projects/{project}', [App\Http\Controllers\Api\ProjectController::class, 'show']);

    // Project Tasks
    Route::get('/projects/{project}/tasks', [App\Http\Controllers\Api\TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [App\Http\Controllers\Api\TaskController::class, 'store']);

    // Project Messages
    Route::get('/projects/{project}/messages', [App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::post('/projects/{project}/messages', [App\Http\Controllers\Api\MessageController::class, 'store']);

    // Project Agent endpoints - rate limited
    Route::prefix('projects/{project}/agent')->name('projects.agent.')->middleware('agent.rate_limit')->group(function () {
        Route::post('/threads', [ProjectAgentController::class, 'createThread']);
        Route::get('/threads', [ProjectAgentController::class, 'getThreads']);
    });

    // Agent Thread endpoints - rate limited
    Route::prefix('agent/threads/{thread}')->name('agent.threads.')->middleware('agent.rate_limit')->group(function () {
        Route::get('/', [AgentController::class, 'getThread']);
        Route::get('/messages', [AgentController::class, 'getMessages']);
        Route::post('/messages', [AgentController::class, 'sendMessage']); // Main rate-limited endpoint
        Route::get('/stream', [AgentController::class, 'stream'])->name('stream');
        Route::post('/cancel', [AgentController::class, 'cancel']);
    });

    // Project and Workspace Summaries
    Route::get('/projects/{project}/summaries', [App\Http\Controllers\Api\SummaryController::class, 'projectSummaries']);
    Route::get('/workspaces/{workspace}/summaries', [App\Http\Controllers\Api\SummaryController::class, 'workspaceSummaries']);
});
