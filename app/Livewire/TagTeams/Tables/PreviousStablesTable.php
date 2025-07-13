<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Stables\StableMember;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousStablesTable extends BasePreviousStablesTable
{
    protected string $databaseTableName = 'stables_members';

    public ?int $tagTeamId;

    /**
     * @return Builder<StableMember>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new Exception("You didn't specify a tag team");
        }

        return StableMember::query()
            ->where('member_id', $this->tagTeamId)
            ->where('member_type', 'tagTeam')
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
