<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Tables;

use App\Actions\Venues\RestoreAction;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;

class VenuesTable extends BaseTableWithActions
{
    protected string $databaseTableName = 'venues';

    protected string $routeBasePath = 'venues';

    protected string $resourceName = 'venues';

    /**
     * @return Builder<Venue>
     */
    public function builder(): Builder
    {
        return Venue::query()
            ->orderBy('name');
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
            Column::make(__('venues.name'), 'name')
                ->searchable(),
            Column::make(__('venues.street_address'), 'street_address'),
            Column::make(__('venues.city'), 'city'),
            Column::make(__('venues.state'), 'state'),
            Column::make(__('venues.zipcode'), 'zipcode'),
        ];
    }

    public function delete(Venue $Venue): void
    {
        $this->deleteModel($Venue);
    }

    /**
     * Restore a deleted venue.
     */
    public function restore(int $venueId): RedirectResponse
    {
        $venue = Venue::onlyTrashed()->findOrFail($venueId);

        Gate::authorize('restore', $venue);

        resolve(RestoreAction::class)->handle($venue);

        return back();
    }
}
