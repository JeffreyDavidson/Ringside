<?php

declare(strict_types=1);

namespace App\Livewire\Table;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use LogicException;
use Stringable;

/** @phpstan-consistent-constructor */
class Column
{
    protected string $field;

    protected bool $searchable = false;

    protected ?Closure $searchCallback = null;

    protected bool $sortable = false;

    protected bool $isHtml = false;

    protected bool $excludedFromColumnSelect = false;

    protected ?Closure $labelCallback = null;

    /** @var view-string|null */
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

    public function searchable(?Closure $callback = null): static
    {
        $this->searchable = true;
        $this->searchCallback = $callback;

        return $this;
    }

    /**
     * @param  Builder<*>  $query
     */
    public function applySearch(Builder $query, string $searchTerm): void
    {
        if ($this->searchCallback !== null) {
            ($this->searchCallback)($query, $searchTerm);

            return;
        }

        $query->where($this->field, 'like', "%{$searchTerm}%");
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

    /** @param view-string $viewPath */
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
            return view($this->viewPath, ['row' => $row])->render();
        }

        if ($this->labelCallback) {
            $result = ($this->labelCallback)($row, $this);

            return $result instanceof View ? $result->render() : $this->resolveStringValue($result);
        }

        return $this->resolveStringValue(data_get($row, $this->field, ''));
    }

    private function resolveStringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return Str::of($value)->toString();
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof Stringable) {
            return Str::of((string) $value)->toString();
        }

        throw new LogicException('Table column values must be stringable.');
    }
}
