<?php

namespace App\Services;

use App\Repositories\ReviewRepository;

class ReviewService
{
    protected $reviewRepository;

    public function __construct(ReviewRepository $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function createReview(array $data, string $recipeId, string $userId)
    {
        $reviewData = [
            'recipe_id' => $recipeId,
            'user_id' => $userId,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ];

        $this->reviewRepository->createReview($reviewData);
    }
}
