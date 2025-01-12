<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Models\Title;
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

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('title_championships.current_champion'), 'current_champion'),
            Column::make(__('title_championships.former_champion'), 'former_champion'),
            Column::make(__('title_championships.dates_held'), 'dates_held'),
            Column::make(__('title_championships.reign_length'), 'reign_length'),
        ];
    }
}
