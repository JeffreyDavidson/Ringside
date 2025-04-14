<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Actions\Titles\ActivateAction;
use App\Actions\Titles\DeactivateAction;
use App\Actions\Titles\RestoreAction;
use App\Actions\Titles\RetireAction;
use App\Actions\Titles\UnretireAction;
use App\Builders\TitleBuilder;
use App\Enums\ActivationStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Exceptions\CannotBeDeactivatedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeUnretiredException;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Title;
use App\View\Columns\FirstActivationDateColumn;
use App\View\Filters\FirstActivationFilter;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

final class TitlesTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'titles';

    protected string $routeBasePath = 'titles';

    protected string $resourceName = 'titles';

    /**
     * @return TitleBuilder<Title>
     */
    public function builder(): TitleBuilder
    {
        return Title::query()
            ->with(['currentActivation'])
            ->oldest('name');
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
            Column::make(__('titles.name'), 'name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            // Column::make(__('titles.current_champion'), 'champion_name'),
            FirstActivationDateColumn::make(__('activations.started_at')),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        /** @var array<string, string> $statuses */
        $statuses = collect(ActivationStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstActivationFilter::make('Activation Date')->setFields('activations', 'titles_activations.started_at', 'titles_activations.ended_at'),
        ];
    }

    public function delete(Title $title): void
    {
        $this->deleteModel($title);
    }

    public function activate(Title $title): RedirectResponse
    {
        Gate::authorize('activate', $title);

        try {
            resolve(ActivateAction::class)->handle($title);
        } catch (CannotBeActivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Deactivates a title.
     */
    public function deactivate(Title $title): RedirectResponse
    {
        Gate::authorize('deactivate', $title);

        try {
            resolve(DeactivateAction::class)->handle($title);
        } catch (CannotBeDeactivatedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Restores a title.
     */
    public function restore(int $titleId): RedirectResponse
    {
        $title = Title::onlyTrashed()->findOrFail($titleId);

        Gate::authorize('restore', $title);

        try {
            resolve(RestoreAction::class)->handle($title);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Retires a title.
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
     * Unretire a title.
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
}
