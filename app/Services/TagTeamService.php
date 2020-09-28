<?php

namespace App\Services;

use App\Models\TagTeam;

class TagTeamService
{
    /**
     * Creates a new tag team.
     *
     * @param  array $data
     * @return \App\Models\TagTeam
     */
    public function create(array $data): TagTeam
    {
        $tagTeam = TagTeam::create(['name' => $data['name'], 'signature_move' => $data['signature_move']]);

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
}
