<?php

use App\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AgentController::class, 'show'])->name('agent.show');
Route::post('/send', [AgentController::class, 'send'])->name('agent.send');
Route::post('/reset', [AgentController::class, 'reset'])->name('agent.reset');
