<?php

namespace Tests\Feature\SuperAdmin\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group superadmins
 */
class ViewTagTeamsListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $tagteams;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mapToIdAndName = function (TagTeam $tagteam) {
            return ['id' => $tagteam->id, 'name' => e($tagteam->name)];
        };

        $bookable          = factory(TagTeam::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $pendingIntroduced = factory(TagTeam::class, 3)->states('pending-introduction')->create()->map($mapToIdAndName);
        $retired           = factory(TagTeam::class, 3)->states('retired')->create()->map($mapToIdAndName);
        $suspended         = factory(TagTeam::class, 3)->states('suspended')->create()->map($mapToIdAndName);

        $this->tagteams = collect([
            'bookable'           => $bookable,
            'pending-introduction' => $pendingIntroduced,
            'retired'            => $retired,
            'suspended'          => $suspended,
            'all'                => collect()
                                ->concat($bookable)
                                ->concat($pendingIntroduced)
                                ->concat($retired)
                                ->concat($suspended)
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_tag_teams_page()
    {
        $this->actAs('super-administrator');

        $response = $this->get(route('tagteams.index'));

        $response->assertOk();
        $response->assertViewIs('tagteams.index');
    }

    /** @test */
    public function a_super_administrator_can_view_all_tag_teams()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('tagteams.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('all')->count(),
            'data'         => $this->tagteams->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_bookable_tag_teams()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('tagteams.index', ['status' => 'bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('bookable')->count(),
            'data'         => $this->tagteams->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_pending_introduction_tag_teams()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('tagteams.index', ['status' => 'pending-introduction']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('pending-introduction')->count(),
            'data'         => $this->tagteams->get('pending-introduction')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_retired_tag_teams()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('tagteams.index', ['status' => 'retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('retired')->count(),
            'data'         => $this->tagteams->get('retired')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_suspended_tag_teams()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('tagteams.index', ['status' => 'suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->tagteams->get('suspended')->count(),
            'data'         => $this->tagteams->get('suspended')->toArray(),
        ]);
    }
}
