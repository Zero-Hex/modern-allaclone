<?php

namespace App\Console\Commands;

use App\Models\Zone;
use App\ViewModels\ZoneViewModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheZones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-zones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache all the zones @show logic';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $currentExpansion = config('everquest.current_expansion');
        $zones = Zone::getExpansionZones($currentExpansion)->flatten(1);

        $this->info("Starting zone cache warming for {$zones->count()} zones...");

        foreach ($zones as $zone) {
            $version = $zone->version;
            $cacheKey = "zones.show.{$zone->id}_v{$version}";

            Cache::forget($cacheKey);

            try {
                Cache::remember($cacheKey, now()->addWeek(), function () use ($zone, $version) {
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

                $this->line("Cached: {$cacheKey} -- {$zone->short_name} / {$zone->id}-{$zone->version}");
            } catch (\Throwable $e) {
                $this->error("Failed: {$cacheKey} -- {$e->getMessage()}");
            }
        }

        $this->info('All zones cached successfully.');
        return Command::SUCCESS;
    }
}
