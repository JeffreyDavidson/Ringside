<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\DeactivateRequest;
use Tests\TestCase;

/**
 * @group titles
 * @group requests
 */
class DeactivateRequestTest extends TestCase
{
    /** @test */
    public function authorized_returns_false_when_unauthenticated()
    {
        $subject = new DeactivateRequest();

        $this->assertFalse($subject->authorize());
    }
}
