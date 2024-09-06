<?php

namespace App\Repositories;

use App\Models\Recipe;
use App\Models\Step;
use App\Models\Ingredient;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class RecipeRepository
{
    public function getLatestRecipes(int $limit)
    {
        return Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPopularRecipes(int $limit)
    {
        return Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.views', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getFilteredRecipes(array $filters)
    {
        $query = Recipe::query()->select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name', \DB::raw('AVG(reviews.rating) as rating'))
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->leftJoin('reviews', 'reviews.recipe_id', '=', 'recipes.id')
            ->groupBy('recipes.id')
            ->orderBy('recipes.created_at', 'desc');

        if (!empty($filters['categories'])) {
            $query->whereIn('recipes.category_id', $filters['categories']);
        }

        if (!empty($filters['rating'])) {
            $query->havingRaw('AVG(reviews.rating) >= ?', [$filters['rating']])
                ->orderBy('rating', 'desc');
        }

        if (!empty($filters['title'])) {
            $query->where('recipes.title', 'like', '%' . $filters['title'] . '%');
        }

        return $query->paginate(5);
    }

    public function getCategories()
    {
        return Category::all();
    }

    public function createRecipe(string $uuid, array $data, string $imageUrl)
    {
        Recipe::insert([
            'id' => $uuid,
            'title' => $data['title'],
            'description' => $data['description'],
            'category_id' => $data['category'],
            'image' => $imageUrl,
            'user_id' => Auth::id(),
        ]);

        $this->createIngredients($uuid, $data['ingredients']);
        $this->createSteps($uuid, $data['steps']);
    }

    protected function createIngredients(string $recipeId, array $ingredients)
    {
        foreach ($ingredients as $ingredient) {
            Ingredient::insert([
                'recipe_id' => $recipeId,
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity']
            ]);
        }
    }

    protected function createSteps(string $recipeId, array $steps)
    {
        foreach ($steps as $index => $step) {
            Step::insert([
                'recipe_id' => $recipeId,
                'step_number' => $index + 1,
                'description' => $step
            ]);
        }
    }

    public function updateRecipe(string $id, array $data)
    {
        $update_data = collect($data)->except('ingredients', 'steps')->toArray();
        Recipe::where('id', $id)->update($update_data);

        $this->updateIngredients($id, $data['ingredients']);
        $this->updateSteps($id, $data['steps']);
    }

    protected function updateIngredients(string $recipeId, array $ingredients)
    {
        Ingredient::where('recipe_id', $recipeId)->delete();

        foreach ($ingredients as $ingredient) {
            Ingredient::insert([
                'recipe_id' => $recipeId,
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity']
            ]);
        }
    }

    protected function updateSteps(string $recipeId, array $steps)
    {
        Step::where('recipe_id', $recipeId)->delete();

        foreach ($steps as $index => $step) {
            Step::insert([
                'recipe_id' => $recipeId,
                'step_number' => $index + 1,
                'description' => $step
            ]);
        }
    }

    public function deleteRecipe(string $id)
    {
        Recipe::where('id', $id)->delete();
    }

    public function getRecipeDetails(string $id)
    {
        return Recipe::with(['ingredients', 'steps', 'reviews', 'user'])
            ->where('recipes.id', $id)
            ->first();
    }

    public function incrementViews(string $id)
    {
        $recipe = Recipe::find($id);
        $recipe->increment('views');
    }
}
