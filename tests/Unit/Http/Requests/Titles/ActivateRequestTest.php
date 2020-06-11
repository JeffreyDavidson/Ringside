<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Exceptions\CannotBeActivatedException;
use App\Http\Requests\Titles\ActivateRequest;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

/**
 * @group titles
 */
class ActivateRequestTest extends TestCase
{
    /** @var ActivateRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ActivateRequest();
    }

    /** @test */
    public function authorize_returns_an_exception_when_a_title_tries_to_be_activated()
    {
        $this->markTestIncomplete();
        TitleFactory::new()->active()->create();

        $this->expectException(CannotBeActivatedException::class);

        $this->assertTrue($this->subject->authorize());
    }
}
