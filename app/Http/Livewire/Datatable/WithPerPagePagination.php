<?php

declare(strict_types=1);

namespace App\Http\Livewire\Datatable;

use Livewire\WithPagination;

trait WithPerPagePagination
{
    use WithPagination;

    protected $perPage = 10;

    public function mountWithPerPagePagination()
    {
        $this->perPage = session()->get('perPage', $this->perPage);
    }

    public function updatedPerPage($value)
    {
        session()->put('perPage', $value);
    }

    public function applyPagination($query)
    {
        return $query->paginate($this->perPage);
    }
}
