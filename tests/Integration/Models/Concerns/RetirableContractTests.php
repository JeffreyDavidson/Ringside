<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Concerns;

trait RetirableContractTests
{
    abstract protected function getRetirable();

    /**
     * @test
     */
    public function a_retirable_with_a_retirement_is_retired()
    {
        $retirable = $this->getRetirable();

        $this->assertTrue($retirable->isRetired());
    }
}
