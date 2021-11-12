<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\Employment;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamRequestDataFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class TagTeamControllerStoreMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([TagTeamsController::class, 'create']))
            ->assertViewIs('tagteams.create')
            ->assertViewHas('tagTeam', new TagTeam);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this
            ->actAs(Role::BASIC)
            ->get(action([TagTeamsController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this
            ->get(action([TagTeamsController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_a_tag_team_and_redirects()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TagTeamsController::class, 'create']))
            ->post(
                action([TagTeamsController::class, 'store']),
                TagTeamRequestDataFactory::new()->create([
                    'name' => 'Example Tag Team Name',
                    'signature_move' => null,
                    'started_at' => null,
                    'wrestlers' => null,
                ])
            )
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap(TagTeam::first(), function ($tagTeam) {
            $this->assertEquals('Example Tag Team Name', $tagTeam->name);
            $this->assertNull($tagTeam->signature_move);
            $this->assertCount(0, $tagTeam->employments);
            $this->assertCount(0, $tagTeam->wrestlers);
        });
    }

    /**
     * @test
     */
    public function an_employment_is_created_only_for_the_tag_team_if_started_at_is_filled_in_request()
    {
        $startDate = now();
        $wrestlers = Wrestler::factory()
            ->has(Employment::factory()->started($startDate->copy()->subWeek()))
            ->count(2)
            ->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TagTeamsController::class, 'create']))
            ->post(
                action([TagTeamsController::class, 'store']),
                TagTeamRequestDataFactory::new()->create([
                    'started_at' => $startDate->toDateString(),
                    'wrestlers' => $wrestlers->pluck('id')->toArray(),
                ])
            );

        tap(TagTeam::first(), function ($tagTeam) use ($startDate) {
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals($startDate->toDateString(), $tagTeam->employments->first()->started_at->toDateString());

            foreach ($tagTeam->wrestlers as $wrestler) {
                $this->assertSame($startDate->toDateString(), $wrestler->pivot->joined_at);
                $this->assertInstanceOf(Wrestler::class, $wrestler);
                $this->assertCount(1, $wrestler->employments);
            }
        });
    }

    /**
     * @test
     */
    public function unemployed_wrestlers_are_employed_on_the_same_date_if_started_at_is_filled_in_request()
    {
        $startDate = now();
        $wrestlers = Wrestler::factory()
            ->unemployed()
            ->count(2)
            ->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TagTeamsController::class, 'create']))
            ->post(
                action([TagTeamsController::class, 'store']),
                TagTeamRequestDataFactory::new()->create([
                    'started_at' => $startDate->toDateString(),
                    'wrestlers' => $wrestlers->pluck('id')->toArray(),
                ])
            );

        tap(TagTeam::first(), function ($tagTeam) use ($startDate) {
            $this->assertCount(1, $tagTeam->employments);

            foreach ($tagTeam->wrestlers as $wrestler) {
                $this->assertSame($startDate->toDateString(), $wrestler->pivot->joined_at);
                $this->assertInstanceOf(Wrestler::class, $wrestler);
                $this->assertCount(1, $wrestler->employments);
            }
        });
    }

    /**
     * @test
     */
    public function unemployed_wrestlers_are_joined_at_the_current_date_if_started_at_is_not_filled_in_request()
    {
        $startDate = now();
        $wrestlers = Wrestler::factory()
            ->unemployed()
            ->count(2)
            ->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TagTeamsController::class, 'create']))
            ->post(
                action([TagTeamsController::class, 'store']),
                TagTeamRequestDataFactory::new()->create([
                    'wrestlers' => $wrestlers->pluck('id')->toArray(),
                ])
            );

        tap(TagTeam::first(), function ($tagTeam) use ($startDate) {
            $this->assertCount(0, $tagTeam->employments);

            foreach ($tagTeam->wrestlers as $wrestler) {
                $this->assertSame($startDate->toDateString(), $wrestler->pivot->joined_at);
                $this->assertInstanceOf(Wrestler::class, $wrestler);
            }
        });
    }

    /**
     * @test
     */
    public function a_tag_team_cannot_have_a_signature_move_with_wrestlers_not_filled_in_request()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TagTeamsController::class, 'create']))
            ->post(
                action([TagTeamsController::class, 'store']),
                TagTeamRequestDataFactory::new()->create([
                    'wrestlers' => null,
                    'signature_move' => 'Signature Move',
                ])
            )
            ->assertSessionHasErrors(['wrestlers']);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_tag_team()
    {
        $this
            ->actAs(Role::BASIC)
            ->from(action([TagTeamsController::class, 'create']))
            ->post(action([TagTeamsController::class, 'store']), TagTeamRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_tag_team()
    {
        $this
            ->from(action([TagTeamsController::class, 'create']))
            ->post(action([TagTeamsController::class, 'store']), TagTeamRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }
}
