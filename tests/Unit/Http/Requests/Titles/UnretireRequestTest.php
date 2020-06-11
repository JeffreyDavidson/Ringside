<?php

namespace Tests\Unit\Http\Requests\Titles;

use Tests\TestCase;
use Tests\Factories\UserFactory;
use Tests\Factories\TitleFactory;
use App\Http\Requests\Titles\UnretireRequest;
use App\Exceptions\CannotBeUnretiredException;

/**
 * @group titles
 */
class UnretireRequestTest extends TestCase
{
    /** @var UnretireRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new UnretireRequest();
    }

    /** @test */
    public function authorize_returns_an_exception_when_a_title_tries_to_be_unretired()
    {
        $user = UserFactory::new()->administrator()->make();

        $this->actingAs($user);

        TitleFactory::new()->retired()->make();

        $this->expectException(CannotBeUnretiredException::class);

        $this->assertTrue($this->subject->authorize());
    }
}
