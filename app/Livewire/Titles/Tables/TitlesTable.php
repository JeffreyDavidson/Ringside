<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Tables;

use App\Builders\TitleBuilder;
use App\Enums\TitleStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasFirstActivationDateColumn;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasFirstActivationDateFilter;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Title;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class TitlesTable extends BaseTableWithActions
{
    use HasFirstActivationDateColumn, HasFirstActivationDateFilter, HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'titles';

    protected string $routeBasePath = 'titles';

    protected string $resourceName = 'titles';

    public function builder(): TitleBuilder
    {
        return Title::query()
            ->with(['currentActivation'])
            ->oldest('name')
            ->when($this->getAppliedFilterWithValue('Status'), fn ($query, $status) => $query->where('status', $status));
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
            $this->getDefaultFirstActivationDateColumn(),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        $statuses = collect(TitleStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            $this->getDefaultFirstActivationmDateFilter(),
        ];
    }

    public function delete(Title $title): void
    {
        $this->deleteModel($title);
    }
}
