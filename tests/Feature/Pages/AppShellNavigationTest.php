<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

/**
 * @param  TestResponse<Response>  $response
 */
function appShellDocument(TestResponse $response): HTMLDocument
{
    $html = $response->assertOk()->getContent();
    if (! is_string($html)) {
        throw new RuntimeException('Expected the page to contain HTML.');
    }

    return HTMLDocument::createFromString($html, LIBXML_NOERROR);
}

describe('user management link', function (): void {
    it('is offered to administrators and marked as the current page only on user management', function (): void {
        // Arrange
        $administrator = administrator();
        $selector = 'aside a[href="'.route('users.index').'"]';

        // Act
        $usersPage = appShellDocument(actingAs($administrator)->get(route('users.index')));
        $dashboard = appShellDocument(actingAs($administrator)->get(route('dashboard')));

        // Assert
        expect($usersPage->querySelector($selector)?->getAttribute('aria-current'))->toBe('page')
            ->and($dashboard->querySelector($selector))->not->toBeNull()
            ->and($dashboard->querySelector($selector)?->hasAttribute('aria-current'))->toBeFalse();
    });

    it('is hidden from members who cannot manage users', function (): void {
        // Arrange
        $member = basicUser();
        $promotion = Promotion::factory()->create();
        $promotion->users()->attach($member, [
            'role' => MembershipRole::Owner->value,
            'status' => MembershipStatus::Active->value,
        ]);

        // Act
        $response = actingAs($member)->get(route('dashboard'));

        // Assert
        $response
            ->assertOk()
            ->assertDontSee('User management')
            ->assertDontSeeHtml('href="'.route('users.index').'"');
    });
});

describe('account menu', function (): void {
    it('only links to destinations that exist', function (): void {
        // Arrange
        $administrator = administrator();

        // Act
        $document = appShellDocument(actingAs($administrator)->get(route('dashboard')));

        // Assert
        $hrefs = array_map(
            fn (Element $link): ?string => $link->getAttribute('href'),
            iterator_to_array($document->querySelectorAll('#account-menu a')),
        );
        expect($hrefs)->each->not->toStartWith('#')
            ->and($document->querySelector('#account-menu button[type="submit"]')?->textContent)->toContain('Log out');
    });
});
