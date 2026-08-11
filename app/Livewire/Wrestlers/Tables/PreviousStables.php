<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PreviousStables extends BasePreviousStablesTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    public string $databaseTableName = 'stables_wrestlers';

    /**
     * @return Builder<Stable>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        return Stable::query()
            ->join('stables_wrestlers', 'stables.id', '=', 'stables_wrestlers.stable_id')
            ->where('stables_wrestlers.wrestler_id', $this->wrestlerId)
            ->whereNotNull('stables_wrestlers.left_at')
            ->select('stables.*')
            ->addSelect([
                'joined_at' => DB::raw('stables_wrestlers.joined_at'),
                'left_at' => DB::raw('stables_wrestlers.left_at'),
            ])
            ->orderByDesc('stables_wrestlers.joined_at');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPaginationEnabled()
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setLoadingPlaceholderContent('Loading')
            ->setLoadingPlaceholderEnabled();
    }
}
