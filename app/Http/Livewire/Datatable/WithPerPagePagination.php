<?php

declare(strict_types=1);

namespace App\Http\Livewire\Datatable;

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
     *
     * @return void
     */
    public function mountWithPerPagePagination()
    {
        $this->perPage = session()->get('perPage', $this->perPage);
    }

    /**
     * Update session with user selected per page value.
     *
     * @param  int  $value
     * @return void
     */
    public function updatedPerPage($value)
    {
        session()->put('perPage', $value);
    }

    /**
     * Apply pagination to query results.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function applyPagination($query)
    {
        return $query->paginate($this->perPage);
    }
}
