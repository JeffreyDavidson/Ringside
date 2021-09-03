<?php

namespace Tests\Integration\Models\Concerns;

use App\Models\Stable;

trait StableMemberContractTests
{
    abstract protected function getStableMember();

    /**
     * @test
     */
    public function a_stable_member_can_have_one_current_stable()
    {
        $stableMember = $this->getStableMember();

        Stable::factory()
            ->hasAttached($stableMember, ['joined_at' => now()->toDateTimeString()])
            ->create();

        $this->assertInstanceOf(Stable::class, $stableMember->currentStable);
    }
}
