<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\UnretireRequest;
use Tests\TestCase;

/**
 * @group titles
 * @group requests
 */
class UnretireRequestTest extends TestCase
{
    /** @test */
    public function authorized_returns_false_when_unauthenticated()
    {
        $subject = new UnretireRequest();

        $this->assertFalse($subject->authorize());
    }
}
