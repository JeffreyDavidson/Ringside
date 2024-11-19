<?php

declare(strict_types=1);

namespace App\Livewire\Venues;

use App\Livewire\Concerns\BaseTableTrait;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class VenuesTable extends DataTableComponent
{
    use BaseTableTrait;

    protected string $databaseTableName = 'venues';

    protected string $routeBasePath = 'venues';

    protected string $resourceName = 'venues';

    public function builder(): Builder
    {
        return Venue::query()
            ->orderBy('name');
    }

    public function configure(): void {}

    public function columns(): array
    {
        return [
            Column::make(__('venues.name'), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__('venues.address'), 'street_address'),
            Column::make(__('venues.city'), 'city'),
            Column::make(__('venues.state'), 'state'),
            Column::make(__('venues.zipcode'), 'zipcode'),
        ];
    }
}
