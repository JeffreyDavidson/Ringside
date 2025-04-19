<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousTagTeamsTable;
use App\Models\TagTeamPartner;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousTagTeamsTable extends BasePreviousTagTeamsTable
{
    /**
     * Wrestler to use for component.
     */
    public ?int $wrestlerId;

    protected string $databaseTableName = 'tag_teams';

    /**
     * @return Builder<TagTeamPartner>
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new Exception("You didn't specify a wrestler");
        }

        return TagTeamPartner::query()
            ->where('wrestler_id', $this->wrestlerId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_wrestlers.tag_team_id',
        ]);
    }
}
