<?php

declare(strict_types=1);

namespace App\Livewire\Table;

use Closure;
use Illuminate\Database\Eloquent\Builder;

abstract class Filter
{
    protected ?Closure $filterCallback = null;

    public function __construct(
        protected string $name,
        protected ?string $key = null,
    ) {
        $this->key = $key ?? str($name)->snake()->toString();
    }

    public function filter(Closure $callback): static
    {
        $this->filterCallback = $callback;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): string
    {
        return $this->key ?? str($this->name)->snake()->toString();
    }

    /**
     * Apply this filter to the query builder.
     *
     * @param  Builder<*>  $builder
     */
    public function apply(Builder $builder, mixed $value): void
    {
        if ($this->filterCallback instanceof Closure && $value !== '' && $value !== null) {
            ($this->filterCallback)($builder, $value);
        }
    }

    /**
     * Get the default value for this filter.
     */
    abstract public function getDefaultValue(): mixed;
}
