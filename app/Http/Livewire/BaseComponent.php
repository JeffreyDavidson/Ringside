<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class BaseComponent extends Component
{
    use WithPagination;

    /**
     * Number of items to display on each page.
     *
     * @var int
     */
    public $perPage = 10;

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
