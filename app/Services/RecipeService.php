<?php

namespace App\Services;

use App\Repositories\RecipeRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RecipeService
{
    protected $recipeRepository;

    public function __construct(RecipeRepository $recipeRepository)
    {
        $this->recipeRepository = $recipeRepository;
    }

    public function getHomePageData()
    {
        $recipes = $this->recipeRepository->getLatestRecipes(3);
        $popular = $this->recipeRepository->getPopularRecipes(2);

        return compact('recipes', 'popular');
    }

    public function getRecipesWithFilters(array $filters)
    {
        return $this->recipeRepository->getFilteredRecipes($filters);
    }

    public function getCategories()
    {
        return $this->recipeRepository->getCategories();
    }

    public function createRecipe(array $data)
    {
        $uuid = Str::uuid()->toString();
        $url = $this->uploadImage($data['image']);

        try {
            DB::beginTransaction();
            $this->recipeRepository->createRecipe($uuid, $data, $url);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }

        return $uuid;
    }

    public function updateRecipe(string $id, array $data)
    {
        if (isset($data['image'])) {
            $data['image'] = $this->uploadImage($data['image']);
        }

        try {
            DB::beginTransaction();
            $this->recipeRepository->updateRecipe($id, $data);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function deleteRecipe(string $id)
    {
        $this->recipeRepository->deleteRecipe($id);
    }

    public function getRecipeDetails(string $id)
    {
        return $this->recipeRepository->getRecipeDetails($id);
    }

    public function incrementRecipeViews(string $id)
    {
        $this->recipeRepository->incrementViews($id);
    }

    protected function uploadImage($image)
    {
        $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
        return Storage::disk('s3')->url($path);
    }
}
