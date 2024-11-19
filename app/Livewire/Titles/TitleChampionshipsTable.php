<?php

declare(strict_types=1);

namespace App\Livewire\Titles;

use App\Models\Title;
use Illuminate\Contracts\View\View;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class TitleChampionshipsTable extends DataTableComponent
{
    /**
     * Undocumented variable.
     */
    public Title $title;

    /**
     * Undocumented function.
     */
    public function mount(Title $title): void
    {
        $this->title = $title;
    }

    public function configure(): void {}

    public function columns(): array
    {
        return [
            Column::make(__('title_championships.current_champion'), 'current_champion'),
            Column::make(__('title_championships.former_champion'), 'former_champion'),
            Column::make(__('title_championships.dates_held'), 'dates_held'),
            Column::make(__('title_championships.reign_length'), 'reign_length'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function render(): View
    {
        $query = $this->title
            ->championships()
            ->latest('won_at')
            ->latest('id');

        $titleChampionships = $query->paginate();

        return view('livewire.titles.title-championships-list', [
            'titleChampionships' => $titleChampionships,
        ]);
    }
}
