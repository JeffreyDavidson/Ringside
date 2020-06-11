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
class EmployWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_employ_a_pending_employment_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function (Wrestler $wrestler) {
            $this->assertTrue($wrestler->isCurrentlyEmployed());
        });
    }

    /** @test */
    public function a_wrestler_without_a_current_employment_can_be_employed()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->employRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertTrue($wrestler->currentEmployment()->exists());
        });
    }
}
