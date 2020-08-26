<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Http\Requests\Titles\ActivateRequest;
use Tests\TestCase;

/**
 * @group titles
 * @group requests
 */
class ActivateRequestTest extends TestCase
{
    /** @test */
    public function authorized_returns_false_when_unauthenticated()
    {
        $subject = new ActivateRequest();

        $this->assertFalse($subject->authorize());
    }
}
