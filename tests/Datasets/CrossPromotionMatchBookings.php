<?php

declare(strict_types=1);

use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Collection;

/*
 * Each case builds a bookable match for an event of the $home promotion with exactly one
 * participant from the $foreign promotion. The second value is the entity type named in the
 * action's error, the third the match form field that rejects the foreign id.
 */
dataset('cross promotion match bookings', [
    'a wrestler' => [
        fn (Promotion $home, Promotion $foreign): EventMatchData => new EventMatchData(
            MatchType::Singles,
            Referee::factory()->bookable()->for($home, 'promotion')->count(1)->create(),
            new Collection,
            collect([
                1 => ['wrestlers' => [Wrestler::factory()->bookable()->for($home, 'promotion')->create()]],
                2 => ['wrestlers' => [Wrestler::factory()->bookable()->for($foreign, 'promotion')->create()]],
            ]),
            null,
        ),
        'wrestlers',
        'form.competitors.1.wrestlers.0',
    ],
    'a tag team' => [
        fn (Promotion $home, Promotion $foreign): EventMatchData => new EventMatchData(
            MatchType::TagTeam,
            Referee::factory()->bookable()->for($home, 'promotion')->count(1)->create(),
            new Collection,
            collect([
                1 => ['tag_teams' => [TagTeam::factory()->bookable()->for($home, 'promotion')->create()]],
                2 => ['tag_teams' => [TagTeam::factory()->bookable()->for($foreign, 'promotion')->create()]],
            ]),
            null,
        ),
        'tag teams',
        'form.competitors.1.tag_teams.0',
    ],
    'a referee' => [
        fn (Promotion $home, Promotion $foreign): EventMatchData => new EventMatchData(
            MatchType::Singles,
            Referee::factory()->bookable()->for($foreign, 'promotion')->count(1)->create(),
            new Collection,
            collect([
                1 => ['wrestlers' => [Wrestler::factory()->bookable()->for($home, 'promotion')->create()]],
                2 => ['wrestlers' => [Wrestler::factory()->bookable()->for($home, 'promotion')->create()]],
            ]),
            null,
        ),
        'referees',
        'form.referees.0',
    ],
    'a title' => [
        fn (Promotion $home, Promotion $foreign): EventMatchData => new EventMatchData(
            MatchType::Singles,
            Referee::factory()->bookable()->for($home, 'promotion')->count(1)->create(),
            Title::factory()->active()->singles()->for($foreign, 'promotion')->count(1)->create(),
            collect([
                1 => ['wrestlers' => [Wrestler::factory()->bookable()->for($home, 'promotion')->create()]],
                2 => ['wrestlers' => [Wrestler::factory()->bookable()->for($home, 'promotion')->create()]],
            ]),
            null,
        ),
        'titles',
        'form.titles.0',
    ],
]);
