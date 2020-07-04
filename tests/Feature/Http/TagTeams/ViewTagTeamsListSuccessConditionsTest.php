<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class ViewTagTeamsListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $tagTeams;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $bookable = TagTeamFactory::new()->count(3)->bookable()->create();
        $pendingEmployment = TagTeamFactory::new()->count(3)->pendingEmployment()->create();
        $retired = TagTeamFactory::new()->count(3)->retired()->create();
        $suspended = TagTeamFactory::new()->count(3)->suspended()->create();

        $this->tagteams = collect([
            'bookable'             => $bookable,
            'pending-employment'   => $pendingEmployment,
            'retired'              => $retired,
            'suspended'            => $suspended,
            'all'                  => collect()
                                ->concat($bookable)
                                ->concat($pendingEmployment)
                                ->concat($retired)
                                ->concat($suspended),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_tag_teams_page()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->indexRequest('tag-teams');

        $response->assertOk();
        $response->assertViewIs('tagteams.index');
    }

    /** @test */
    public function an_administrator_can_view_all_tag_teams()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('tag-teams.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('all')->count(),
            'data'         => $this->tagteams->get('all')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_bookable_tag_teams()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('tag-teams.index', ['status' => 'bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('bookable')->count(),
            'data'         => $this->tagteams->get('bookable')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_pending_employment_tag_teams()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('tag-teams.index', ['status' => 'pending-employment']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('pending-employment')->count(),
            'data'         => $this->tagteams->get('pending-employment')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_retired_tag_teams()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('tag-teams.index', ['status' => 'retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('retired')->count(),
            'data'         => $this->tagteams->get('retired')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_suspended_tag_teams()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('tag-teams.index', ['status' => 'suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('suspended')->count(),
            'data'         => $this->tagteams->get('suspended')->only(['id'])->toArray(),
        ]);
    }
}
