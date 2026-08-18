<?php

use App\Http\Controllers\{OperationsController, TriggerController, WorkflowController, WorkflowOperationsController};
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['ok' => true, 'service' => 'queueflow']);
Route::prefix('auth')->middleware('throttle:30,1')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Auth\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->get('/me', [\App\Http\Controllers\Auth\AuthController::class, 'me']);
    Route::middleware('auth:sanctum')->delete('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout']);
});
Route::post('/hooks/{endpoint}', [TriggerController::class, 'webhook'])->middleware('throttle:120,1');
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/operations/action-catalog', [WorkflowController::class, 'catalog']);
    Route::get('/workflows', [WorkflowController::class, 'index']);
    Route::post('/workflows', [WorkflowController::class, 'store']);
    Route::get('/workflows/{workflow}', [WorkflowController::class, 'show']);
    Route::patch('/workflows/{workflow}', [WorkflowController::class, 'update']);
    Route::post('/workflows/{workflow}/publish', [WorkflowController::class, 'publish']);
    Route::post('/workflows/{workflow}/trigger', [WorkflowController::class, 'trigger']);
    Route::get('/workflows/{workflow}/executions', [WorkflowController::class, 'executions']);
    Route::get('/workflows/{workflow}/dead-letters', [WorkflowController::class, 'deadLetters']);
    Route::post('/workflows/{workflow}/webhook', [WorkflowOperationsController::class, 'webhook']);
    Route::post('/workflows/{workflow}/schedules', [WorkflowOperationsController::class, 'schedule']);
    Route::delete('/workflows/{workflow}/schedules/{schedule}', [WorkflowOperationsController::class, 'deleteSchedule']);
    Route::get('/executions/{execution}', [WorkflowOperationsController::class, 'execution']);
    Route::post('/executions/{execution}/retry', [WorkflowOperationsController::class, 'retry']);
    Route::post('/executions/{execution}/cancel', [WorkflowOperationsController::class, 'cancel']);
    Route::get('/operations/metrics', [OperationsController::class, 'metrics']);
    Route::get('/operations/organizations/{organization}', [OperationsController::class, 'organization']);
    Route::patch('/operations/organizations/{organization}', [OperationsController::class, 'updateOrganization']);
    Route::get('/operations/secrets', [OperationsController::class, 'secrets']);
    Route::post('/operations/secrets', [OperationsController::class, 'saveSecret']);
    Route::delete('/operations/secrets/{secret}', [OperationsController::class, 'deleteSecret']);
});
