<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Builders\Titles\TitleBuilder;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Components\Tables\Columns\FirstActivityPeriodColumn;
use App\Livewire\Components\Tables\Filters\FirstActivityPeriodFilter;
use App\Livewire\Titles\Components\TitleActionsComponent;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

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
 *
 * @example
 * ```php
 * // In a Blade template
 * <livewire:titles.tables.titles-table />
 *
 * // The table displays titles like:
 * // - WWE Championship Title (Active, First Activated: 2020-01-15)
 * // - Intercontinental Title (Retired, First Activated: 2019-06-01)
 * // - Tag Team Titles (Inactive, First Activated: 2021-03-10)
 * ```
 */
class TitlesTable extends BaseTableWithActions
{
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
     *
     * @example
     * ```php
     * // The query retrieves titles with their activity status:
     * // SELECT titles.*, activity_periods.* FROM titles
     * // LEFT JOIN activity_periods ON titles.id = activity_periods.title_id
     * // WHERE activity_periods.ended_at IS NULL
     * // ORDER BY titles.name ASC
     * ```
     */
    public function builder(): TitleBuilder
    {
        return Title::query()
            ->with(['currentActivityPeriod'])
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
     *
     * @example
     * ```php
     * // Table displays columns:
     * // | Name                    | Status   | First Activation |
     * // | WWE Championship Title  | Active   | 2020-01-15      |
     * // | Intercontinental Title  | Retired  | 2019-06-01      |
     * // | Tag Team Titles         | Inactive | 2021-03-10      |
     * ```
     */
    public function columns(): array
    {
        return [
            Column::make(__('titles.name'), 'name')
                ->searchable(),
            Column::make(__('core.status'), 'status')
                ->label(fn ($row) => $row->status?->label() ?? 'Unknown')
                ->excludeFromColumnSelect(),
            // Column::make(__('titles.current_champion'), 'champion_name'),
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
     *
     * @example
     * ```php
     * // Available filters:
     * // - Status: [All, Undebuted, Active, Inactive, Pending Debut]
     * // - Activation Date: [Date range picker for first activation]
     * ```
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
                    /** @var TitleBuilder $builder */
                    match ($value) {
                        'undebuted' => $builder->undebuted(),
                        'active' => $builder->active(),
                        'inactive' => $builder->inactive(),
                        'with_pending_debut' => $builder->withPendingDebut(),
                        default => null,
                    };
                }),
            FirstActivityPeriodFilter::make('Activation Date')->setFields('activations', 'titles_activations.started_at', 'titles_activations.ended_at'),
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
        $this->deleteModel($title);
    }

    /**
     * Activate a title for competition.
     *
     * @param  Title  $title  The title to activate
     * @return RedirectResponse Redirect response with success or error message
     */
    public function activate(Title $title): RedirectResponse
    {
        return $this->executeAction(
            DebutAction::class,
            $title,
            'debut'
        );
    }

    /**
     * Pull a title from active competition.
     *
     * @param  Title  $title  The title to pull
     * @return RedirectResponse Redirect response with success or error message
     */
    public function deactivate(Title $title): RedirectResponse
    {
        return $this->executeAction(
            PullAction::class,
            $title,
            'pull'
        );
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
        return $this->executeAction(
            RetireAction::class,
            $title,
            'retire'
        );
    }

    /**
     * Unretire a previously retired title.
     *
     * @param  Title  $title  The title to unretire
     * @return RedirectResponse Redirect response with success or error message
     */
    public function unretire(Title $title): RedirectResponse
    {
        return $this->executeAction(
            UnretireAction::class,
            $title,
            'unretire'
        );
    }

    public function handleTitleAction(string $action, int $titleId): void
    {
        $title = Title::findOrFail($titleId);

        // Delegate to the TitleActionsComponent
        $actionsComponent = new TitleActionsComponent();
        $actionsComponent->title = $title;

        match ($action) {
            'debut' => $actionsComponent->debut(),
            'pull' => $actionsComponent->deactivate(),
            'reinstate' => $actionsComponent->reinstate(),
            'retire' => $actionsComponent->retire(),
            'unretire' => $actionsComponent->unretire(),
            'restore' => $actionsComponent->restore(),
            default => null,
        };
    }
}
