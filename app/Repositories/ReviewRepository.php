<?php

namespace App\Repositories;

use App\Models\Review;

class ReviewRepository
{
    public function createReview(array $data)
    {
        Review::insert($data);
    }
}
