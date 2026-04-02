<?php

declare(strict_types=1);

namespace App\Livewire\Table\Filters;

use App\Livewire\Table\Filter;

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
}
