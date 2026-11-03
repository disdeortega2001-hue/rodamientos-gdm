<?php

use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

// These run without the web middleware: they must respond before the sessions table exists.
Route::get('/estado', [SystemController::class, 'status']);
Route::get('/instalar', [SystemController::class, 'installForm']);
Route::post('/instalar', [SystemController::class, 'install']);
