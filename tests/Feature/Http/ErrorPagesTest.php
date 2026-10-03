<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Route::middleware('web')->get('/error-page-probe/expired', fn () => abort(419));
    Route::middleware('web')->get('/error-page-probe/broken', fn () => throw new RuntimeException('Secret failure details'));
});

describe('branded error pages', function (): void {
    test('a guest who opens a missing page gets the branded not found page with a way to sign in', function (): void {
        // Act
        $response = get('/this-page-does-not-exist');

        // Assert
        $response->assertNotFound()
            ->assertSee(__('errors.not_found_title'))
            ->assertSee(__('errors.not_found_description'))
            ->assertSeeHtml(route('login'))
            ->assertDontSeeHtml(route('logout'));
    });

    test('a signed-in member who opens a missing record gets a way back to the dashboard and a log out button', function (): void {
        // Arrange
        $user = basicUser();
        $promotion = Promotion::factory()->create();
        $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
        actingAs($user);

        // Act
        $response = get(route('wrestlers.show', 999999));

        // Assert
        $response->assertNotFound()
            ->assertSee(__('errors.not_found_title'))
            ->assertSeeHtml(route('dashboard'))
            ->assertSeeHtml(route('logout'))
            ->assertSee(__('auth-forms.log_out'));
    });

    test('a member without permission gets the branded forbidden page', function (): void {
        // Arrange
        $user = basicUser();
        $promotion = Promotion::factory()->create();
        $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
        actingAs($user);

        // Act
        $response = get(route('users.index'));

        // Assert
        $response->assertForbidden()
            ->assertSee(__('errors.forbidden_title'))
            ->assertSee(__('errors.forbidden_description'))
            ->assertSeeHtml(route('dashboard'))
            ->assertSeeHtml(route('logout'));
    });

    test('an expired form gets the branded page expired page', function (): void {
        // Act
        $response = get('/error-page-probe/expired');

        // Assert
        $response->assertStatus(419)
            ->assertSee(__('errors.page_expired_title'))
            ->assertSee(__('errors.page_expired_description'))
            ->assertSeeHtml(route('login'));
    });

    test('an unexpected failure gets the branded server error page without the failure details', function (): void {
        // Arrange
        config(['app.debug' => false]);

        // Act
        $response = get('/error-page-probe/broken');

        // Assert
        $response->assertInternalServerError()
            ->assertSee(__('errors.server_error_title'))
            ->assertSee(__('errors.server_error_description'))
            ->assertDontSee('Secret failure details');
    });
});
