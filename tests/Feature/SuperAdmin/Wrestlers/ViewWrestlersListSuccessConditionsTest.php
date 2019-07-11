<?php

namespace Tests\Feature\SuperAdmin\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group superadmins
 */
class ViewWrestlersListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $wrestlers;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $mapToIdAndName = function (Wrestler $wrestler) {
            return ['id' => $wrestler->id, 'name' => e($wrestler->name)];
        };

        $bookable  = factory(Wrestler::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $retired   = factory(Wrestler::class, 3)->states('retired')->create()->map($mapToIdAndName);
        $suspended = factory(Wrestler::class, 3)->states('suspended')->create()->map($mapToIdAndName);
        $injured   = factory(Wrestler::class, 3)->states('injured')->create()->map($mapToIdAndName);
        $pendingIntroduced = factory(Wrestler::class, 3)->states('pending-introduced')->create()->map($mapToIdAndName);

        $this->wrestlers = collect([
            'bookable'           => $bookable,
            'retired'            => $retired,
            'suspended'          => $suspended,
            'injured'            => $injured,
            'pending-introduced' => $pendingIntroduced,
            'all'                => collect()
                                ->concat($bookable)
                                ->concat($retired)
                                ->concat($suspended)
                                ->concat($injured)
                                ->concat($pendingIntroduced)
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_wrestlers_page()
    {
        $this->actAs('super-administrator');

        $response = $this->get(route('wrestlers.index'));

        $response->assertOk();
        $response->assertViewIs('wrestlers.index');
    }

    /** @test */
    public function a_super_administrator_can_view_all_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('all')->count(),
            'data'         => $this->wrestlers->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_bookable_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('bookable')->count(),
            'data'         => $this->wrestlers->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_pending_introduced_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_pending_introduced']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('pending-introduced')->count(),
            'data'         => $this->wrestlers->get('pending-introduced')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_retired_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('retired')->count(),
            'data'         => $this->wrestlers->get('retired')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_suspended_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('suspended')->count(),
            'data'         => $this->wrestlers->get('suspended')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_injured_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_injured']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('injured')->count(),
            'data'         => $this->wrestlers->get('injured')->toArray(),
        ]);
    }
}
