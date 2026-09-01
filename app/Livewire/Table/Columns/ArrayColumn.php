<?php

declare(strict_types=1);

namespace App\Livewire\Table\Columns;

use App\Livewire\Table\Column;
use Closure;
use LogicException;

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

    public function link(Closure $title, Closure $location): static
    {
        return $this->outputFormat(
            function (mixed $item) use ($title, $location): string {
                $linkTitle = $title($item);
                $linkLocation = $location($item);

                if (! is_string($linkTitle) || ! is_string($linkLocation)) {
                    throw new LogicException('Array column link callbacks must return strings.');
                }

                return static::linkHtml($linkTitle, $linkLocation);
            }
        );
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
            ? ($this->dataCallback)($row)
            : collect();

        if ($items->isEmpty()) {
            return $this->emptyValue;
        }

        if ($this->outputFormatCallback) {
            return $items->map(fn (mixed $item) => ($this->outputFormatCallback)($item))
                ->implode($this->separator);
        }

        return $items->implode($this->separator);
    }

    public function isHtml(): bool
    {
        return true;
    }
}
