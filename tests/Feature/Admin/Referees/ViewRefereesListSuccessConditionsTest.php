<?php

namespace Tests\Feature\Admin\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group admins
 */
class ViewRefereesListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $referees;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mapToIdAndName = function (Referee $referee) {
            return [
                'id' => $referee->id,
                'first_name' => e($referee->first_name),
                'last_name' => e($referee->last_name),
            ];
        };

        $bookable          = factory(Referee::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $pendingIntroduced = factory(Referee::class, 3)->states('pending-introduced')->create()->map($mapToIdAndName);
        $retired           = factory(Referee::class, 3)->states('retired')->create()->map($mapToIdAndName);
        $suspended         = factory(Referee::class, 3)->states('suspended')->create()->map($mapToIdAndName);
        $injured           = factory(Referee::class, 3)->states('injured')->create()->map($mapToIdAndName);

        $this->referees = collect([
            'bookable'           => $bookable,
            'pending-introduced' => $pendingIntroduced,
            'retired'            => $retired,
            'suspended'          => $suspended,
            'injured'            => $injured,
            'all'                => collect()
                                ->concat($bookable)
                                ->concat($pendingIntroduced)
                                ->concat($retired)
                                ->concat($suspended)
                                ->concat($injured)
        ]);
    }

    /** @test */
    public function an_administrator_can_view_referees_page()
    {
        $this->actAs('administrator');

        $response = $this->get(route('referees.index'));

        $response->assertOk();
        $response->assertViewIs('referees.index');
    }

    /** @test */
    public function an_administrator_can_view_all_referees()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('referees.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->referees->get('all')->count(),
            'data'         => $this->referees->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_bookable_referees()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('referees.index', ['status' => 'only_bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->referees->get('bookable')->count(),
            'data'         => $this->referees->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_pending_introduced_referees()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('referees.index', ['status' => 'only_pending_introduced']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->referees->get('pending-introduced')->count(),
            'data'         => $this->referees->get('pending-introduced')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_retired_referees()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('referees.index', ['status' => 'only_retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->referees->get('retired')->count(),
            'data'         => $this->referees->get('retired')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_suspended_referees()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('referees.index', ['status' => 'only_suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->referees->get('suspended')->count(),
            'data'         => $this->referees->get('suspended')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_injured_referees()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('referees.index', ['status' => 'only_injured']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->referees->get('injured')->count(),
            'data'         => $this->referees->get('injured')->toArray(),
        ]);
    }
}