<?php

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\StoreRequest;
use App\Models\Employment;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TagTeamsController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('tagteams.create')
        ->assertViewHas('tagTeam', new TagTeam);
});

test('a basic user cannot view the form for creating a tag team', function () {
    $this->actingAs(basicUser())
        ->get(action([TagTeamsController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a tag team', function () {
    $this->get(action([TagTeamsController::class, 'create']))
        ->assertRedirect(route('login'));
});

test('store creates a tag team and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Tag Team Name',
        'signature_move' => null,
        'start_date' => null,
        'wrestlers' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([TagTeamsController::class, 'create']))
        ->post(action([TagTeamsController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect(TagTeam::latest()->first())
        ->name->toBe('Example Tag Team Name')
        ->signature_move->toBeNull()
        ->employments->toBeEmpty()
        ->wrestlers->toBeEmpty();
});

test('an employment is created only for the tag team if start date is filled in request', function () {
    $startDate = now();
    $wrestlers = Wrestler::factory()
        ->has(Employment::factory()->started($startDate->copy()->subWeek()))
        ->count(2)
        ->create();

    $data = StoreRequest::factory()->create([
        'start_date' => $startDate->toDateTimeString(),
        'wrestlers' => $wrestlers->modelKeys(),
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
    $startDate = now();
    Carbon::setTestNow($startDate);
    $wrestlers = Wrestler::factory()
        ->unemployed()
        ->count(2)
        ->create();

    $data = StoreRequest::factory()->create([
        'start_date' => $startDate->toDateTimeString(),
        'wrestlers' => $wrestlers->pluck('id')->toArray(),
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
    $startDate = now();
    Carbon::setTestNow($startDate);
    $wrestlers = Wrestler::factory()
        ->unemployed()
        ->count(2)
        ->create();

    $data = StoreRequest::factory()->create([
        'wrestlers' => $wrestlers->pluck('id')->toArray(),
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

test('a basic user cannot create a tag team', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([TagTeamsController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a tag team', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([TagTeamsController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
