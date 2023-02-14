<?php

declare(strict_types=1);

namespace App\Http\Livewire\Titles;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use App\Http\Livewire\BaseComponent;
use App\Models\Title;
use App\Models\TitleChampionship;

/**
 * @property \Illuminate\Database\Eloquent\Collection $rows
 * @property \Illuminate\Database\Eloquent\Builder $rowsQuery
 */
class TitleChampionshipsList extends BaseComponent
{
    /**
     * Undocumented variable.
     *
     * @var \App\Models\Title
     */
    public Title $title;

    /**
     * List of filters that are allowed.
     *
     * @var array<string, string>
     */
    public $filters = [
        'search' => '',
    ];

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function mount(Title $title): void
    {
        $this->title = $title;
    }

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRowsQueryProperty(): Builder
    {
        return TitleChampionship::where('title_id', $this->title->id)->latest('won_at');
    }

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRowsProperty(): LengthAwarePaginator
    {
        return $this->applyPagination($this->rowsQuery);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function render(): View
    {
        return view('livewire.titles.title-championships-list', [
            'titleChampionships' => $this->rows,
        ]);
    }
}
