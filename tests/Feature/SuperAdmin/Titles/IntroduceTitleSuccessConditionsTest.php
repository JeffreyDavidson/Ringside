<?php

namespace Tests\Feature\SuperAdmin\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group superadmins
 */
class IntroduceTitleSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_introduce_a_pending_introduction_title()
    {
        $this->actAs('super-administrator');
        $title = factory(Title::class)->states('pending-introduction')->create();

        $response = $this->put(route('titles.introduce', $title));

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) {
            $this->assertTrue($title->is_competable);
        });
    }
}