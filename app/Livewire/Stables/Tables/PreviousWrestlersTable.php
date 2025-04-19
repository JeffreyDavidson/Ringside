<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Base\Tables\BasePreviousWrestlersTable;
use App\Models\StableWrestler;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousWrestlersTable extends BasePreviousWrestlersTable
{
    protected string $databaseTableName = 'stables_wrestlers';

    public ?int $stableId;

    /**
     * @return Builder<StableWrestler>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new Exception("You didn't specify a stable");
        }

        return StableWrestler::query()
            ->with(['wrestler'])
            ->where('stable_id', $this->stableId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_wrestlers.wrestler_id as wrestler_id',
        ]);
    }
}
