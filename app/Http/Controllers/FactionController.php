<?php

namespace App\Http\Controllers;

use App\Models\FactionList;
use App\Models\NpcType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FactionController extends Controller
{
    public function index(Request $request)
    {
        $factions = Cache::remember('factions.all', now()->addWeek(), function () {
            return FactionList::orderBy('name', 'asc')->get();
        });

        return view('factions.index', [
            'factions' => $factions,
            'metaTitle' => config('app.name') . ' - Factions',
        ]);
    }

    public function show(FactionList $faction)
    {
        // all factions for select
        $allFactions = Cache::remember('factions.all', now()->addWeek(), function () {
            return FactionList::orderBy('name', 'asc')->get();
        });

        $npcs = NpcType::with([
            'npcFactionEntries' => function ($q) use ($faction) {
                $q->where('faction_id', $faction->id)
                    ->select('npc_faction_id', 'faction_id', 'value');
            },
            'spawnEntries.spawn2.zoneData'
        ])
            ->whereHas('npcFactionEntries', function ($q) use ($faction) {
                $q->where('faction_id', $faction->id);
            })
            ->select('id', 'name', 'npc_faction_id')
            ->groupBy('name')
            ->get()
            ->unique('id')
            ->sortBy(function ($npc) {
                return optional(
                    $npc->spawnEntries->firstWhere(fn($se) => $se->spawn2 && $se->spawn2->zoneData)
                )?->spawn2?->zoneData?->long_name ?? '';
        });

        $factions = [
            'raised' => collect(),
            'lowered' => collect(),
        ];

        foreach ($npcs as $npc) {
            foreach ($npc->npcFactionEntries as $entry) {
                $value = (int) $entry->value;
                if ($value === 0) {
                    continue;
                }

                $zoneId = optional(
                    $npc->spawnEntries->firstWhere(fn($se) => $se->spawn2 && $se->spawn2->zoneData)
                )?->spawn2?->zoneData?->id;

                $zoneName = optional(
                    $npc->spawnEntries->firstWhere(fn($se) => $se->spawn2 && $se->spawn2->zoneData)
                )?->spawn2?->zoneData?->long_name ?? 'Unknown Zone';

                $type = $value > 0 ? 'raised' : 'lowered';
                $zoneKey = $zoneId . '|' . $zoneName;

                $factions[$type]->push([
                    'zone_key' => $zoneKey,
                    'zone' => $zoneName,
                    'zone_id' => $zoneId,
                    'npc_id' => $npc->id,
                    'npc_name' => $npc->clean_name,
                    'value' => $value,
                ]);
            }
        }

        $factions['raised'] = $factions['raised']->groupBy('zone_key')->map(function ($npcs) {
            return $npcs->sortBy('npc_name');
        });

        $factions['lowered'] = $factions['lowered']->groupBy('zone_key')->map(function ($npcs) {
            return $npcs->sortBy('npc_name');
        });

        return view('factions.show', [
            'allFactions' => $allFactions,
            'faction' => $faction,
            'factions' => $factions,
            'metaTitle' => config('app.name') . ' - Faction: ' . $faction->name,
        ]);
    }
}
