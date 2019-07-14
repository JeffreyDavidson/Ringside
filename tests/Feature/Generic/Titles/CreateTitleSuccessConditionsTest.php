<?php

namespace Tests\Feature\Generic\Titles;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
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
    public function a_title_introduced_today_or_before_is_bookable()
    {
        $this->actAs('administrator');

        $this->post(route('titles.store'), $this->validParams(['introduced_at' => today()->toDateTimeString()]));

        tap(Title::first(), function ($title) {
            $this->assertTrue($title->is_bookable);
        });
    }

    /** @test */
    public function a_title_introduced_after_today_is_pending_introduced()
    {
        $this->actAs('administrator');

        $this->post(route('titles.store'), $this->validParams(['introduced_at' => Carbon::tomorrow()->toDateTimeString()]));

        tap(Title::first(), function ($title) {
            $this->assertTrue($title->is_pending_introduced);
        });
    }
}
