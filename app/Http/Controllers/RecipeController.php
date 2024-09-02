<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Step;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function home()
    {
        $recipes = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.created_at', 'desc')
            ->limit(3)
            ->get();

        $popular = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->orderBy('recipes.views', 'desc')
            ->limit(2)
            ->get();

        return view('home', compact('recipes', 'popular'));
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->all();

        $query = Recipe::query()->select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name', \DB::raw('AVG(reviews.rating) as rating'))
            ->join('users', 'users.id', '=', 'recipes.user_id')
            ->leftJoin('reviews', 'reviews.recipe_id', '=', 'recipes.id')
            ->groupBy('recipes.id')
            ->orderBy('recipes.created_at', 'desc');

        if(!empty($filters['categories'])) {
            $query->whereIn('recipes.category_id', $filters['categories']);
        }

        if(!empty($filters['rating'])) {
            $query->havingRaw('AVG(reviews.rating) >= ?', [$filters['rating']])
                ->orderBy('rating', 'desc');
        }

        if(!empty($filters['title'])) {
            $query->where('recipes.title', 'like', '%'.$filters['title'].'%');
        }

        $recipes = $query->paginate(5);

        $categories = Category::all();

        return view('recipes.index', compact('recipes', 'categories', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();

        return view('recipes.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $posts = $request->all();
        $uuid = Str::uuid()->toString();

        $image = $request->file('image');
        // S3に画像をアップロード
        $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
        // S3のURLを取得
        $url = Storage::disk('s3')->url($path);

        Recipe::insert([
            'id' => $uuid,
            'title' => $posts['title'],
            'description' => $posts['description'],
            'category_id' => $posts['category'],
            'image' => $url,
            'user_id' => Auth::id(),
        ]);

        $steps = [];
        foreach($posts['steps'] as $key => $step) {
            $steps[$key] = [
                'recipe_id' => 'uuid',
                'step_number' => $key + 1,
                'description' => $step
            ];
        }
        Step::insert($steps);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $recipe = Recipe::with(['ingredients', 'steps', 'reviews', 'user'])
            ->where('recipes.id', $id)
            ->first();

        $recipe_record = Recipe::find($id);
        $recipe_record->increment('views');

        return view('recipes.show', compact('recipe'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
