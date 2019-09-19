<?php

namespace Tests\Feature\SuperAdmin\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group superadmins
 * @group roster
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

        $bookable            = factory(Wrestler::class, 3)->states('bookable')->create();
        $pendingEmployment   = factory(Wrestler::class, 3)->states('pending-employment')->create();
        $retired             = factory(Wrestler::class, 3)->states('retired')->create();
        $suspended           = factory(Wrestler::class, 3)->states('suspended')->create();
        $injured             = factory(Wrestler::class, 3)->states('injured')->create();

        $this->wrestlers = collect([
            'bookable'             => $bookable,
            'pending-employment'   => $pendingEmployment,
            'retired'              => $retired,
            'suspended'            => $suspended,
            'injured'              => $injured,
            'all'                  => collect()
                                ->concat($bookable)
                                ->concat($pendingEmployment)
                                ->concat($retired)
                                ->concat($suspended)
                                ->concat($injured)
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
            'data'         => $this->wrestlers->get('all')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_bookable_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('bookable')->count(),
            'data'         => $this->wrestlers->get('bookable')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_pending_employment_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'pending-employment']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('pending-employment')->count(),
            'data'         => $this->wrestlers->get('pending-employment')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_retired_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('retired')->count(),
            'data'         => $this->wrestlers->get('retired')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_suspended_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('suspended')->count(),
            'data'         => $this->wrestlers->get('suspended')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_injured_wrestlers()
    {
        $this->actAs('super-administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'injured']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('injured')->count(),
            'data'         => $this->wrestlers->get('injured')->only(['id'])->toArray(),
        ]);
    }
}
