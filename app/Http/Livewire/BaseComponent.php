<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Http\Livewire\Datatable\WithPerPagePagination;
use Livewire\Component;

class BaseComponent extends Component
{
    use WithPerPagePagination;

    /**
     * Number of items to display on each page.
     *
     * @var int
     */
    protected $perPage = 10;

    /**
     * The view type to display for pagination.
     *
     * @return string
     */
    public function paginationView()
    {
        return 'pagination.datatables';
    }
}
