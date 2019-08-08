<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class ViewStablesSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $stables;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mapToIdAndName = function (Stable $stable) {
            return ['id' => $stable->id, 'name' => e($stable->name)];
        };

        $bookable            = factory(Stable::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $pendingIntroduction = factory(Stable::class, 3)->states('pending-introduction')->create()->map($mapToIdAndName);
        $retired             = factory(Stable::class, 3)->states('retired')->create()->map($mapToIdAndName);

        $this->stables = collect([
            'bookable'             => $bookable,
            'pending-introduction' => $pendingIntroduction,
            'retired'              => $retired,
            'all'                  => collect()
                                  ->concat($bookable)
                                  ->concat($pendingIntroduction)
                                  ->concat($retired)
        ]);
    }

    /** @test */
    public function an_administrator_can_view_stables_page()
    {
        $this->actAs('administrator');

        $response = $this->get(route('roster.stables.index'));

        $response->assertOk();
        $response->assertViewIs('stables.index');
    }

    /** @test */
    public function an_administrator_can_view_all_stables()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('roster.stables.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->stables->get('all')->count(),
            'data'         => $this->stables->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_bookable_stables()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('roster.stables.index', ['status' => 'only_bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->stables->get('bookable')->count(),
            'data'         => $this->stables->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_pending_introduced_stables()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('roster.stables.index', ['status' => 'only_pending_introduction']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->stables->get('pending-introduction')->count(),
            'data'         => $this->stables->get('pending-introduction')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_retired_stables()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('roster.stables.index', ['status' => 'only_retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->stables->get('retired')->count(),
            'data'         => $this->stables->get('retired')->toArray(),
        ]);
    }
}