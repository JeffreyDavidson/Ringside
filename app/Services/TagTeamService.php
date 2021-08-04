<?php

namespace App\Services;

use App\Exceptions\CannotBeEmployedException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Exceptions\NotEnoughMembersException;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use Carbon\Carbon;
use Exception;

class TagTeamService
{
    protected $tagTeamRepository;

    public function __construct(TagTeamRepository $tagTeamRepository)
    {
        $this->tagTeamRepository = $tagTeamRepository;
    }

    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\TagTeam
     */
    public function create(array $data): TagTeam
    {
        $tagTeam = $this->tagTeamRepository->create($data);

        $this->addTagTeamPartners($tagTeam, $data['wrestlers']);

        if ($data['started_at']) {
            $tagTeam->employ($data['started_at']);
        }

        return $tagTeam;
    }

    /**
     * Updates a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  array $data
     * @return \App\Models\TagTeam
     */
    public function update(TagTeam $tagTeam, array $data): TagTeam
    {
        $tagTeam->update(['name' => $data['name'], 'signature_move' => $data['signature_move']]);

        $this->updateTagTeamPartners($tagTeam, $data['wrestlers']);

        if ($data['started_at'] && ! $tagTeam->isCurrentlyEmployed()) {
            $tagTeam->employ($data['started_at']);
        }

        return $tagTeam;
    }

    /**
     * Add tag team partners to a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  array $wrestlerIds
     * @return \App\Models\TagTeam
     */
    public function addTagTeamPartners(TagTeam $tagTeam, array $wrestlerIds): TagTeam
    {
        if ($wrestlerIds) {
            $tagTeam->addWrestlers($wrestlerIds, now());
        }

        return $tagTeam;
    }

    /**
     * Add wrestlers to a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  array  $wrestlers
     * @param  string|null $dateJoined
     *
     * @throws Exception
     *
     * @return $this
     */
    public function addWrestlers($tagTeam, $wrestlerIds, $dateJoined = null)
    {
        if (count($wrestlerIds) !== self::MAX_WRESTLERS_COUNT) {
            throw NotEnoughMembersException::forTagTeam();
        }

        $dateJoined ?? now();

        $tagTeam->wrestlers()->sync([
            $wrestlerIds[0] => ['joined_at' => $dateJoined],
            $wrestlerIds[1] => ['joined_at' => $dateJoined],
        ]);

        return $this;
    }

    /**
     * Update a tag team with tag team partners.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  array $wrestlerIds
     * @return \App\Models\TagTeam
     */
    public function updateTagTeamPartners(TagTeam $tagTeam, array $wrestlerIds): TagTeam
    {
        if ($tagTeam->currentWrestlers->isEmpty()) {
            if ($wrestlerIds) {
                foreach ($wrestlerIds as $wrestlerId) {
                    $tagTeam->currentWrestlers()->attach($wrestlerId, ['joined_at' => now()]);
                }
            }
        } else {
            $currentTagTeamPartners = collect($tagTeam->currentWrestlers->modelKeys());
            $suggestedTagTeamPartners = collect($wrestlerIds);
            $formerTagTeamPartners = $currentTagTeamPartners->diff($suggestedTagTeamPartners);
            $newTagTeamPartners = $suggestedTagTeamPartners->diff($currentTagTeamPartners);

            $now = now();

            foreach ($formerTagTeamPartners as $tagTeamPartner) {
                $tagTeam->currentWrestlers()->updateExistingPivot($tagTeamPartner, ['left_at' => $now]);
            }

            foreach ($newTagTeamPartners as $newTagTeamPartner) {
                $tagTeam->currentWrestlers()->attach(
                    $newTagTeamPartner,
                    ['joined_at' => $now]
                );
            }
        }

        return $tagTeam;
    }

    /**
     * Employ a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string|null $startAtDate
     * @return $this
     */
    public function employ($tagTeam, $startAtDate = null)
    {
        throw_unless($tagTeam->canBeEmployed(), new CannotBeEmployedException);

        $startAtDate = Carbon::parse($startAtDate)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $tagTeam->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startAtDate]);

        if ($tagTeam->currentWrestlers->every->isNotInEmployment()) {
            $tagTeam->currentWrestlers->each->employ($startAtDate);
        }

        $tagTeam->updateStatusAndSave();
    }

    /**
     * Release a tag team.
     *
     * @param  string|null $releasedAt
     * @return $this
     */
    public function release($tagTeam, $releasedAt = null)
    {
        throw_unless($tagTeam->canBeReleased(), new CannotBeReleasedException);
    }

    /**
     * Retire a tag team.
     *
     * @param \App\Models\TagTeam $tagTeam
     * @param  string|null $retiredAt
     * @return $this
     */
    public function retire($tagTeam, $retiredAt = null)
    {
        throw_unless($tagTeam->canBeRetired(), new CannotBeRetiredException);

        $retiredDate = $retiredAt ?: now();

        if ($tagTeam->isSuspended()) {
            $tagTeam->reinstate($retiredAt);
        }

        $tagTeam->currentEmployment()->update(['ended_at' => $retiredDate]);
        $tagTeam->retirements()->create(['started_at' => $retiredDate]);
        $tagTeam->currentWrestlers->each->retire($retiredDate);
        $tagTeam->updateStatusAndSave();
    }

    /**
     * Unretire a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire($tagTeam, $unretiredAt = null)
    {
        throw_unless($tagTeam->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now();

        $tagTeam->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $tagTeam->currentWrestlers->each->unretire($unretiredDate);
        $tagTeam->updateStatusAndSave();

        $tagTeam->employ($unretiredDate);
        $tagTeam->updateStatusAndSave();
    }

    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\TagTeam $tagTeam
     * @param  string|null $suspendedAt
     * @return void
     */
    public function suspend($tagTeam, $suspendedAt = null)
    {
        throw_unless($tagTeam->canBeSuspended(), new CannotBeSuspendedException);

        $suspendedDate = $suspendedAt ?: now();

        $tagTeam->suspensions()->create(['started_at' => $suspendedDate]);
        $tagTeam->currentWrestlers->each->suspend($suspendedDate);
        $tagTeam->updateStatusAndSave();
    }

    /**
     * Reinstate a tag team.
     *
     * @param \App\Models\TagTeam $tagTeam
     * @param  string|null $reinstatedAt
     * @return void
     */
    public function reinstate($tagTeam, $reinstatedAt = null)
    {
        throw_unless($tagTeam->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = $reinstatedAt ?: now();

        $tagTeam->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $tagTeam->currentWrestlers->each->reinstate($reinstatedDate);
        $tagTeam->updateStatusAndSave();
    }
}
