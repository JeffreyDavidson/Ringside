<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Builders\Titles\TitleBuilder;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBePulledException;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Exceptions\Titles\CannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Titles\Title;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Livewire table component for managing championship titles.
 *
 * This table displays all championship titles in the system with their current
 * status, activation dates, and provides actions for title lifecycle management.
 * It supports filtering by activation status and date ranges, along with search
 * functionality for title names.
 *
 * The table integrates with various title management actions including activation,
 * deactivation, retirement, restoration, and deletion through a comprehensive
 * action system with proper authorization and error handling.
 */
/** @extends BaseTable<Title> */
class Main extends BaseTable
{
    /**
     * Enable action column for this table.
     */
    protected bool $showActionColumn = true;

    /**
     * The database table name for the main query.
     *
     * @var string The name of the titles table
     */
    protected string $databaseTableName = 'titles';

    /**
     * The base route path for title-related actions.
     *
     * @var string The route prefix for title management
     */
    protected string $routeBasePath = 'titles';

    /**
     * The resource name for authorization and routing.
     *
     * @var string The resource identifier for titles
     */
    protected string $resourceName = 'titles';

    /**
     * Build the query for retrieving titles with their relationships.
     *
     * Creates a query that fetches all titles with their current activity period
     * information, ordered alphabetically by name. The current activity period
     * relationship provides access to activation status and dates.
     *
     * @return TitleBuilder<Title> Query builder for titles with eager loaded relationships
     */
    public function builder(): TitleBuilder
    {
        return Title::query()
            ->withActivityStatusState()
            ->with(['firstActivityPeriod', 'currentChampionship.champion'])
            ->oldest('name');
    }

    /**
     * Configure additional table settings and behavior.
     *
     * Includes authorization check to ensure only authorized users can access
     * the titles table, plus any additional table-specific configuration.
     */
    public function configure(): void
    {
        Gate::authorize('viewList', Title::class);
    }

    /**
     * Define the table columns for title display.
     *
     * Configures the columns shown in the titles table including the title name,
     * current status, and first activation date. The status column shows whether
     * a title is currently active, inactive, or retired.
     *
     * @return array<int, Column> Array of column definitions for the table
     */
    public function columns(): array
    {
        return [
            Column::make(__('titles.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn (Title $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            Column::make(__('titles.current_champion'), 'champion_name')
                ->label(fn (Title $row) => TitleChampionshipQuery::currentChampion($row)->name ?? 'Vacant'),
            FirstActivityPeriodColumn::make(__('activations.started_at')),
        ];
    }

    /**
     * Define the available filters for the titles table.
     *
     * Provides filtering options including activity status filter (Undebuted,
     * Active, Inactive, Pending Debut) and first activation date range filter for finding
     * titles activated within specific time periods.
     *
     * @return array<int, Filter> Array of filter definitions for the table
     */
    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status')
                ->options([
                    '' => 'All',
                    'undebuted' => 'Undebuted',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'with_pending_debut' => 'Pending Debut',
                ])
                ->filter(function (Builder $builder, string $value): void {
                    /** @var TitleBuilder<Title> $builder */
                    match ($value) {
                        'undebuted' => $builder->undebuted(),
                        'active' => $builder->active(),
                        'inactive' => $builder->inactive(),
                        'with_pending_debut' => $builder->withPendingDebut(),
                        default => null,
                    };
                }),
            FirstActivityPeriodFilter::make('Activation Date')->setFields('activityPeriods', 'activity_periods.started_at', 'activity_periods.ended_at'),
        ];
    }

    /**
     * Delete a title from the system.
     *
     * Performs soft deletion of the specified title using the base table's
     * delete functionality. The title will be moved to trash and can be
     * restored later if needed.
     *
     * @param  Title  $title  The title to delete
     */
    public function delete(Title $title): void
    {
        Gate::authorize('delete', $title);

        resolve(DeleteAction::class)->handle($title);
        session()->flash('status', 'Title successfully deleted.');
    }

    /**
     * Debut a title for competition.
     *
     * @param  Title  $title  The title to debut
     * @return RedirectResponse Redirect response with success or error message
     */
    public function debut(Title $title): RedirectResponse
    {
        Gate::authorize('debut', $title);

        try {
            resolve(DebutAction::class)->handle($title);
        } catch (CannotBeDebutedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Put a title on hold (remove from active competition).
     *
     * @param  Title  $title  The title to put on hold
     * @return RedirectResponse Redirect response with success or error message
     */
    public function putOnHold(Title $title): RedirectResponse
    {
        Gate::authorize('pull', $title);

        try {
            resolve(PullAction::class)->handle($title);
        } catch (CannotBePulledException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Restore a previously deleted title.
     *
     * @param  int  $titleId  The ID of the deleted title to restore
     * @return RedirectResponse Redirect response with success or error message
     */
    public function restore(int $titleId): RedirectResponse
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        Gate::authorize('restore', $title);

        resolve(RestoreAction::class)->handle($title);

        return back();
    }

    /**
     * Retire a title permanently.
     *
     * @param  Title  $title  The title to retire
     * @return RedirectResponse Redirect response with success or error message
     */
    public function retire(Title $title): RedirectResponse
    {
        Gate::authorize('retire', $title);

        try {
            resolve(RetireAction::class)->handle($title);
        } catch (CannotBeRetiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Unretire a previously retired title.
     *
     * @param  Title  $title  The title to unretire
     * @return RedirectResponse Redirect response with success or error message
     */
    public function unretire(Title $title): RedirectResponse
    {
        Gate::authorize('unretire', $title);

        try {
            resolve(UnretireAction::class)->handle($title);
        } catch (CannotBeUnretiredException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function reinstate(Title $title): RedirectResponse
    {
        Gate::authorize('reinstate', $title);

        try {
            resolve(ReinstateAction::class)->handle($title);
        } catch (CannotBeReinstatedException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return back();
    }

    public function handleTitleAction(string $action, int $titleId): void
    {
        $title = $action === 'restore'
            ? Title::onlyTrashed()->findOrFail($titleId)
            : Title::findOrFail($titleId);

        match ($action) {
            'debut' => $this->debut($title),
            'pull' => $this->putOnHold($title),
            'reinstate' => $this->reinstate($title),
            'retire' => $this->retire($title),
            'unretire' => $this->unretire($title),
            'restore' => $this->restore($titleId),
            default => null,
        };
    }
}
