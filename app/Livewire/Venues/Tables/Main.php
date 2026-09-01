<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Tables;

use App\Actions\Venues\DeleteAction;
use App\Actions\Venues\RestoreAction;
use App\Builders\Events\VenueBuilder;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Concerns\ExecutesBusinessActions;
use App\Livewire\Table\Column;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Venue> */
class Main extends BaseTable
{
    use ExecutesBusinessActions;

    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'venues';

    protected string $routeBasePath = 'venues';

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
        Gate::authorize('viewAny', Venue::class);
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
        Gate::authorize('delete', $venue);

        $this->executeBusinessAction(function () use ($deleteAction, $venue): void {
            $deleteAction->handle($venue);
        }, __('venues.actions.deleted'));
    }

    /**
     * Restore a deleted venue.
     */
    public function restore(int $venueId, RestoreAction $restoreAction): void
    {
        $venue = Venue::onlyTrashed()->findOrFail($venueId);

        Gate::authorize('restore', $venue);

        if ($this->executeBusinessAction(function () use ($restoreAction, $venue): void {
            $restoreAction->handle($venue);
        }, __('venues.actions.restored'))) {
            $this->redirectRoute('venues.index');
        }

    }
}
