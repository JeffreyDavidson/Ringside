<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use TRegx\DataProvider\DataProviders;

/**
 * @group managers
 * @group roster
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

        $available = ManagerFactory::new()->count(3)->available()->create();
        $pendingEmployment = ManagerFactory::new()->count(3)->pendingEmployment()->create();
        $retired = ManagerFactory::new()->count(3)->retired()->create();
        $suspended = ManagerFactory::new()->count(3)->suspended()->create();
        $injured = ManagerFactory::new()->count(3)->injured()->create();

        $this->managers = collect([
            'available'            => $available,
            'pending-employment'   => $pendingEmployment,
            'retired'              => $retired,
            'suspended'            => $suspended,
            'injured'              => $injured,
            'all'                  => collect()
                                ->concat($available)
                                ->concat($pendingEmployment)
                                ->concat($retired)
                                ->concat($suspended)
                                ->concat($injured),
        ]);
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_view_managers_page($adminRoles)
    {
        $this->actAs($adminRoles);

        $response = $this->indexRequest('managers');

        $response->assertOk();
        $response->assertViewIs('managers.index');
    }

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_view_all_managers($adminRoles)
    {
        $this->actAs($adminRoles);

        $responseAjax = $this->ajaxJson(route('managers.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get('all')->count(),
            'data'         => $this->managers->get('all')->only(['id'])->toArray(),
        ]);
    }

    /**
     * @test
     * @dataProvider providers
     */
    public function administrators_can_view_filtered_managers_by_status($adminRoles, $managerStatuses)
    {
        $this->actAs($adminRoles);

        $responseAjax = $this->ajaxJson(route('managers.index', ['status' => $managerStatuses]));

        $responseAjax->assertJson([
            'recordsTotal' => $this->managers->get($managerStatuses)->count(),
            'data'         => $this->managers->get($managerStatuses)->only(['id'])->toArray(),
        ]);
    }

    public function providers()
    {
        return DataProviders::cross(
            $this->adminRoles(),
            $this->managerStatuses()
        );
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }

    public function managerStatuses()
    {
        return [
            ['available'],
            ['pending-employment'],
            ['retired'],
            ['suspended'],
            ['injured'],
        ];
    }
}
