<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\Task;
use App\Models\NpcType;
use App\Models\NpcSpell;
use App\Filters\NpcFilter;
use App\Models\TaskActivity;
use Illuminate\Http\Request;
use App\Models\AlternateCurrency;

class NpcController extends Controller
{
    public function index(Request $request)
    {
        $npcs = collect();
        $currentExpansion = config('everquest.current_expansion');

        if ($request->query->count() > 0) {
            $npcs = (new NpcFilter($request))
                ->apply(NpcType::query())
                ->select('id', 'name', 'level', 'race', 'class', 'hp', 'maxlevel', 'version')
                ->whereNotIn('race', [127, 240])
                ->with([
                    'firstSpawnEntries.spawn2.zoneData',
                ])
                ->orderBy('name', 'asc')
                ->paginate(50)
                ->withQueryString();

            $zones = Zone::select('id', 'zoneidnumber', 'short_name', 'long_name', 'expansion', 'version')->get();

            foreach ($npcs as $npc) {
                foreach ($npc->spawnEntries as $entry) {
                    if (!isset($entry->spawn2)) continue;

                    $entry->matched_zone = $zones
                        ->where('short_name', $entry->spawn2->zone)
                        ->where('version', $entry->spawn2->version)
                        ->first();
                }
            }
        }

        return view('npcs.index', [
            'npcs' => $npcs,
            'metaTitle' => config('app.name') . ' - NPC Search',
        ]);
    }

    public function show(NpcType $npc)
    {
        $ignoreZones = config('everquest.ignore_zones') ?? [];

        $npc = NpcType::with('npcSpellset.attackProcSpell')
            ->with([
                'spawnEntries.spawn2' => function ($q) use ($ignoreZones) {
                    if (!empty($ignoreZones)) {
                        $q->whereNotIn('zone', $ignoreZones);
                    }

                    $q->with(['npcs' => function ($npcs) {
                        $npcs->select('id', 'name', 'level', 'race', 'class');
                    }, 'spawnGroup']);
                },
                'firstSpawnEntries.spawn2.zoneData',
                'npcFaction.primaryFaction',
                'npcFactionEntries.factionList',
                'lootTable.loottableEntries.lootdropEntries.item',
                'merchantlist.items',
            ])
            ->findOrFail($npc->id);

        if ($npc->npcSpellset) {
            $npc->attackProcSpell = $npc->npcSpellset->attackProcSpell;
            $npc->attackProcSpellProcChance = $npc->npcSpellset->proc_chance;
        }

        $npcSpellset = $npc->npcSpellset;
        if ($npcSpellset && $npcSpellset->parent_list > 0) {
            $npc->npcSpellset = NpcSpell::with('npcSpellEntries.spells', 'attackProcSpell')
                ->where('id', $npcSpellset->parent_list)
                ->first();
        }

        if ($npc->npcSpellset) {
            $npc->filteredSpellEntries = $npc->npcSpellset->npcSpellEntries()
                ->where('minlevel', '<=', $npc->level)
                ->where('maxlevel', '>=', $npc->level)
                ->orderBy('priority', 'desc')
                ->with('spells')
                ->get();
        } else {
            $npc->filteredSpellEntries = collect();
        }

        // separate and group faction
        $raisesFaction = [];
        $lowersFaction = [];

        foreach ($npc->npcFactionEntries as $entry) {
            $factionName = $entry->factionList->name ?? 'Unknown';
            $factionId   = $entry->faction_id;
            $value       = $entry->value;

            if ($value > 0) {
                $raisesFaction[] = [
                    'name' => $factionName,
                    'id' => $factionId,
                    'value' => $value,
                ];
            } elseif ($value < 0) {
                $lowersFaction[] = [
                    'name' => $factionName,
                    'id' => $factionId,
                    'value' => $value,
                ];
            }
        }

        $defaultTab = null;
        if ($npc->lootTable?->loottableEntries->isNotEmpty()) {
            $defaultTab = 'drops';
        } elseif ($npc->merchantlist->isNotEmpty()) {
            $defaultTab = 'merchant';
        } elseif ($npc->spawnEntries->isNotEmpty()) {
            $defaultTab = 'spawns';
        } elseif ($npc->npcFactionEntries->isNotEmpty()) {
            $defaultTab = 'faction';
        }

        $lvl = $npc->level ? ' - Level (' . $npc->level . ')' : '';

        $altCurrency = AlternateCurrency::allAltCurrency();

        // find tasks that reference this NPC by ID or name in npc_match_list
        $relatedTasks = collect();
        if (config('everquest.display_task_info')) {
            $taskActivityIds = TaskActivity::where('npc_match_list', 'like', '%' . $npc->id . '%')
                ->orWhere('npc_match_list', 'like', '%' . $npc->name . '%')
                ->pluck('taskid')
                ->unique();

            if ($taskActivityIds->isNotEmpty()) {
                $relatedTasks = Task::whereIn('id', $taskActivityIds)
                    ->where('enabled', 1)
                    ->select('id', 'title', 'type', 'min_level', 'max_level', 'reward_id_list')
                    ->orderBy('min_level')
                    ->get();

                $relatedTasks = Task::attachRewardsMultiple($relatedTasks);
            }
        }

        return view('npcs.show', [
            'npc' => $npc,
            'defaultTab' => $defaultTab,
            'raisesFaction' => $raisesFaction,
            'lowersFaction' => $lowersFaction,
            'altCurrency' => $altCurrency,
            'relatedTasks' => $relatedTasks,
            'metaTitle' => config('app.name') . ' - NPC: ' . $npc->clean_name . $lvl,
        ]);
    }
}
