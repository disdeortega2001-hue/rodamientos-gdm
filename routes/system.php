<?php

use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

// The installer runs without the web middleware: it must respond before the sessions table exists.
Route::get('/instalar', [SystemController::class, 'installForm']);
Route::post('/instalar', [SystemController::class, 'install']);
