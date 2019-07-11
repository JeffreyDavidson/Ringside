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
        $inactive  = factory(Wrestler::class, 3)->states('inactive')->create()->map($mapToIdAndName);
        $retired   = factory(Wrestler::class, 3)->states('retired')->create()->map($mapToIdAndName);
        $suspended = factory(Wrestler::class, 3)->states('suspended')->create()->map($mapToIdAndName);
        $injured   = factory(Wrestler::class, 3)->states('injured')->create()->map($mapToIdAndName);

        $this->wrestlers = collect([
            'bookable'  => $bookable,
            'inactive'  => $inactive,
            'retired'   => $retired,
            'suspended' => $suspended,
            'injured'   => $injured,
            'all'       => collect()
                ->concat($bookable)
                ->concat($inactive)
                ->concat($retired)
                ->concat($suspended)
                ->concat($injured)
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_wrestlers_page()
    {
        $this->actAs('administrator');

        $response = $this->get(route('wrestlers.index'));

        $response->assertOk();
        $response->assertViewIs('wrestlers.index');
    }

    /** @test */
    public function a_super_administrator_can_view_all_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('all')->count(),
            'data'         => $this->wrestlers->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_bookable_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('bookable')->count(),
            'data'         => $this->wrestlers->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_inactive_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_inactive']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('inactive')->count(),
            'data'         => $this->wrestlers->get('inactive')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_retired_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('retired')->count(),
            'data'         => $this->wrestlers->get('retired')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_suspended_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('suspended')->count(),
            'data'         => $this->wrestlers->get('suspended')->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_injured_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'only_injured']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('injured')->count(),
            'data'         => $this->wrestlers->get('injured')->toArray(),
        ]);
    }
}
