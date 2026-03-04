<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\PetBeastlordData;
use App\Models\Spell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PetController extends Controller
{
    public function index(Request $request, $id = null)
    {
        $petClassIds = array_keys((array) config('everquest.pet_classes'));
        $selClass = 'classes15';
        $selClassName = 'Beastlord';

        if ($id) {
            if (!in_array((int) $id, $petClassIds)) {
                abort(404);
            }

            $selClass = 'classes' . $id;
            $selClassName = config('everquest.classes.' . $id);
        }

        $pets = Cache::remember("pet_{$selClass}", now()->addWeek(), function () use ($selClass) {
            return Spell::with('pets', 'npcs')
                ->where($selClass, '>', 0)
                ->where($selClass, '<=', config('everquest.server_max_level'))
                ->select('id', 'name', 'new_icon', 'teleport_zone', $selClass)
                ->groupBy('teleport_zone')
                ->orderBy($selClass)
                ->get();
        });

        // show beastlord pet data
        $bl_pet_data = collect();
        if ($selClass === 'classes15') {
            $bl_pet_data = PetBeastlordData::all();
        }

        return view('pets.index', [
            'pets' => $pets,
            'bl_pet_data' => $bl_pet_data,
            'selClassName' => $selClassName,
            'metaTitle' => config('app.name') . ' - Pets: ' . $selClassName,
        ]);
    }

    public function show(Pet $pet)
    {
        $pet = Pet::where('id', $pet->id)->with('npcs')->firstOrFail();

        return view('pets.show', [
            'pet' => $pet,
            'metaTitle' => config('app.name') . ' - Pet: ' . $pet->type,
        ]);
    }

    public function popup($pet)
    {
        $pet = Pet::where('id', $pet)
            ->orWhere('type', $pet)
            ->firstOrFail();

        return response()->json([
            'html' => view('pets.partials.popup', [
                'pet' => $pet,
            ])->render()
        ]);
    }
}
