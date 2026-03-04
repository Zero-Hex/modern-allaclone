<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SpellFilter
{
    protected $request;
    protected $builder;

    protected array $filters = [
        'name',
        'class',
        'level',
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

        $nameFilled = $this->request->filled('name');
        $classFilled = $this->request->filled('class');
        $levelFilled = $this->request->filled('level');

        if ($nameFilled && !$classFilled && !$levelFilled) {
            $this->fallbackClassLevelFilter();
        }

        return $this->builder;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function name($value)
    {
        $this->builder->where('name', 'like', '%' . $this->escapeLike($value) . '%');
    }

    protected function level($value)
    {
        $level = (int) $value;
        $class = $this->request->get('class');
        $opt = (int) $this->request->get('opt', 3);
        $max = config('everquest.server_max_level');

        if ($level > $max) {
            $level = $max;
        }

        $class = (int) $class;
        if ($class >= 1 && $class <= 16 && $level > 0) {
            $class_col = "classes{$class}";

            if ($opt === 1) {
                $this->builder->where($class_col, $level);
            } elseif ($opt === 2) {
                $this->builder->where($class_col, '>=', $level)->where($class_col, '<=', $max);
            } elseif ($opt === 3) {
                $this->builder->where($class_col, '<=', $level)->where($class_col, '<=', $max);
            }
        } elseif (!$class && $level > 0) {
            $this->builder->where(function ($query) use ($level, $opt, $max) {
                for ($i = 1; $i <= 16; $i++) {
                    $class_col = "classes{$i}";

                    if ($opt === 1) { // only
                        $query->orWhere($class_col, $level);
                    } elseif ($opt === 2) { // and higher
                        $query->orWhere(function ($q) use ($class_col, $level, $max) {
                            $q->where($class_col, '>=', $level)
                            ->where($class_col, '<=', $max);
                        });
                    } elseif ($opt === 3) { // and lower
                        $query->orWhere(function ($q) use ($class_col, $level, $max) {
                            $q->where($class_col, '<=', $level);
                            //->where($class_col, '<=', $max);
                        });
                    }
                }
            });
        }
    }

    protected function class($value)
    {
        $value = (int) $value;
        if ($value >= 1 && $value <= 16) {
            $class_col = "classes{$value}";
            $this->builder->where($class_col, '>', 0);

            if (!$this->request->filled('level')) {
                $this->builder->where($class_col, '<=', config('everquest.server_max_level'));
            }
        }
    }

    protected function fallbackClassLevelFilter()
    {
        $max = config('everquest.server_max_level');
        $level = $this->request->filled('level') ? (int) $this->request->get('level') : 1;
        $opt = (int) $this->request->input('opt', 3);

        if ($opt == 3 && $level == 1) {
            $level = 70;
        }

        $this->builder->where(function ($query) use ($level, $max, $opt) {
            for ($i = 1; $i <= 16; $i++) {
                $class_col = "classes{$i}";

                $query->orWhere(function ($q) use ($class_col, $level, $max, $opt) {
                    //$q->where($class_col, '>', 0);

                    match ($opt) {
                        1 => $q->where($class_col, $level), // (1) Only
                        2 => $q->where($class_col, '>=', $level)->where($class_col, '<=', $max), // (2) And Higher
                        3 => $q->where($class_col, '<=', $level)->where($class_col, '>', 0), // (3) And Lower
                        default => $q->where($class_col, $level),
                    };
                });
            }
        });
    }
}
