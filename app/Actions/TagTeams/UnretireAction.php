<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Unretire a tag team.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(TagTeam $tagTeam, ?Carbon $unretiredDate = null): void
    {
        throw_if($tagTeam->canBeUnretired(), CannotBeUnretiredException::class);

        $unretiredDate ??= now();

        $this->tagTeamRepository->unretire($tagTeam, $unretiredDate);

        $tagTeam->currentWrestlers->each(fn ($wrestler) => WrestlersUnretireAction::run($wrestler, $unretiredDate));

        $this->tagTeamRepository->employ($tagTeam, $unretiredDate);
    }
}
