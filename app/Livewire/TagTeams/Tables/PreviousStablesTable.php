<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\StableTagTeam;
use Exception;
use Illuminate\Database\Eloquent\Builder;

final class PreviousStablesTable extends BasePreviousStablesTable
{
    protected string $databaseTableName = 'stables_tag_teams';

    public ?int $tagTeamId;

    /**
     * @return Builder<StableTagTeam>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new Exception("You didn't specify a tag team");
        }

        return StableTagTeam::query()
            ->where('tag_team_id', $this->tagTeamId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_tag_teams.stable_id as stable_id',
        ]);
    }
}
