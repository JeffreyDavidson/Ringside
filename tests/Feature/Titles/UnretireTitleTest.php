<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TitleFactory;

/**
 * @group titles
 */
class UnretireTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->retired()->create();

        $response = $this->unretireRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertEquals(now()->toDateTimeString(), $title->fresh()->retirements()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->retired()->create();

        $response = $this->unretireRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_title()
    {
        $title = TitleFactory::new()->retired()->create();

        $response = $this->unretireRequest($title);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_active_title_cannot_unretire()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->active()->create();

        $response = $this->unretireRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_future_activation_title_cannot_unretire()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->futureActivation()->create();

        $response = $this->unretireRequest($title);

        $response->assertForbidden();
    }
}
