<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Mockery\MockInterface;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->create();
});

test('edit returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TagTeamsController::class, 'edit'], $this->tagTeam))
        ->assertStatus(200)
        ->assertViewIs('tag-teams.edit')
        ->assertViewHas('tagTeam', $this->tagTeam);
});

test('the correct wrestlers are available to join an editable team', function () {
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA = Wrestler::factory()->create(['name' => 'Randy Orton']))
        ->hasAttached($wrestlerB = Wrestler::factory()->create(['name' => 'Shawn Michaels']))
        ->create();

    $unemployedWrestler = Wrestler::factory()->unemployed()->create(['name' => 'Hulk Hogan']);
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create(['name' => 'The Rock']);
    $bookableWrestlerNotOnBookableTagTeam = Wrestler::factory()->bookable()->create(['name' => 'Stone Cold Steve Austin']);
    Wrestler::factory()->bookable()->onCurrentTagTeam()->create();
    Wrestler::factory()->injured()->create();
    Wrestler::factory()->suspended()->create();
    Wrestler::factory()->released()->create();
    Wrestler::factory()->retired()->create();

    $wrestlers = (new Collection([
        $wrestlerA,
        $wrestlerB, $unemployedWrestler,
        $futureEmployedWrestler,
        $bookableWrestlerNotOnBookableTagTeam,
    ]));

    Mockery::mock(WrestlerRepository::class, function (MockInterface $mock) use ($wrestlers) {
        $mock->shouldReceive('getAvailableWrestlersForExistingTagTeam')->andReturn($wrestlers);
    });

    $this->actingAs(administrator())
        ->get(action([TagTeamsController::class, 'edit'], $tagTeam))
        ->assertStatus(200)
        ->assertViewIs('tag-teams.edit')
        ->assertViewHas('wrestlers', function ($data) use ($wrestlers) {
            return $data->keys()->all() == $wrestlers->modelKeys() && count($wrestlers->modelKeys()) === 5;
        })
        ->assertSeeText('Hulk Hogan')
        ->assertSeeText('The Rock')
        ->assertSeeText('Stone Cold Steve Austin')
        ->assertSeeText('Randy Orton')
        ->assertSeeText('Shawn Michaels');
});

test('a basic user cannot view the form for editing a tag team', function () {
    $this->actingAs(basicUser())
        ->get(action([TagTeamsController::class, 'edit'], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a tag team', function () {
    $this->get(action([TagTeamsController::class, 'edit'], $this->tagTeam))
        ->assertRedirect(route('login'));
});
