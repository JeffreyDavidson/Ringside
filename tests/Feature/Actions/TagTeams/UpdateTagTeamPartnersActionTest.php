<?php

use App\Actions\TagTeams\UpdateTagTeamPartnersAction;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    testTime()->freeze();

    $this->tagTeamRepository = mock(TagTeamRepository::class);
});

test('it can add wrestlers to a tag team when tag team doesnt have current tag team partners', function () {
    $wrestlers = Wrestler::factory()->count(2)->create();
    $tagTeam = TagTeam::factory()->create();

    $this->tagTeamRepository
        ->shouldReceive('addTagTeamPartners')
        ->once()
        ->with($tagTeam, $wrestlers)
        ->andReturn();

    UpdateTagTeamPartnersAction::run($tagTeam, $wrestlers);
});

test('it can updates tag team partners when tag team has current tag team partners', function () {
    $currentTagTeamPartners = Wrestler::factory()->count(2)->create();
    $newTagTeamPartners = Wrestler::factory()->count(2)->create();
    $tagTeam = TagTeam::factory()->withCurrentWrestlers($currentTagTeamPartners)->create();

    $this->tagTeamRepository
        ->shouldReceive('syncTagTeamPartners')
        ->once()
        ->withArgs(function (TagTeam $tagTeamToSyncPartners, Collection $tagTeamPartnersToRemove, Collection $tagTeamPartnersToAdd) use ($currentTagTeamPartners) {
            expect($tagTeamPartnersToRemove)->each(fn ($tagTeamPartnerToRemove) => $tagTeamPartnerToRemove->toBeInstanceOf(Wrestler::class));
            expect($tagTeamPartnersToRemove->pluck('id')->toArray())->toMatchArray($currentTagTeamPartners->pluck('id')->toArray());

            return true;
        });

    UpdateTagTeamPartnersAction::run($tagTeam, $newTagTeamPartners);
});
