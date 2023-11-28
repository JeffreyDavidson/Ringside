<?php

declare(strict_types=1);

namespace App\Http\Livewire\Datatable;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

trait WithPerPagePagination
{
    use WithPagination;

    /**
     * Number of records to display per page.
     *
     * @var int
     */
    protected $perPage = 10;

    /**
     * Apply number of records pages from session.
     */
    public function mountWithPerPagePagination(): void
    {
        $this->perPage = session()->get('perPage', $this->perPage);
    }

    /**
     * Update session with user selected per page value.
     */
    public function updatedPerPage(int $value): void
    {
        session()->put('perPage', $value);
    }

    /**
     * Apply pagination to query results.
     */
    public function applyPagination(Builder $query): LengthAwarePaginator
    {
        return $query->paginate($this->perPage);
    }
}
