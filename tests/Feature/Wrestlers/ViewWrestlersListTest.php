<?php

namespace Tests\Feature\Wrestlers;

use App\Enums\Role;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

/**
 * @group wrestlers
 * @group roster
 */
class ViewWrestlersListTest extends TestCase
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

        $bookable = WrestlerFactory::new()->count(3)->bookable()->create();
        $pendingEmployment = WrestlerFactory::new()->count(3)->pendingEmployment()->create();
        $retired = WrestlerFactory::new()->count(3)->retired()->create();
        $suspended = WrestlerFactory::new()->count(3)->suspended()->create();
        $injured = WrestlerFactory::new()->count(3)->injured()->create();

        $this->wrestlers = collect([
            'bookable'           => $bookable,
            'pending-employment' => $pendingEmployment,
            'retired'            => $retired,
            'suspended'          => $suspended,
            'injured'            => $injured,
            'all'                => collect()
                                ->concat($bookable)
                                ->concat($pendingEmployment)
                                ->concat($retired)
                                ->concat($suspended)
                                ->concat($injured),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_wrestlers_page()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->indexRequest('wrestlers');

        $response->assertOk();
        $response->assertViewIs('wrestlers.index');
    }

    /** @test */
    public function an_administrator_can_view_all_wrestlers()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('wrestlers.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('all')->count(),
            'data'         => $this->wrestlers->get('all')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_bookable_wrestlers()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('bookable')->count(),
            'data'         => $this->wrestlers->get('bookable')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_pending_employment_wrestlers()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'pending-employment']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('pending-employment')->count(),
            'data'         => $this->wrestlers->get('pending-employment')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_retired_wrestlers()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('retired')->count(),
            'data'         => $this->wrestlers->get('retired')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_suspended_wrestlers()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'suspended']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('suspended')->count(),
            'data'         => $this->wrestlers->get('suspended')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_injured_wrestlers()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('wrestlers.index', ['status' => 'injured']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->wrestlers->get('injured')->count(),
            'data'         => $this->wrestlers->get('injured')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_basic_user_cannot_view_wrestlers_page()
    {
        $this->actAs(Role::BASIC);

        $response = $this->indexRequest('wrestlers');

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_wrestlers_page()
    {
        $response = $this->indexRequest('wrestler');

        $response->assertRedirect(route('login'));
    }
}
