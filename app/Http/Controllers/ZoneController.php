<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Models\AlternateCurrency;
use App\ViewModels\ZoneViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ZoneController extends Controller
{
    public function index(Request $request)
    {
        $currentExpansion = config('everquest.current_expansion');
        $expansions = config('everquest.expansions');

        $zones = Cache::remember('zones.index', now()->addWeek(), function () use ($currentExpansion) {
            return Zone::getExpansionZones($currentExpansion);
        });

        return view('zones.index', [
            'zones' => $zones,
            'expansions' => $expansions,
            'metaTitle' => config('app.name') . ' - Zones',
        ]);
    }

    public function show(Zone $zone, Request $request)
    {
        $version = (int) $request->query('v', 0);

        if ($version < 0 || $version > 255) {
            abort(404);
        }

        $zoneCache = Cache::remember("zones.show.{$zone->id}_v{$version}", now()->addWeek(), function () use ($zone, $version) {
            $zone = Zone::where('id', $zone->id)
                ->with('zonepoints', function ($q) use ($version) {
                    $q->when($version > 0, fn ($q) => $q->where('version', $version))
                        ->groupBy('target_zone_id')
                        ->with('targetZones:id,zoneidnumber,short_name,long_name');
                })
                ->when($version > 0, fn ($q) => $q->where('version', $version))
                ->firstOrFail();

            $vm = new ZoneViewModel($zone, $version);

            return [
                'zone' => $zone,
                'npcs' => $vm->npcs(),
                'drops' => $vm->drops(),
                'spawnGroups' => $vm->spawnGroups(),
                'foraged' => $vm->foraged(),
                'fished' => $vm->fished(),
                'connectedZones' => $vm->connectedZones(),
                'tasks' => $vm->tasks(),
            ];
        });

        // get cached alt currency since tasks could use it
        $altCurrency = AlternateCurrency::allAltCurrency();

        // zone version for meta title
        $zone = $zoneCache['zone'];
        $zversion = $zone->version ? ' - version (' . $zone->version . ')' : '';

        // collect all available versions for this zone's short_name
        $availableVersions = Zone::where('short_name', $zone->short_name)
            ->select('id', 'version')
            ->orderBy('version')
            ->get();

        return view('zones.show', [
            ...$zoneCache,
            'altCurrency' => $altCurrency,
            'availableVersions' => $availableVersions,
            'currentVersion' => $version,
            'metaTitle' => config('app.name') . ' - Zone: ' . $zone->long_name . $zversion,
        ]);
    }
}
