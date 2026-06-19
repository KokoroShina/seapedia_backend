<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppReview extends Model
{
    use HasFactory;

    protected $table = 'app_reviews';

    protected $fillable = [
        'reviewer_name',
        'rating',
        'comment',
        'created_at',
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
    ];
}
