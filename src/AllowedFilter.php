<?php

namespace Spatie\QueryBuilder;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\FiltersExact;
use Spatie\QueryBuilder\Filters\FiltersScope;
use Spatie\QueryBuilder\Filters\FiltersPartial;
use Spatie\QueryBuilder\Filters\Filter;

class AllowedFilter
{
    /** @var \Spatie\QueryBuilder\Filters\Filter */
    protected $filterClass;

    /** @var string */
    protected $name;

    /** @var string */
    protected $internalName;

    /** @var Collection */
    protected $ignored;

    public function __construct(string $name, Filter $filterClass, ?string $internalName = null)
    {
        $this->name = $name;

        $this->filterClass = $filterClass;

        $this->ignored = Collection::make();

        $this->internalName = $internalName ?? $name;
    }

    public function filter(QueryBuilder $query, $value)
    {
        $valueToFilter = $this->resolveValueForFiltering($value);

        if (is_null($valueToFilter)) {
            return;
        }

        ($this->filterClass)($query, $valueToFilter, $this->internalName);
    }

    public static function exact(string $name, ?string $internalName = null) : self
    {
        return new static($name, new FiltersExact(), $internalName);
    }

    public static function partial(string $name, $internalName = null) : self
    {
        return new static($name, new FiltersPartial(), $internalName);
    }

    public static function scope(string $name, $internalName = null) : self
    {
        return new static($name, new FiltersScope(), $internalName);
    }

    public static function custom(string $name, Filter $filterClass, $internalName = null) : self
    {
        return new static($name, $filterClass, $internalName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isForFilter(string $filterName): bool
    {
        return $this->name === $filterName;
    }

    public function ignore(...$values): self
    {
        $this->ignored = $this->ignored
            ->merge($values)
            ->flatten();

        return $this;
    }

    public function getIgnored(): array
    {
        return $this->ignored->toArray();
    }

    public function getInternalName(): string
    {
        return $this->internalName;
    }

    protected function resolveValueForFiltering($value)
    {
        if (is_array($value)) {
            $remainingProperties = array_diff($value, $this->ignored->toArray());

            return ! empty($remainingProperties) ? $remainingProperties : null;
        }

        return ! $this->ignored->contains($value) ? $value : null;
    }
}
