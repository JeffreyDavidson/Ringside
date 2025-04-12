<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\StableWrestler;
use Illuminate\Database\Eloquent\Builder;

class PreviousStablesTable extends BasePreviousStablesTable
{
    protected string $databaseTableName = 'stables_wrestlers';

    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    /**
     * @return Builder<StableWrestler>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new \Exception("You didn't specify a wrestler");
        }

        return StableWrestler::query()
            ->where('wrestler_id', $this->wrestlerId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_wrestlers.stable_id as stable_id',
        ]);
    }
}
