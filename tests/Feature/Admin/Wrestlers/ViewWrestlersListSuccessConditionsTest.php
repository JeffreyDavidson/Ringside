<?php

namespace Tests\Feature\Admin\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group admins
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

        $bookable          = factory(Wrestler::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $pendingIntroduced = factory(Wrestler::class, 3)->states('pending-introduction')->create()->map($mapToIdAndName);
        $retired           = factory(Wrestler::class, 3)->states('retired')->create()->map($mapToIdAndName);
        $suspended         = factory(Wrestler::class, 3)->states('suspended')->create()->map($mapToIdAndName);
        $injured           = factory(Wrestler::class, 3)->states('injured')->create()->map($mapToIdAndName);

        $this->wrestlers = collect([
            'bookable'           => $bookable,
            'pending-introduction' => $pendingIntroduced,
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
    public function an_administrator_can_view_wrestlers_page()
    {
        $this->actAs('administrator');

        $response = $this->get(route('wrestlers.index'));

        $response->assertOk();
        $response->assertViewIs('wrestlers.index');
    }

    /** @test */
    public function an_administrator_can_view_all_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('all')->count(),
            'data'         => $this->wrestlers->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_bookable_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('bookable')->count(),
            'data'         => $this->wrestlers->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_pending_introduction_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'pending-introduction']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('pending-introduction')->count(),
            'data'         => $this->wrestlers->get('pending-introduction')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_retired_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('retired')->count(),
            'data'         => $this->wrestlers->get('retired')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_suspended_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('suspended')->count(),
            'data'         => $this->wrestlers->get('suspended')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_injured_wrestlers()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'injured']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('injured')->count(),
            'data'         => $this->wrestlers->get('injured')->toArray(),
        ]);
    }
}
