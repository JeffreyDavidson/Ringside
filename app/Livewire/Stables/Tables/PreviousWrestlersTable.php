<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Stables\StableMember;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousWrestlersTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'wrestlers';

    protected string $databaseTableName = 'stables_members';

    public ?int $stableId;

    /**
     * @return Builder<StableMember>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new Exception("You didn't specify a stable");
        }

        return StableMember::query()
            ->with('member')
            ->where('stables_members.stable_id', $this->stableId)
            ->where('stables_members.member_type', 'wrestler')
            ->whereNotNull('stables_members.left_at')
            ->orderByDesc('stables_members.joined_at');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('wrestlers.name'))
                ->title(fn (StableMember $row) => $row->member?->name ?? 'Unknown')
                ->location(fn (StableMember $row) => $row->member ? route('wrestlers.show', $row->member) : '#'),
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }

    public function configure(): void
    {
        // Removed additional selects that were causing SQL conflicts
        // The polymorphic relationship handles member selection automatically
    }
}
