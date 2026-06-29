<?php

use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/api');
});

// Serve public storage files (images, etc.)
Route::get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '^[a-zA-Z0-9_\-/]+\.[a-zA-Z0-9]+$');
