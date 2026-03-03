<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Zone extends Model
{
    use HasFactory;

    protected $connection = 'eqemu';
    protected $table = 'zone';

    public function forages(): HasMany
    {
        return $this->hasMany(Forage::class, 'zoneid', 'zoneidnumber');
    }

    public function fished(): HasMany
    {
        return $this->hasMany(Fishing::class, 'zoneid', 'zoneidnumber');
    }

    public function spawns(): HasMany
    {
        return $this->hasMany(SpawnTwo::class, 'zone', 'short_name');
    }

    public function zonepoints(): HasMany
    {
        return $this->hasMany(ZonePoint::class, 'zone', 'short_name');
    }

    public function taskActivities(): HasMany
    {
        return $this->hasMany(TaskActivity::class, 'zones', 'zoneidnumber');
    }

    public static function getExpansionZones(int $expansion): Collection
    {
        return self::where('expansion', '<=', $expansion)
            ->where('min_status', 0)
            ->select('id', 'expansion', 'short_name', 'long_name', 'version', 'zone_exp_multiplier')
            ->orderBy('expansion', 'asc')
            ->orderBy('long_name', 'asc')
            ->get()
            ->groupBy('expansion');
    }
}
