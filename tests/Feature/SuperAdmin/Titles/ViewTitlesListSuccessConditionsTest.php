<?php

namespace Tests\Feature\SuperAdmin\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group superadmins
 */
class ViewTitlesListSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $titles;

    protected function setUp(): void
    {
        parent::setUp();

        $competable          = factory(Title::class, 3)->states('competable')->create();
        $pendingIntroduction = factory(Title::class, 3)->states('pending-introduction')->create();
        $retired             = factory(Title::class, 3)->states('retired')->create();

        $this->titles = collect([
            'pending-introduction' => $pendingIntroduction,
            'competable'           => $competable,
            'retired'              => $retired,
            'all'                  => collect()
                                ->concat($competable)
                                ->concat($pendingIntroduction)
                                ->concat($retired)
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_titles_page()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);

        $response = $this->get(route('titles.index'));

        $response->assertOk();
        $response->assertViewIs('titles.index');
    }

    /** @test */
    public function a_super_administrator_can_view_all_titles()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('titles.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('all')->count(),
            'data'         => $this->titles->get('all')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_all_competable_titles()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);

        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'competable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('competable')->count(),
            'data'         => $this->titles->get('competable')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_all_pending_introduction_titles()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);
        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'pending-introduction']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('pending-introduction')->count(),
            'data'         => $this->titles->get('pending-introduction')->only(['id'])->toArray(),
        ]);
    }

    /** @test */
    public function a_super_administrator_can_view_all_retired_titles()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);
        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('retired')->count(),
            'data'         => $this->titles->get('retired')->only(['id'])->toArray(),
        ]);
    }
}
