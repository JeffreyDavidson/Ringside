<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\RetireRequest;
use Tests\TestCase;

/**
 * @group titles
 * @group requests
 */
class RetireRequestTest extends TestCase
{
    /** @test */
    public function authorized_returns_false_when_unauthenticated()
    {
        $subject = new RetireRequest();

        $this->assertFalse($subject->authorize());
    }
}
