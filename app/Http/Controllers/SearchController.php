<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Zone;
use App\Models\Spell;
use App\Models\NpcType;
use App\Models\FactionList;
use App\Models\TradeskillRecipe;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function suggest(Request $request)
    {
        $q = $request->query('q');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $qSafe = $this->escapeLike($q);

        // create a special query for npc type names
        $qNpcs = str_replace(' ', '_', $qSafe);
        $qNpcs = str_replace('`', '-', $qNpcs);

        $results = collect();

        $results = $results
            ->merge(
                NpcType::where('name', 'like', "%{$qSafe}%")->orWhere('name', 'like', "%{$qNpcs}%")
                    ->groupBy('name')->limit(5)->get()->map(function ($npc) {
                    return [
                        'type' => 'npc',
                        'name' => $npc->clean_name,
                        'url' => route('npcs.show', $npc->id),
                        'id' => 'npc-' . $npc->id
                    ];
                })
            )->merge(
                Item::where('name', 'like', "%{$qSafe}%")->limit(10)->get()->map(function ($item) {
                    return [
                        'type' => 'item',
                        'name' => $item->Name,
                        'url' => route('items.show', $item->id),
                        'id' => 'item-' . $item->id
                    ];
                })
            )->merge(
                TradeskillRecipe::where('name', 'like', "%{$qSafe}%")->limit(5)->get()->map(function ($r) {
                    return [
                        'type' => 'recipe',
                        'name' => $r->name,
                        'url' => route('recipes.show', $r->id),
                        'id' => 'recipe-' . $r->id
                    ];
                })
            )->merge(
                Zone::where('long_name', 'like', "%{$qSafe}%")
                    ->orWhere('short_name', 'like', "%{$qSafe}%")
                    ->groupBy('short_name', 'long_name')->limit(5)->get()->map(function ($z) {
                    return [
                        'type' => 'zone',
                        'name' => $z->long_name,
                        'url' => route('zones.show', $z->id),
                        'id' => 'zone-' . $z->id
                    ];
                })
            )->merge(
                FactionList::where('name', 'like', "%{$qSafe}%")->limit(5)->get()->map(function ($f) {
                    return [
                        'type' => 'faction',
                        'name' => $f->name,
                        'url' => route('factions.show', $f->id),
                        'id' => 'faction-' . $f->id
                    ];
                })
            )->merge(
                Spell::where('name', 'like', "%{$qSafe}%")->groupBy('name')->limit(5)->get()->map(function ($s) {
                    return [
                        'type' => 'spell',
                        'name' => $s->name,
                        'url' => route('spells.show', $s->id),
                        'id' => 'spell-' . $s->id
                    ];
                })
            );

        return response()->json($results->take(40)->values());
    }
}
