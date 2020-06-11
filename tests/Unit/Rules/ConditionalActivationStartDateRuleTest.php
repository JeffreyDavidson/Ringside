<?php

namespace Tests\Unit\Rules;

use App\Rules\CannotBeEmployedAfterDate;
use Tests\Factories\EmploymentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

class ConditionalActivationStartDateRuleTest extends TestCase
{
    use RefreshDatabase;

}
