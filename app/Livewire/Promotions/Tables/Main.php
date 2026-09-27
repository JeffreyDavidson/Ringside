<?php

declare(strict_types=1);

namespace App\Livewire\Promotions\Tables;

use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Table\Column;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<Promotion> */
class Main extends BaseTable
{
    #[\Override]
    protected string $databaseTableName = 'promotions';

    #[\Override]
    protected string $routeBasePath = 'promotions';

    #[\Override]
    protected string $resourceName = 'promotions';

    /** @return Builder<Promotion> */
    public function builder(): Builder
    {
        return Promotion::query()
            ->select('promotions.*')
            ->withCount('users')
            ->orderBy('name');
    }

    protected function configure(): void
    {
        Gate::authorize('viewAny', Promotion::class);
        $this->setSearchPlaceholder(__('promotions.search'));
        $this->emptyStateTitle = __('promotions.empty_title');
        $this->emptyStateDescription = __('promotions.empty_description');
        $this->emptyStateIcon = 'heroicon-o-building-office-2';
    }

    /** @return array<int, Column> */
    public function columns(): array
    {
        return [
            Column::make(__('promotions.name'), 'name')
                ->label(fn (Promotion $promotion) => view('components.tables.columns.promotion-name', [
                    'promotion' => $promotion,
                ])->render())
                ->html()
                ->searchable()
                ->sortable(),
            Column::make(__('promotions.slug'), 'slug')
                ->searchable()
                ->sortable(),
            Column::make(__('promotions.members'), 'users_count')
                ->label(fn (Promotion $promotion): string => (string) $promotion->users_count),
            Column::make(__('promotions.created'), 'created_at')
                ->label(fn (Promotion $promotion): string => $promotion->created_at?->toFormattedDateString() ?? '—'),
            Column::make(__('promotions.actions'))
                ->label(fn (Promotion $promotion) => view('components.tables.columns.promotion-actions', [
                    'promotion' => $promotion,
                ])->render())
                ->html()
                ->excludeFromColumnSelect(),
        ];
    }
}
