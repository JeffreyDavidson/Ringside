<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class DataTable extends Component
{
    /**
     * The collection of models to be passed to view.
     *
     * @var \Illuminate\Pagination\LengthAwarePaginator
     */
    protected $collection;

    /**
     * Create a new component instance.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $collection
     * @return void
     */
    public function __construct(LengthAwarePaginator $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.data-table');
    }
}
