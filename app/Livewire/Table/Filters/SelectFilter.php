<?php

declare(strict_types=1);

namespace App\Livewire\Table\Filters;

use App\Livewire\Table\Filter;

/** @phpstan-consistent-constructor */
class SelectFilter extends Filter
{
    /** @var array<int|string, string> */
    protected array $options = [];

    public static function make(string $name, ?string $key = null): static
    {
        return new static($name, $key);
    }

    /**
     * @param  array<int|string, string>  $options
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<int|string, string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getDefaultValue(): string
    {
        return '';
    }
}
