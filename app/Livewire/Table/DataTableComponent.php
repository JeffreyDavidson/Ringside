<?php

declare(strict_types=1);

namespace App\Livewire\Table;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @template TModel of Model
 */
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

    protected ?string $beforeWrapperView = null;

    /**
     * Return the query builder for the table data.
     *
     * @return Builder<TModel>
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
        if (! in_array($this->perPage, $this->perPageAccepted, true)) {
            $this->perPage = $this->perPageAccepted[0] ?? 10;
        }

        $this->resetPage();
    }

    public function sort(string $field): void
    {
        if (! $this->isSortableField($field)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[On('refreshDatatable')]
    public function refreshDatatable(): void {}

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
            return array_merge($columns, $this->appendColumns());
        }

        return $columns;
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    protected function getRows(): LengthAwarePaginator
    {
        $this->normalizeTableState();

        $query = $this->builder();

        if ($this->additionalSelects) {
            if ($query->getQuery()->columns === null) {
                $query->select('*');
            }

            $query->addSelect($this->additionalSelects);
        }

        $this->applySearch($query);
        $this->applyFilters($query);
        $this->applySorting($query);

        return $query->paginate($this->perPage);
    }

    /**
     * @param  Builder<TModel>  $query
     */
    protected function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $searchTerm = $this->search;
        $searchableColumns = collect($this->getColumns())->filter(fn (Column $col): bool => $col->isSearchable());

        if ($searchableColumns->isEmpty()) {
            return;
        }

        $query->where(function (Builder $q) use ($searchableColumns, $searchTerm): void {
            foreach ($searchableColumns as $column) {
                $q->orWhere(function (Builder $columnQuery) use ($column, $searchTerm): void {
                    $column->applySearch($columnQuery, $searchTerm);
                });
            }
        });
    }

    /**
     * @param  Builder<TModel>  $query
     */
    protected function applyFilters(Builder $query): void
    {
        foreach ($this->filters() as $filter) {
            $value = $this->filterValues[$filter->getKey()] ?? $filter->getDefaultValue();
            $filter->apply($query, $value);
        }
    }

    /**
     * @param  Builder<TModel>  $query
     */
    protected function applySorting(Builder $query): void
    {
        if ($this->sortField !== '' && $this->isSortableField($this->sortField)) {
            $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

            $query->orderBy($this->sortField, $direction);
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

    private function normalizeTableState(): void
    {
        if (! in_array($this->perPage, $this->perPageAccepted, true)) {
            $this->perPage = $this->perPageAccepted[0] ?? 10;
        }

        if ($this->sortField !== '' && ! $this->isSortableField($this->sortField)) {
            $this->sortField = '';
            $this->sortDirection = 'asc';
        }
    }

    private function isSortableField(string $field): bool
    {
        return collect($this->getColumns())
            ->contains(fn (Column $column): bool => $column->isSortable() && $column->getField() === $field);
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

    protected function setSearchPlaceholder(string $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;

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
}
