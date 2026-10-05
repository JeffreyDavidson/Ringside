<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Data\Matches\EventMatchData;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

final class MatchConfigurationRequirements
{
    public function ensureComplete(EventMatchData $data): void
    {
        if ($data->sides->isEmpty()) {
            throw InvalidMatchConfigurationException::missingCompetitors();
        }

        if ($data->referees->isEmpty()) {
            throw InvalidMatchConfigurationException::missingReferees();
        }
    }

    /**
     * Only the event's own promotion may be booked on its card: every referee, title, wrestler and tag team
     * must share the event's promotion_id. Global administrators are not scoped to a promotion, so this is
     * the authority that keeps their bookings inside the event's promotion.
     *
     * @throws InvalidMatchConfigurationException
     */
    public function ensureWithinEventPromotion(Event $event, EventMatchData $data): void
    {
        $participantsByType = [
            'referees' => $data->referees->all(),
            'titles' => $data->titles->all(),
            'wrestlers' => $data->sides->flatMap(fn (array $side): array => $side['wrestlers'] ?? [])->all(),
            'tag teams' => $data->sides->flatMap(fn (array $side): array => $side['tag_teams'] ?? [])->all(),
        ];

        foreach ($participantsByType as $entityType => $participants) {
            $this->ensureParticipantsWithinEventPromotion($event, $entityType, $participants);
        }
    }

    /**
     * @param  array<int, Referee|Title|TagTeam|Wrestler>  $participants
     *
     * @throws InvalidMatchConfigurationException
     */
    public function ensureParticipantsWithinEventPromotion(Event $event, string $entityType, array $participants): void
    {
        foreach ($participants as $participant) {
            if ($participant->promotion_id !== $event->promotion_id) {
                throw InvalidMatchConfigurationException::outsideEventPromotion($entityType);
            }
        }
    }
}
