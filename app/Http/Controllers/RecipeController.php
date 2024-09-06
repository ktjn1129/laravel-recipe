<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RecipeService;
use App\Http\Requests\RecipeCreateRequest;
use App\Http\Requests\RecipeUpdateRequest;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    protected $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function home()
    {
        $data = $this->recipeService->getHomePageData();

        return view('home', $data);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        $recipes = $this->recipeService->getRecipesWithFilters($filters);
        $categories = $this->recipeService->getCategories();

        return view('recipes.index', compact('recipes', 'categories', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = $this->recipeService->getCategories();

        return view('recipes.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RecipeCreateRequest $request)
    {
        $uuid = $this->recipeService->createRecipe($request->all());

        flash()->success('レシピを投稿しました！');

        return redirect()->route('recipe.show', ['id' => $uuid]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $recipe = $this->recipeService->getRecipeDetails($id);

        $this->recipeService->incrementRecipeViews($id);

        $is_my_recipe = false;
        if(Auth::check() && (Auth::id() === $recipe['user_id'])) {
            $is_my_recipe = true;
        }
        $is_reviewed = false;
        if(Auth::check()) {
            $is_reviewed = $recipe->reviews->contains('user_id', Auth::id());
        }

        return view('recipes.show', compact('recipe', 'is_my_recipe', 'is_reviewed'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $recipe = $this->recipeService->getRecipeDetails($id);
        $categories = $this->recipeService->getCategories();


        if(!Auth::check() || (Auth::id() !== $recipe['user_id'])) {
            abort(403);
        }

        return view('recipes.edit', compact('recipe', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RecipeUpdateRequest $request, string $id)
    {
        $data = $request->except(['_token', '_method' ]);
        $this->recipeService->updateRecipe($id, $data);

        return redirect()->route('recipe.show', ['id' => $id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->recipeService->deleteRecipe($id);

        flash()->warning('レシピを削除しました！');

        return redirect()->route('home');
    }
}
