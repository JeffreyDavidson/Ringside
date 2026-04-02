<?php

declare(strict_types=1);

namespace App\Livewire\Table\Columns;

use App\Livewire\Table\Column;
use Closure;

class LinkColumn extends Column
{
    protected ?Closure $titleCallback = null;

    protected ?Closure $locationCallback = null;

    public function title(Closure $callback): static
    {
        $this->titleCallback = $callback;

        return $this;
    }

    public function location(Closure $callback): static
    {
        $this->locationCallback = $callback;

        return $this;
    }

    public function resolveValue(mixed $row): string
    {
        $title = $this->titleCallback ? ($this->titleCallback)($row) : '';
        $location = $this->locationCallback ? ($this->locationCallback)($row) : '';

        if ($location === '' || $location === null) {
            return (string) $title;
        }

        return '<a href="'.e($location).'">'.e($title).'</a>';
    }

    public function isHtml(): bool
    {
        return true;
    }
}
