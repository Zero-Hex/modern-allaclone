<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Zone;
use App\Filters\ItemFilter;
use Illuminate\Http\Request;
use App\ViewModels\ItemViewModel;
use Illuminate\Support\Facades\Cache;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'stat1comp' => 'in:1,2,5',
            'stat2comp' => 'in:1,2,5',
            'stat3comp' => 'in:1,2,5',
        ]);

        $items = collect();
        if ($request->query->count() > 0) {
            $items = (new ItemFilter($request))
                ->apply(Item::query())
                ->select([
                    'id', 'Name', 'icon', 'itemtype', 'ac', 'hp', 'damage', 'delay',
                    'augtype', 'slots', 'bagslots', 'bagwr',
                ])
                ->orderBy('id', 'asc')
                ->paginate(50)
                ->withQueryString();
        }

        return view('items.index', [
            'items' => $items,
            'metaTitle' => config('app.name') . ' - Item Search',
        ]);
    }

    public function show(Item $item)
    {
        $itemCache = Cache::remember("items.show.{$item->id}", now()->addWeek(), function () use ($item) {
            $item = Item::with('evolvingDetails.item')->where('id', $item->id)->firstOrFail();
            $vm = (new ItemViewModel($item))->withEffects();

            return [
                'item' => $item,
                'recipes' => $vm->recipes(),
                'used_in_ts' => $vm->usedInTradeskills(),
                'forage' => $vm->forageZones(),
                'fishing' => $vm->fishingZones(),
                'soldByZone' => $vm->soldInZones(),
                'ground_spawn' => $vm->itemGroundSpawn(),
            ];
        });

        return view('items.show', [
            ...$itemCache,
            'metaTitle' => config('app.name') . ' - Item: ' . $item->Name,
        ]);
    }

    public function popup(Item $item)
    {
        $item = Item::where('id', $item->id)->firstOrFail();
        (new ItemViewModel($item))->withEffects();

        return response()->json([
            'html' => view('items.partials.popup', ['item' => $item])->render()
        ]);
    }

    public function drops_by_zone(Item $item)
    {
        $drops = Cache::remember("items.drops_by_zone.{$item->id}", now()->addWeek(), function () use ($item) {
            return (new ItemViewModel($item))->dropsByZone();
        });

        return response()->json([
            'drops_by_zone' => $drops['drops_by_zone'],
            'top_npcs' => $drops['top_npcs'],
        ]);
    }
}
