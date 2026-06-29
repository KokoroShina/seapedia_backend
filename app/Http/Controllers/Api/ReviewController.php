<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\AppReview;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /**
     * GET /api/reviews
     * List semua review (public)
     */
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

    /**
     * POST /api/reviews
     * Buat review baru (protected, semua role boleh akses)
     *
     * Input: rating (1-5), comment
     * reviewer_name diambil otomatis dari username user login
     * comment disanitasi dengan strip_tags() untuk prevent XSS
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $review = AppReview::create([
            'reviewer_name' => $request->user()->username,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil ditambahkan',
            'data' => $review,
        ], 201);
    }
}