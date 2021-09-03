<?php

namespace Tests\Integration\Models\Concerns;

use Carbon\Carbon;

trait EmployableContractTests
{
    abstract protected function getEmployable();

    /**
     * @test
     */
    public function an_employable_with_an_employment_date_in_the_past_is_currently_employed()
    {
        $employable = $this->getEmployable();
        $employable->employments()->create(['started_at' => Carbon::parse('-1 week')]);

        $this->assertTrue($employable->isCurrentlyEmployed());
    }

    /**
     * @test
     */
    public function an_employable_without_an_employment_is_unemployed()
    {
        $employable = $this->getEmployable();

        $this->assertTrue($employable->isUnemployed());
    }
}
