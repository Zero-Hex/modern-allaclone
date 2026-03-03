<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ItemFilter
{
    protected $request;
    protected $builder;

    protected array $filters = [
        'name',
        'slot',
        'augslot',
        'type',
        'class',
        'bagslots',
        'effect',
        'effect_id',
        'evo',
        /* stats */
        'stat1',
        'stat1comp',
        'stat1val',
        'stat2',
        'stat2comp',
        'stat2val',
        'stat3',
        'stat3comp',
        'stat3val',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters as $filter) {
            if (method_exists($this, $filter) && $this->request->filled($filter)) {
                $this->{$filter}($this->request->get($filter));
            }
        }

        $this->applyLevelFilter();

        return $this->builder;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function name($value)
    {
        $this->builder->where('Name', 'like', '%' . $this->escapeLike($value) . '%');
    }

    protected function type($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = (int) $value;

        // handle bagslots if custom bag type selected
        $bagSlots = ($this->request->get('bagslots') >= 1 ? $this->request->get('bagslots') : 1);

        if ($value === 7 || $value === 19) {
            // combine throwing
            $this->builder->whereIn('itemtype', [7, 19]);
        } elseif ($value == 56 || $value == 64) {
            // combine augment distillers
            $this->builder->whereIn('itemtype', [56, 64]);
        } elseif ($value == 33 || $value == 39) {
            // combine keys
            $this->builder->whereIn('itemtype', [33, 39]);
        } elseif ($value == 555) {
            // custom bag filter
            $this->builder->where('bagslots', '>=', $bagSlots)
                ->whereIn('bagtype', [0, 1, 2, 3, 4, 5, 6, 7]);
        } elseif ($value == 556) {
            // custom quest bag filter
            $this->builder->where('bagslots', '>=', $bagSlots)
                ->where('bagtype', 13);
        } elseif ($value == 557) {
            // custom ts bags filter
            $this->builder->where('bagslots', '>=', $bagSlots)
                ->where('bagtype', '>=', 9)
                ->where('bagtype', '!=', 13);
        } else {
            $this->builder->where('itemtype', $value);
        }
    }

    protected function bagslots($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = (int) $value;

        // if custom bag itemtype is selected, lets get it
        $hasType = (in_array($this->request->get('type'), [555, 556, 557]));
        if ($hasType) {
            return;
        }

        $this->builder->where('bagslots', '>=', $value);
    }

    protected function slot($value)
    {
        if ($value !== null && is_numeric($value)) {
            $bitmask = (int) $value;

            $this->builder->whereRaw("(slots & ?) != 0", [$bitmask]);
        }
    }

    protected function augslot($value)
    {
        if ($value !== null && is_numeric($value)) {
            $bitmask = 1 << ($value - 1);
            $this->builder->whereRaw("(augtype & ?) != 0", [$bitmask]);
        }
    }

    protected function class($value)
    {
        if ($value !== null && is_numeric($value)) {
            $bitmask = (int) $value;

            // all class.. and any (to fetch without class associated)
            if ($bitmask === 65535) {
                $this->builder->where(function ($q) use ($bitmask) {
                    $q->whereRaw("(classes & ?) != 0", [$bitmask])
                    ->orWhere('classes', 0);
                });
            } else {
                $this->builder->whereRaw("(classes & ?) != 0", [$bitmask]);
            }
        }
    }

    protected function effect($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $effectRelations = [
            'procEffectSpell',
            'wornEffectSpell',
            'focusEffectSpell',
            'clickEffectSpell',
            'scrollEffectSpell',
        ];

        $this->builder->where(function ($query) use ($value, $effectRelations) {
            foreach ($effectRelations as $relation) {
                $query->orWhereHas($relation, function ($q) use ($value) {
                    $q->where('name', 'like', '%' . $this->escapeLike($value) . '%')->select('id');
                });
            }
        });
    }

    protected function effect_id($value)
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return;
        }

        $id = (int) $value;
        $effectFields = ['proceffect', 'worneffect', 'focuseffect', 'clickeffect', 'scrolleffect'];

        $this->builder->where(function ($query) use ($id, $effectFields) {
            foreach ($effectFields as $field) {
                $query->orWhere($field, $id);
            }
        });
    }

    protected function evo($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($value == 1) {
            $this->builder->where('evolvinglevel', '>=', 1)
                ->where('evoid', '!=', 0);
        }
    }

    protected function stat1()
    {
        $this->applyStat('stat1');
    }

    protected function stat2()
    {
        $this->applyStat('stat2');
    }

    protected function stat3()
    {
        $this->applyStat('stat3');
    }

    protected function applyStat($statKey)
    {
        $stat = $this->request->get($statKey);
        $comp = $this->request->get($statKey . 'comp', 1);
        $val  = $this->request->get($statKey . 'val');

        if ($stat && $val !== null) {

            $op = match ((int) $comp) {
                1 => '>=',
                2 => '<=',
                5 => '=',
                default => '>='
            };

            if ($stat === 'ratio') {
                $this->builder->whereRaw('(damage > 0 AND delay / damage ' . $op . ' ?)', [$val]);
            } else {
                if ($op === '<=') {
                    $this->builder->where($stat, '>=', 1);
                }

                $this->builder->where($stat, $op, $val);
            }
        }
    }

    protected function applyLevelFilter()
    {
        $maxServerLevel = config('everquest.server_max_level');
        $minLevel = (int) $this->request->get('min_lvl');
        $maxLevel = (int) $this->request->get('max_lvl');

        if ($minLevel > 0 && $maxLevel === 0) {
            $maxLevel = $maxServerLevel;
        }

        if ($maxLevel > 0 && $minLevel === 0) {
            $minLevel = 0;
        }

        $maxLevel = min($maxLevel, $maxServerLevel);

        if ($minLevel > 0 || $maxLevel > 0) {
            $this->builder->whereBetween('reqlevel', [$minLevel, $maxLevel]);
        } else {
            $this->builder->where('reqlevel', '<=', $maxServerLevel);
        }
    }
}
