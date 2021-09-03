<?php

namespace Tests\Integration\Models\Concerns;

trait SuspendableContractTests
{
    abstract protected function getSuspendable();

    /**
     * @test
     */
    public function a_suspendable_with_a_suspension_is_suspended()
    {
        $suspendable = $this->getSuspendable();

        $this->assertTrue($suspendable->isSuspended());
    }
}
