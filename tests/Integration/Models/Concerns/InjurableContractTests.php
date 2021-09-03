<?php

namespace Tests\Integration\Models\Concerns;

trait InjurableContractTests
{
    abstract protected function getInjurable();

    /**
     * @test
     */
    public function an_injurable_with_an_injury_is_injured()
    {
        $injurable = $this->getInjurable();

        $this->assertTrue($injurable->isInjured());
    }
}
