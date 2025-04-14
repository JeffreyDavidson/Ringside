<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousTitleChampionshipsTable;
use App\Models\TagTeam;
use App\Models\TitleChampionship;
use Illuminate\Database\Eloquent\Builder;

final class PreviousTitleChampionshipsTable extends BasePreviousTitleChampionshipsTable
{
    /**
     * Tag Team to use for component.
     */
    public ?int $tagTeamId;

    /**
     * @return Builder<TitleChampionship>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new \Exception("You didn't specify a tag team");
        }

        return TitleChampionship::query()
            ->whereHasMorph(
                'previousChampion',
                [TagTeam::class],
                function (Builder $query) {
                    $query->whereIn('id', [$this->tagTeamId]);
                }
            );
    }
}
