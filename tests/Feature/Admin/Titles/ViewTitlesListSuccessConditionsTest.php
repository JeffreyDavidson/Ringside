<?php

namespace Tests\Feature\Admin\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group admins
 */
class ViewTitlesListSuccessConditionsTest extends TestCase
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

        $bookable          = factory(Title::class, 3)->states('bookable')->create()->map($mapToIdAndName);
        $pendingIntroduced = factory(Title::class, 3)->states('pending-introduced')->create()->map($mapToIdAndName);
        $retired           = factory(Title::class, 3)->states('retired')->create()->map($mapToIdAndName);

        $this->titles = collect([
            'bookable'           => $bookable,
            'pending-introduced' => $pendingIntroduced,
            'retired'            => $retired,
            'all'                => collect()
                                ->concat($bookable)
                                ->concat($pendingIntroduced)
                                ->concat($retired)
        ]);
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
    public function an_administrator_can_view_all_bookable_titles()
    {
        $this->actAs('administrator');

        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'only_bookable']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('bookable')->count(),
            'data'         => $this->titles->get('bookable')->toArray(),
        ]);
    }

    /** @test */
    public function an_administrator_can_view_all_pending_introduced_titles()
    {
        $this->actAs('administrator');
        $responseAjax = $this->ajaxJson(route('titles.index', ['status' => 'only_pending_introduced']));

        $responseAjax->assertJson([
            'recordsTotal' => $this->titles->get('pending-introduced')->count(),
            'data'         => $this->titles->get('pending-introduced')->toArray(),
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
