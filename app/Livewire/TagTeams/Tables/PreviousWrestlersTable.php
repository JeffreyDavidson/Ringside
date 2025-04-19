<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousWrestlersTable;
use App\Models\TagTeamPartner;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousWrestlersTable extends BasePreviousWrestlersTable
{
    protected string $databaseTableName = 'tag_team_wrestler';

    public ?int $tagTeamId;

    /**
     * @return Builder<TagTeamPartner>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new Exception("You didn't specify a tag team");
        }

        return TagTeamPartner::query()
            ->where('tag_team_id', $this->tagTeamId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_team_wrestler.wrestler_id as wrestler_id',
        ]);
    }
}
