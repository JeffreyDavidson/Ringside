<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use App\Models\Title;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group titles
 */
class CreateTitleSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Name Title',
            'introduced_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_title()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->createRequest('title');

        $response->assertViewIs('titles.create');
        $response->assertViewHas('title', new Title);
    }

    /** @test */
    public function an_administrator_can_create_a_title()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('title', $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap(Title::first(), function ($title) use ($now) {
            $this->assertEquals('Example Name Title', $title->name);
            $this->assertEquals($now->toDateTimeString(), $title->introduced_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_title_introduced_today_or_before_is_competable()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $this->storeRequest('titles', $this->validParams(['introduced_at' => today()->toDateTimeString()]));

        tap(Title::first(), function ($title) {
            $this->assertTrue($title->isCompetable());
        });
    }

    /** @test */
    public function a_title_introduced_after_today_is_pending_introduction()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $this->storeRequest('titles', $this->validParams(['introduced_at' => Carbon::tomorrow()->toDateTimeString()]));

        tap(Title::first(), function ($title) {
            $this->assertTrue($title->isPendingIntroduction());
        });
    }
}
