<?php

declare(strict_types=1);

namespace App\Livewire\Table\Filters;

use App\Livewire\Table\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DateRangeFilter extends Filter
{
    /** @var array<string, mixed> */
    protected array $config = [];

    /** @var array<int, string> */
    protected array $pillValues = [];

    public static function make(string $name, ?string $key = null): static
    {
        return new static($name, $key);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function config(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param  array<int, string>  $values
     */
    public function setFilterPillValues(array $values): static
    {
        $this->pillValues = $values;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultValue(): array
    {
        return [];
    }

    /**
     * Apply this filter to the query builder.
     *
     * Only invokes the user-provided filter callback when the value is an
     * array with non-empty `minDate` and `maxDate` entries. Without this
     * guard, the default empty-array value bypasses the parent class's
     * `!== ''/!== null` check and reaches callbacks that read those keys.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, mixed $value): void
    {
        if (! is_array($value)) {
            return;
        }

        if (empty($value['minDate']) || empty($value['maxDate'])) {
            return;
        }

        parent::apply($builder, $value);
    }
}
