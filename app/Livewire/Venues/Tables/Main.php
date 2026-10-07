<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Tables;

use App\Actions\Venues\DeleteAction;
use App\Builders\Events\VenueBuilder;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Table\Column;
use App\Models\Events\Venue;

/** @extends BaseTable<Venue> */
class Main extends BaseTable
{
    #[\Override]
    protected bool $showActionColumn = true;

    #[\Override]
    protected string $modelClass = Venue::class;

    #[\Override]
    protected string $actionsView = 'components.tables.columns.venue-actions';

    #[\Override]
    protected string $actionsRowVariable = 'venue';

    #[\Override]
    protected string $databaseTableName = 'venues';

    #[\Override]
    protected string $routeBasePath = 'venues';

    #[\Override]
    protected string $resourceName = 'venues';

    /**
     * @return VenueBuilder<Venue>
     */
    public function builder(): VenueBuilder
    {
        return Venue::query()
            ->alphabetical();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->emptyStateTitle = __('venues.empty_title');
        $this->emptyStateDescription = __('venues.empty_description');
        $this->emptyStateIcon = 'heroicon-o-building-office-2';
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('venues.name'), 'name')
                ->searchable(),
            Column::make(__('venues.street_address'), 'street_address')
                ->searchable(),
            Column::make(__('venues.city'), 'city')
                ->searchable(),
            Column::make(__('venues.state'), 'state')
                ->searchable(),
            Column::make(__('venues.zipcode'), 'zipcode'),
        ];
    }

    public function delete(Venue $venue, DeleteAction $deleteAction): void
    {
        $this->deleteRecord($venue, $deleteAction->handle(...), __('venues.actions.deleted'));
    }
}
