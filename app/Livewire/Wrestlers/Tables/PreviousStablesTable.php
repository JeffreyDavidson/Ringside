<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Stables\StableMember;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousStablesTable extends BasePreviousStablesTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    public string $databaseTableName = 'stables_members';

    /**
     * @return Builder<StableMember>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new Exception("You didn't specify a wrestler");
        }

        return StableMember::query()
            ->where('member_id', $this->wrestlerId)
            ->where('member_type', 'wrestler')
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_members.stable_id as stable_id',
        ]);
    }
}
