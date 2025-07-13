<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousManagersTable;
use App\Models\TagTeams\TagTeamManager;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousManagersTable extends BasePreviousManagersTable
{
    protected string $databaseTableName = 'tag_teams_managers';

    /**
     * Tag Team to use for component.
     */
    public ?int $tagTeamId;

    /**
     * @return Builder<TagTeamManager>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new Exception("You didn't specify a tag team");
        }

        return TagTeamManager::query()
            ->where('tag_team_id', $this->tagTeamId)
            ->whereNotNull('fired_at')
            ->orderByDesc('hired_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_managers.manager_id',
        ]);
    }
}
