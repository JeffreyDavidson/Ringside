<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TitleFactory;

/**
 * @group titles
 */
class RetireTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_active_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->active()->create();

        $response = $this->retireRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertEquals(now()->toDateTimeString(), $title->fresh()->currentRetirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_an_active_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->active()->create();

        $response = $this->retireRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_active_title()
    {
        $title = TitleFactory::new()->active()->create();

        $response = $this->retireRequest($title);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_retired_title_cannot_be_retired_again()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeRetiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->retired()->create();

        $response = $this->retireRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_future_activation_title_cannot_be_retired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeRetiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->futureActivation()->create();

        $response = $this->retireRequest($title);

        $response->assertForbidden();
    }
}
