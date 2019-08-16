<?php

namespace Tests\Feature\Admin\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group admins
 */
class ViewManagersListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $managers;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mapToIdAndName = function (Manager $manager) {
            return [
                'id' => $manager->id,
                'first_name' => e($manager->first_name),
                'last_name' => e($manager->last_name),
            ];
        };

        $bookable            = factory(Manager::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $pendingIntroduction = factory(Manager::class, 3)->states('pending-introduction')->create()->map($mapToIdAndName);
        $retired             = factory(Manager::class, 3)->states('retired')->create()->map($mapToIdAndName);
        $suspended           = factory(Manager::class, 3)->states('suspended')->create()->map($mapToIdAndName);
        $injured             = factory(Manager::class, 3)->states('injured')->create()->map($mapToIdAndName);

        $this->managers = collect([
            'bookable'             => $bookable,
            'pending-introduction' => $pendingIntroduction,
            'retired'              => $retired,
            'suspended'            => $suspended,
            'injured'              => $injured,
            'all'                  => collect()
                                ->concat($bookable)
                                ->concat($pendingIntroduction)
                                ->concat($retired)
                                ->concat($suspended)
                                ->concat($injured)
        ]);
    }

    /** @test */
    public function an_administrator_can_view_managers_page()
    {
        $this->actAs('administrator');

        $response = $this->get(route('managers.index'));

        $response->assertOk();
        $response->assertViewIs('managers.index');
    }

    /** @test */
    public function an_administrator_can_view_all_managers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('managers.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get('all')->count(),
            'data'         => $this->managers->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_bookable_managers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('managers.index', ['status' => 'bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get('bookable')->count(),
            'data'         => $this->managers->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_pending_introduction_managers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('managers.index', ['status' => 'pending-introduction']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get('pending-introduction')->count(),
            'data'         => $this->managers->get('pending-introduction')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_retired_managers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('managers.index', ['status' => 'retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get('retired')->count(),
            'data'         => $this->managers->get('retired')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_suspended_managers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('managers.index', ['status' => 'suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get('suspended')->count(),
            'data'         => $this->managers->get('suspended')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_injured_managers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('managers.index', ['status' => 'injured']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get('injured')->count(),
            'data'         => $this->managers->get('injured')->toArray(),
        ]);
    }
}
