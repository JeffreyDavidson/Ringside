<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Requests\Titles\RetireRequest;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

/**
 * @group titles
 */
class RetireRequestTest extends TestCase
{
    /** @var RetireRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RetireRequest();
    }

    /** @test */
    public function authorize_returns_an_exception_when_a_title_tries_to_be_retired()
    {
        $this->markTestIncomplete();

        TitleFactory::new()->active()->create();

        $this->expectException(CannotBeRetiredException::class);

        $this->assertTrue($this->subject->authorize());
    }
}
