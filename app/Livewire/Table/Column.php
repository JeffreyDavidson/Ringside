<?php

declare(strict_types=1);

namespace App\Livewire\Table;

use Closure;
use Illuminate\Contracts\View\View;

class Column
{
    protected string $field;

    protected bool $searchable = false;

    protected bool $sortable = false;

    protected bool $isHtml = false;

    protected bool $excludedFromColumnSelect = false;

    protected ?Closure $labelCallback = null;

    protected ?string $viewPath = null;

    public function __construct(
        protected string $title,
        ?string $from = null,
    ) {
        $this->field = $from ?? str($title)->snake()->toString();
    }

    public static function make(string $title, ?string $from = null): static
    {
        return new static($title, $from);
    }

    public function searchable(): static
    {
        $this->searchable = true;

        return $this;
    }

    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    public function html(): static
    {
        $this->isHtml = true;

        return $this;
    }

    public function label(Closure $callback): static
    {
        $this->labelCallback = $callback;

        return $this;
    }

    public function view(string $viewPath): static
    {
        $this->viewPath = $viewPath;

        return $this;
    }

    public function excludeFromColumnSelect(): static
    {
        $this->excludedFromColumnSelect = true;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    /**
     * Resolve the display value for a given row.
     */
    public function resolveValue(mixed $row): string
    {
        if ($this->viewPath) {
            $view = view($this->viewPath, ['row' => $row]);

            return $view instanceof View ? $view->render() : (string) $view;
        }

        if ($this->labelCallback) {
            $result = ($this->labelCallback)($row, $this);

            return $result instanceof View ? $result->render() : (string) $result;
        }

        return (string) data_get($row, $this->field, '');
    }
}
