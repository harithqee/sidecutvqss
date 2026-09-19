<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{BarberController, QueueTicketController, MessageTemplateController};


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('barbers')->group(function () {
    Route::get('/', [BarberController::class, 'index']);
    Route::post('/', [BarberController::class, 'store']);
    Route::put('/{barber}', [BarberController::class, 'update']);
    Route::patch('/{barber}/toggle-active', [BarberController::class, 'toggleActive']);
    Route::delete('/{barber}', [BarberController::class, 'destroy']);
});

Route::prefix('queue')->group(function () {
    Route::get('/', [QueueTicketController::class, 'index']);
    Route::get('/history', [QueueTicketController::class, 'history']);
    Route::post('/', [QueueTicketController::class, 'store']);
    Route::patch('/{ticket}/status', [QueueTicketController::class, 'updateStatus']);
    Route::post('/{ticket}/sms', [QueueTicketController::class, 'sendSms']);
});

Route::apiResource('message-templates', MessageTemplateController::class)
    ->except(['show']);
Route::patch('message-templates/{messageTemplate}/toggle-active', [MessageTemplateController::class, 'toggleActive']);