<?php

declare(strict_types=1);

namespace App\Livewire\Table;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithPagination;

abstract class DataTableComponent extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortField = '';

    public string $sortDirection = 'asc';

    /** @var array<string, mixed> */
    public array $filterValues = [];

    public int $perPage = 10;

    /** @var array<int, int> */
    protected array $perPageAccepted = [5, 10, 25, 50, 100];

    protected string $primaryKey = 'id';

    protected string $searchPlaceholder = 'Search...';

    /** @var array<string> */
    protected array $additionalSelects = [];

    protected bool $paginationEnabled = true;

    protected bool $filtersEnabled = true;

    protected ?string $beforeWrapperView = null;

    /**
     * Return the query builder for the table data.
     *
     * @return Builder<Model>
     */
    abstract public function builder(): Builder;

    /**
     * Return the column definitions for the table.
     *
     * @return array<int, Column>
     */
    abstract public function columns(): array;

    /**
     * Configure the table component. Called during mount.
     */
    public function configure(): void {}

    /**
     * Return filter definitions for the table.
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->configure();
        $this->initializeFilterValues();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterValues(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sort(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render(): View
    {
        return view('livewire.table.data-table', [
            'columns' => $this->getColumns(),
            'rows' => $this->getRows(),
            'filters' => $this->filters(),
            'perPageOptions' => $this->perPageAccepted,
            'searchPlaceholder' => $this->searchPlaceholder,
            'beforeWrapperView' => $this->beforeWrapperView,
        ]);
    }

    /**
     * @return array<int, Column>
     */
    protected function getColumns(): array
    {
        $columns = $this->columns();

        if (method_exists($this, 'appendColumns')) {
            $columns = array_merge($columns, $this->appendColumns());
        }

        return $columns;
    }

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    protected function getRows(): LengthAwarePaginator
    {
        $query = $this->builder();

        if ($this->additionalSelects) {
            $query->addSelect($this->additionalSelects);
        }

        $this->applySearch($query);
        $this->applyFilters($query);
        $this->applySorting($query);

        return $query->paginate($this->perPage);
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $searchTerm = $this->search;
        $searchableColumns = collect($this->getColumns())->filter(fn (Column $col) => $col->isSearchable());

        if ($searchableColumns->isEmpty()) {
            return;
        }

        $query->where(function (Builder $q) use ($searchableColumns, $searchTerm): void {
            foreach ($searchableColumns as $column) {
                $q->orWhere($column->getField(), 'like', "%{$searchTerm}%");
            }
        });
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applyFilters(Builder $query): void
    {
        foreach ($this->filters() as $filter) {
            $value = $this->filterValues[$filter->getKey()] ?? $filter->getDefaultValue();
            $filter->apply($query, $value);
        }
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function applySorting(Builder $query): void
    {
        if ($this->sortField !== '') {
            $query->orderBy($this->sortField, $this->sortDirection);
        }
    }

    protected function initializeFilterValues(): void
    {
        foreach ($this->filters() as $filter) {
            if (! isset($this->filterValues[$filter->getKey()])) {
                $this->filterValues[$filter->getKey()] = $filter->getDefaultValue();
            }
        }
    }

    /* Configuration methods for compatibility with existing code */

    protected function setPrimaryKey(string $key): static
    {
        $this->primaryKey = $key;

        return $this;
    }

    protected function setColumnSelectDisabled(): static
    {
        return $this;
    }

    protected function setPaginationEnabled(): static
    {
        $this->paginationEnabled = true;

        return $this;
    }

    /**
     * @param  array<string>  $selects
     */
    protected function addAdditionalSelects(array $selects): static
    {
        $this->additionalSelects = array_merge($this->additionalSelects, $selects);

        return $this;
    }

    /**
     * @param  array<int, int>  $accepted
     */
    protected function setPerPageAccepted(array $accepted): static
    {
        $this->perPageAccepted = $accepted;

        return $this;
    }

    protected function setLoadingPlaceholderContent(string $content): static
    {
        return $this;
    }

    protected function setLoadingPlaceholderEnabled(): static
    {
        return $this;
    }

    protected function setFiltersStatus(bool $status): static
    {
        $this->filtersEnabled = $status;

        return $this;
    }

    protected function setSearchPlaceholder(string $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;

        return $this;
    }

    protected function setSearchIcon(string $icon): static
    {
        return $this;
    }

    /**
     * @param  array<string, mixed>|callable  $attributes
     */
    protected function setSearchFieldAttributes(array|callable $attributes): static
    {
        return $this;
    }

    /**
     * @param  array<string, string>  $areas
     */
    protected function setConfigurableAreas(array $areas): static
    {
        if (isset($areas['before-wrapper'])) {
            $this->beforeWrapperView = $areas['before-wrapper'];
        }

        return $this;
    }

    /* Styling no-ops — our Blade views handle styling directly */

    /** @param  array<string, mixed>  $attributes */
    protected function setPerPageFieldAttributes(array $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function setTableWrapperAttributes(array $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function setTableAttributes(array $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function setTheadAttributes(array $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>|callable  $attributes */
    protected function setThAttributes(array|callable $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>|callable  $attributes */
    protected function setThSortButtonAttributes(array|callable $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function setTbodyAttributes(array $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>|callable  $attributes */
    protected function setTrAttributes(array|callable $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>|callable  $attributes */
    protected function setTdAttributes(array|callable $attributes): static
    {
        return $this;
    }

    /** @param  array<string, mixed>  $attributes */
    protected function setPaginationWrapperAttributes(array $attributes): static
    {
        return $this;
    }
}
