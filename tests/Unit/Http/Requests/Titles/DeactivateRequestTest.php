<?php

namespace Tests\Unit\Http\Requests\Titles;

use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Requests\Titles\DeactivateRequest;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

/**
 * @group titles
 */
class DeactivateRequestTest extends TestCase
{
    /** @var DeactivateRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new DeactivateRequest();
    }

    /** @test */
    public function authorize_returns_an_exception_when_a_title_tries_to_be_deactivated()
    {
        $this->markTestIncomplete();

        TitleFactory::new()->active()->create();

        $this->expectException(CannotBeDeactivatedException::class);

        $this->assertTrue($this->subject->authorize());
    }
}
