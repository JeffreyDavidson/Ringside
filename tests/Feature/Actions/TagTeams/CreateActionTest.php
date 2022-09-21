<?php

use App\Actions\TagTeams\CreateAction;
use App\Data\TagTeamData;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\StoreRequest;
use App\Models\Employment;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use function Spatie\PestPluginTestTime\testTime;

test('it creates a tag team', function () {
    $requestData = StoreRequest::factory()->new()->create([
        'name' => 'Example Tag Team Name',
        'signature_move' => null,
        'start_date' => null,
        'wrestlerA' => null,
        'wrestlerB' => null,
    ]);
    $tagTeamData = TagTeamData::fromStoreRequest($requestData);

    CreateAction::run($tagTeamData);

    expect(TagTeam::latest()->first())
        ->name->toBe('Example Tag Team Name')
        ->signature_move->toBeNull()
        ->employments->toBeEmpty()
        ->wrestlers->toBeEmpty();
});

test('an employment is created only for the tag team if start date is filled in request and wrestlers already have active employment', function () {
    testTime()->freeze($startDate = Carbon::now());
    [$wrestlerA, $wrestlerB] = Wrestler::factory()
        ->has(Employment::factory()->started($startDate->copy()->subWeek()))
        ->count(2)
        ->create();

    $data = StoreRequest::factory()->create([
        'start_date' => $startDate->toDateTimeString(),
        'wrestlerA' => $wrestlerA->getKey(),
        'wrestlerB' => $wrestlerB->getKey(),
    ]);

    $this->actingAs(administrator())
        ->from(action([TagTeamsController::class, 'create']))
        ->post(action([TagTeamsController::class, 'store']), $data);

    expect(TagTeam::latest()->first())
        ->employments->toHaveCount(1)
        ->wrestlers->each(function ($wrestler) use ($startDate) {
            $wrestler->employments->toHaveCount(1)
                ->pivot->joined_at->toEqual($startDate->toDateTimeString());
        });
});

test('unemployed wrestlers are employed on the same date if start date is filled in request', function () {
    testTime()->freeze($startDate = Carbon::now());
    [$wrestlerA, $wrestlerB] = Wrestler::factory()
        ->unemployed()
        ->count(2)
        ->create();

    $data = StoreRequest::factory()->create([
        'start_date' => $startDate->toDateTimeString(),
        'wrestlerA' => $wrestlerA->getKey(),
        'wrestlerB' => $wrestlerB->getKey(),
    ]);

    $this->actingAs(administrator())
        ->from(action([TagTeamsController::class, 'create']))
        ->post(action([TagTeamsController::class, 'store']), $data);

    expect(TagTeam::latest()->first())
        ->employments->toHaveCount(1)
        ->wrestlers->each(function ($wrestler) use ($startDate) {
            $wrestler->employments->toHaveCount(1)
                ->pivot->joined_at->toEqual($startDate->toDateTimeString());
        });
});

test('unemployed wrestlers are joined at the current date if start date is not filled in request', function () {
    testTime()->freeze($startDate = Carbon::now());
    [$wrestlerA, $wrestlerB] = Wrestler::factory()
        ->unemployed()
        ->count(2)
        ->create();

    $data = StoreRequest::factory()->create([
        'wrestlerA' => $wrestlerA->getKey(),
        'wrestlerB' => $wrestlerB->getKey(),
    ]);

    $this->actingAs(administrator())
        ->from(action([TagTeamsController::class, 'create']))
        ->post(action([TagTeamsController::class, 'store']), $data);

    expect(TagTeam::latest()->first())
        ->employments->toBeEmpty()
        ->wrestlers->each(function ($wrestler) use ($startDate) {
            $wrestler->pivot->joined_at->toEqual($startDate->toDateTimeString());
        });
});
