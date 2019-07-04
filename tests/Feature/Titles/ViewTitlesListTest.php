<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** @group titles */
class ViewTitlesListTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $titles;

    protected function setUp(): void
    {
        parent::setUp();
        $mapToIdAndName = function (Title $title) {
            return ['id' => $title->id, 'name' => e($title->name)];
        };

        $active   = factory(Title::class, 3)->states('active')->create()->map($mapToIdAndName);
        $inactive = factory(Title::class, 3)->states('inactive')->create()->map($mapToIdAndName);
        $retired  = factory(Title::class, 3)->states('retired')->create()->map($mapToIdAndName);

        $this->titles = collect([
            'active'   => $active,
            'inactive' => $inactive,
            'retired'  => $retired,
            'all'      => collect()->concat($active)->concat($inactive)->concat($retired)
        ]);
    }

    /** @test */
    public function a_basic_user_cannot_view_titles_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('titles.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_titles_page()
    {
        $response = $this->get(route('titles.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_administrator_can_view_titles_page()
    {
        $this->actAs('administrator');

        $response = $this->get(route('titles.index'));

        $response->assertOk();
        $response->assertViewIs('titles.index');
    }

    /** @test */
    public function an_administrator_can_view_all_titles()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('titles.index'));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('all')->count(),
            'data'         => $this->titles->get('all')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_all_active_titles()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'only_active']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('active')->count(),
            'data'         => $this->titles->get('active')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_all_inactive_titles()
    {
        $this->actAs('administrator');
        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'only_inactive']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('inactive')->count(),
            'data'         => $this->titles->get('inactive')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_all_retired_titles()
    {
        $this->actAs('administrator');
        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'only_retired']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('retired')->count(),
            'data'         => $this->titles->get('retired')->toArray(),
        ]);
    }
}
