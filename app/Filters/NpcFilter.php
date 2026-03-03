<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class NpcFilter
{
    protected $request;
    protected $builder;

    protected array $filters = [
        'name',
        'min_lvl',
        'max_lvl',
        'zone',
        'faction',
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

        return $this->builder;
    }

    protected function name($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = str_replace(['\\', '%'], ['\\\\', '\\%'], $value);
        $value = str_replace(' ', '_', $value);
        $value = str_replace('`', '-', $value);

        $this->builder->where('name', 'like', "%{$value}%");
    }

    protected function min_lvl($value)
    {
        $this->builder->where('level', '>=', (int) $value);
    }

    protected function max_lvl($value)
    {
        $this->builder->where('level', '<=', (int) $value);
    }

    protected function zone($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = str_replace(['\\', '%'], ['\\\\', '\\%'], $value);

        $this->builder->whereHas('spawnentries.spawn2', function ($q) use ($value) {
            $q->where('zone', 'like', "%{$value}%");
        });
    }

    protected function faction($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = str_replace(['\\', '%'], ['\\\\', '\\%'], $value);

        $this->builder->whereHas('npcFaction.primaryFaction', function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%");
        });
    }
}
