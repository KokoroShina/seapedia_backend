<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        // Prevent directory traversal
        $path = preg_replace('/\.\.+/', '', $path);
        $path = trim($path, '/');

        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    }
}
