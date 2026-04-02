<?php

declare(strict_types=1);

namespace App\Livewire\Table\Columns;

use App\Livewire\Table\Column;
use Closure;

class ArrayColumn extends Column
{
    protected ?Closure $dataCallback = null;

    protected ?Closure $outputFormatCallback = null;

    protected string $separator = ', ';

    protected string $emptyValue = '';

    public function data(Closure $callback): static
    {
        $this->dataCallback = $callback;

        return $this;
    }

    public function outputFormat(Closure $callback): static
    {
        $this->outputFormatCallback = $callback;

        return $this;
    }

    public function separator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    public function emptyValue(string $value): static
    {
        $this->emptyValue = $value;

        return $this;
    }

    public function resolveValue(mixed $row): string
    {
        $items = $this->dataCallback
            ? ($this->dataCallback)(null, $row)
            : collect();

        if ($items->isEmpty()) {
            return $this->emptyValue;
        }

        if ($this->outputFormatCallback) {
            return $items->map(fn (mixed $item, int $index) => ($this->outputFormatCallback)($index, $item))
                ->implode($this->separator);
        }

        return $items->implode($this->separator);
    }

    public function isHtml(): bool
    {
        return true;
    }
}
