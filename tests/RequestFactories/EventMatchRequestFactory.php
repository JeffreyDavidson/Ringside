<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use App\Models\MatchType;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Worksome\RequestFactories\RequestFactory;

class EventMatchRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'match_type_id' => MatchType::first()->id,
            'referees' => [Referee::factory()->bookable()->create()->id],
            'titles' => [],
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
            'preview' => null,
        ];
    }

    /**
     * Indicate that the event match should be a singles match.
     */
    public function singles(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', 'singles')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a tag team match.
     */
    public function tagteam(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', 'tagteam')->first()->id,
            'competitors' => [
                0 => [
                    'tagteams' => [TagTeam::factory()->bookable()->create()->id],
                ],
                1 => [
                    'tagteams' => [TagTeam::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a triple threat match.
     */
    public function tripleThreat(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', 'triple')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                2 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a triangle match.
     */
    public function triangle(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', 'triangle')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                2 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a fatal 4 way match.
     */
    public function fatal4way(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', 'fatal4way')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                2 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
                3 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a six-man tag team match.
     */
    public function sixManTag(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', '6man')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                    'tagteams' => [TagTeam::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                    'tagteams' => [TagTeam::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be an eight-man tag team match.
     */
    public function eightManTag(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', '8man')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => Wrestler::factory()->bookable()->count(4)->create()->modelKeys(),
                ],
                1 => [
                    'wrestlers' => Wrestler::factory()->bookable()->count(4)->create()->modelKeys(),
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a ten-man tag team match.
     */
    public function tenManTag(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', '8man')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => Wrestler::factory()->bookable()->count(5)->create()->modelKeys(),
                ],
                1 => [
                    'wrestlers' => Wrestler::factory()->bookable()->count(5)->create()->modelKeys(),
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a two on one handicap match.
     */
    public function twoOnOneHandicap(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', '21handicap')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => Wrestler::factory()->bookable()->count(2)->create()->modelKeys(),
                ],
                1 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a two on one handicap match.
     */
    public function threeOnTwoHandicap(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', '21handicap')->first()->id,
            'competitors' => [
                0 => [
                    'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
                    'tagteams' => [TagTeam::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestlers' => Wrestler::factory()->bookable()->count(2)->create()->modelKeys(),
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a battle royal match.
     */
    public function battleRoyal(): static
    {
        $competitors = [];

        for ($x = 0; $x <= 9; $x++) {
            $competitors[$x] = [
                'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
            ];
        }

        return $this->state([
            'match_type_id' => MatchType::where('slug', 'battleRoyal')->first()->id,
            'competitors' => $competitors,
        ]);
    }

    /**
     * Indicate that the event match should be a royal rumble match.
     */
    public function royalRumble(): static
    {
        $competitors = [];

        for ($x = 0; $x <= 9; $x++) {
            $competitors[$x] = [
                'wrestlers' => [Wrestler::factory()->bookable()->create()->id],
            ];
        }

        return $this->state([
            'match_type_id' => MatchType::where('slug', 'royalRumble')->first()->id,
            'competitors' => $competitors,
        ]);
    }

    /**
     * Indicate that the event match should be a tornado tag team match.
     */
    public function tornadotag(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', 'tornadotag')->first()->id,
            'competitors' => [
                0 => [
                    'tagteams' => [TagTeam::factory()->bookable()->create()->id],
                ],
                1 => [
                    'tagteams' => [TagTeam::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the event match should be a tag team match.
     */
    public function gauntlet(): static
    {
        return $this->state([
            'match_type_id' => MatchType::where('slug', 'gauntlet')->first()->id,
            'competitors' => [
                0 => [
                    'wrestler' => [Wrestler::factory()->bookable()->create()->id],
                ],
                1 => [
                    'wrestler' => [Wrestler::factory()->bookable()->create()->id],
                ],
                2 => [
                    'wrestler' => [Wrestler::factory()->bookable()->create()->id],
                ],
                3 => [
                    'wrestler' => [Wrestler::factory()->bookable()->create()->id],
                ],
                4 => [
                    'wrestler' => [Wrestler::factory()->bookable()->create()->id],
                ],
                5 => [
                    'wrestler' => [Wrestler::factory()->bookable()->create()->id],
                ],
            ],
        ]);
    }
}
