<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Filters\RecipeFilter;
use App\Models\ContainerObject;
use App\Models\TradeskillRecipe;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $recipes = (new RecipeFilter($request))
            ->apply(TradeskillRecipe::query())
            ->orderBy('name', 'asc')
            ->paginate(50)
            ->withQueryString();

        $tradeskills = collect(config('everquest.skills.tradeskill'))->sort()->toArray();

        return view('recipes.index', [
            'tradeskills' => $tradeskills,
            'recipes' => $recipes,
            'metaTitle' => config('app.name') . ' - Recipe Search',
        ]);
    }

    public function show(TradeskillRecipe $recipe)
    {
        $tradeskills = collect(config('everquest.skills.tradeskill'))->sort()->toArray();
        $objects = collect(config('everquest.object_containers'))->toArray();

        $container = $recipe->containerEntries();

        // we need to check if the container is a world object
        // and if so, get the icon for it
        foreach ($container as $val) {
            if (!$val->item && array_key_exists($val->item_id, $objects)) {
                $val->custom_container_name = $objects[$val->item_id];
                $val->custom_container_icon = ContainerObject::where('type', $val->item_id)->value('icon');
            }
        }

        $success = $recipe->successEntries();
        $fail = $recipe->failEntries();
        $components = $recipe->componentEntriesWithFlags();

        $failCount = $fail->mapWithKeys(function ($entry) {
            return [($entry->item?->id ?? $entry->item_id) => $entry->failcount];
        })->toArray();;

        return view('recipes.show', [
            'recipe' => $recipe,
            'container' => $container,
            'success' => $success,
            'fail' => $fail,
            'components' => $components,
            'tradeskills' => $tradeskills,
            'failCount' => $failCount,
            'metaTitle' => config('app.name') . ' - Recipe: ' . ucRomanNumeral($recipe->name),
        ]);
    }
}
