<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * OpenAPI Documentation Controller
 * Serves as the central point for Swagger/OpenAPI annotations
 */
class OpenApiController extends Controller
{
    /**
     * Redirect to Swagger UI
     */
    public function index(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->to('/api/documentation');
    }
}
