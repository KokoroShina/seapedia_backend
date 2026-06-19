<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppReview;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = AppReview::select('reviewer_name', 'rating', 'comment', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar review berhasil diambil',
            'data' => $reviews
        ]);
    }
}
