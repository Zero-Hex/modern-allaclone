<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TaskFilter
{
    protected $request;
    protected $builder;

    protected array $filters = [
        'name',
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

        return $this->builder;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function name($value)
    {
        $this->builder->where('title', 'like', '%' . $this->escapeLike($value) . '%');
    }

    protected function level($value)
    {
        $level = (int) $value;
        $this->builder->where(function ($query) use ($level) {
            $query->where(function ($q) use ($level) {
                $q->where('min_level', 0)->orWhere('min_level', '<=', $level);
            })->where(function ($q) use ($level) {
                $q->where('max_level', 0)->orWhere('max_level', '>=', $level);
            });
        });
    }
}
