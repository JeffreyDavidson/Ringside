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
use function Pest\Laravel\get;

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
        // Arrange
        $wrestler = Wrestler::factory()
            ->employed()
            ->onCurrentTagTeam(TagTeam::factory()
                ->create(['name' => "O'Neil & Sons"]))
            ->create();

        $manager = Manager::factory()
            ->create([
                'first_name' => 'Travis',
                'last_name' => "O'Keefe",
            ]);
        $wrestler->managers()
            ->attach($manager, ['hired_at' => now()->subDay()]);
        $wrestler->stables()
            ->attach(Stable::factory()
                ->create(['name' => "Hart & O'Hara"]), ['joined_at' => now()->subDay()]);

        TitleChampionship::factory()
            ->for(Title::factory()
                ->state(['name' => "Champion's & Challenger's Belt"]), 'title')
            ->forWrestler($wrestler)
            ->current()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk();

        assertNamesRenderEscapedOnce($response, [
            "O'Neil & Sons",
            "Travis O'Keefe",
            "Hart & O'Hara",
            "Champion's & Challenger's Belt",
        ]);
    });

    test('the manager page escapes its wrestler and tag team links', function () {
        // Arrange
        $manager = Manager::factory()
            ->employed()
            ->create();
        $manager->wrestlers()
            ->attach(Wrestler::factory()
                ->create(['name' => "Sean O'Neil & Sons"]), ['hired_at' => now()->subDay()]);
        $manager->tagTeams()
            ->attach(TagTeam::factory()
                ->create(['name' => "O'Neil & Sons"]), ['hired_at' => now()->subDay()]);

        actingAs(administrator());

        // Act
        $response = get(route('managers.show', $manager));

        // Assert
        $response
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["Sean O'Neil & Sons", "O'Neil & Sons"]);
    });

    test('the tag team page escapes its wrestler, manager, stable and title links', function () {
        // Arrange
        $tagTeam = TagTeam::factory()
            ->employed()
            ->create();
        $tagTeam->currentWrestlers->firstOrFail()
            ->update(['name' => "O'Neil & Sons"]);
        $tagTeam->currentWrestlers->skip(1)
            ->firstOrFail()
            ->update(['name' => "D'Angelo & Sons"]);
        $tagTeam->managers()
            ->attach(
                Manager::factory()
                    ->create([
                        'first_name' => 'Travis',
                        'last_name' => "O'Keefe",
                    ]),
                ['hired_at' => now()->subDay()],
            );
        $tagTeam->stables()
            ->attach(Stable::factory()
                ->create(['name' => "Hart & O'Hara"]), ['joined_at' => now()->subDay()]);

        TitleChampionship::factory()
            ->for(Title::factory()
                ->state(['name' => "Champion's & Challenger's Belt"]), 'title')
            ->forTagTeam($tagTeam)
            ->current()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('tag-teams.show', $tagTeam));

        // Assert
        $response
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
        // Arrange
        $stable = Stable::factory()
            ->active()
            ->create();
        $stable->currentWrestlers->firstOrFail()
            ->update(['name' => "O'Neil & Sons"]);
        $stable->currentTagTeams->firstOrFail()
            ->update(['name' => "Hart & O'Hara"]);

        actingAs(administrator());

        // Act
        $response = get(route('stables.show', $stable));

        // Assert
        $response
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["O'Neil & Sons", "Hart & O'Hara"]);
    });

    test('the event page escapes its venue link', function () {
        // Arrange
        $event = Event::factory()
            ->atVenue(Venue::factory()
                ->create(['name' => "O'Neil & Sons Arena"]))
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('events.show', $event));

        // Assert
        $response
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["O'Neil & Sons Arena"]);
    });

    test('the title page escapes its current champion link', function () {
        // Arrange
        $title = Title::factory()
            ->create();
        TitleChampionship::factory()
            ->for($title, 'title')
            ->forWrestler(Wrestler::factory()
                ->create(['name' => "O'Neil & Sons"]))
            ->current()
            ->create();

        actingAs(administrator());

        // Act
        $response = get(route('titles.show', $title));

        // Assert
        $response
            ->assertOk();

        assertNamesRenderEscapedOnce($response, ["O'Neil & Sons"]);
    });
});
