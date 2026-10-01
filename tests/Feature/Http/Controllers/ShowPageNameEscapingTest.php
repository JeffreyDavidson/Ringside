<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;

/**
 * @param  array<int, string>  $names
 * @param  TestResponse<Response>  $response
 */
function assertNamesRenderEscapedOnce(TestResponse $response, array $names): void
{
    foreach ($names as $name) {
        $response->assertSee($name);
    }

    $response
        ->assertDontSeeHtml('&amp;#039;')
        ->assertDontSeeHtml('&amp;amp;');
}

describe('Show pages escape related names exactly once', function () {
    test('the wrestler page escapes its tag team, manager, stable and title links', function () {
        $wrestler = Wrestler::factory()
            ->employed()
            ->onCurrentTagTeam(TagTeam::factory()->create(['name' => "O'Neil & Sons"]))
            ->create();
        $manager = Manager::factory()->create(['first_name' => 'Travis', 'last_name' => "O'Keefe"]);
        $wrestler->managers()->attach($manager, ['hired_at' => now()->subDay()]);
        $wrestler->stables()->attach(Stable::factory()->create(['name' => "Hart & O'Hara"]), ['joined_at' => now()->subDay()]);
        TitleChampionship::factory()
            ->for(Title::factory()->create(['name' => "Champion's & Challenger's Belt"]), 'title')
            ->forWrestler($wrestler)
            ->current()
            ->create();

        $response = actingAs(administrator())
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk();

        assertNamesRenderEscapedOnce($response, [
            "O'Neil & Sons",
            "Travis O'Keefe",
            "Hart & O'Hara",
            "Champion's & Challenger's Belt",
        ]);
    });

    test('the manager page escapes its wrestler and tag team links', function () {
        $manager = Manager::factory()->employed()->create();
        $manager->wrestlers()->attach(Wrestler::factory()->create(['name' => "Sean O'Neil & Sons"]), ['hired_at' => now()->subDay()]);
        $manager->tagTeams()->attach(TagTeam::factory()->create(['name' => "O'Neil & Sons"]), ['hired_at' => now()->subDay()]);

        $response = actingAs(administrator())
            ->get(route('managers.show', $manager))
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["Sean O'Neil & Sons", "O'Neil & Sons"]);
    });

    test('the tag team page escapes its wrestler, manager, stable and title links', function () {
        $tagTeam = TagTeam::factory()->employed()->create();
        $tagTeam->currentWrestlers->firstOrFail()->update(['name' => "O'Neil & Sons"]);
        $tagTeam->currentWrestlers->skip(1)->firstOrFail()->update(['name' => "D'Angelo & Sons"]);
        $tagTeam->managers()->attach(
            Manager::factory()->create(['first_name' => 'Travis', 'last_name' => "O'Keefe"]),
            ['hired_at' => now()->subDay()],
        );
        $tagTeam->stables()->attach(Stable::factory()->create(['name' => "Hart & O'Hara"]), ['joined_at' => now()->subDay()]);
        TitleChampionship::factory()
            ->for(Title::factory()->create(['name' => "Champion's & Challenger's Belt"]), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        $response = actingAs(administrator())
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk();

        assertNamesRenderEscapedOnce($response, [
            "O'Neil & Sons",
            "D'Angelo & Sons",
            "Travis O'Keefe",
            "Hart & O'Hara",
            "Champion's & Challenger's Belt",
        ]);
    });

    test('the stable page escapes its wrestler and tag team links', function () {
        $stable = Stable::factory()->active()->create();
        $stable->currentWrestlers->firstOrFail()->update(['name' => "O'Neil & Sons"]);
        $stable->currentTagTeams->firstOrFail()->update(['name' => "Hart & O'Hara"]);

        $response = actingAs(administrator())
            ->get(route('stables.show', $stable))
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["O'Neil & Sons", "Hart & O'Hara"]);
    });

    test('the event page escapes its venue link', function () {
        $event = Event::factory()->atVenue(Venue::factory()->create(['name' => "O'Neil & Sons Arena"]))->create();

        $response = actingAs(administrator())
            ->get(route('events.show', $event))
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["O'Neil & Sons Arena"]);
    });

    test('the title page escapes its current champion link', function () {
        $title = Title::factory()->create();
        TitleChampionship::factory()
            ->for($title, 'title')
            ->forWrestler(Wrestler::factory()->create(['name' => "O'Neil & Sons"]))
            ->current()
            ->create();

        $response = actingAs(administrator())
            ->get(route('titles.show', $title))
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["O'Neil & Sons"]);
    });
});
