<?php

declare(strict_types=1);
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Storylines\Components\Create;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('an administrator chooses an explicit promotion and creates a private story', function () {
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $page = livewire(Create::class, ['promotionId' => $promotion->id, 'title' => 'The betrayal', 'premise' => 'Partners become rivals.', 'payoff' => 'Title match.']);
    $page->set('promotionId', $promotion->id);
    $page->call('save');
    $story = Storyline::query()->firstOrFail();
    $page->assertHasNoErrors()->assertRedirect(route('storylines.show', $story));
    expect($story->promotion_id)->toBe($promotion->id)->and($story->payoff)->toBe('Title match.');
});
test('a promotion owner cannot create an unowned or foreign story', function () {
    $promotion = Promotion::factory()->create();
    $foreign = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $page = livewire(Create::class, ['title' => 'Private', 'premise' => 'Private premise.']);
    $page->set('promotionId', null)->call('save')->assertHasErrors(['promotionId']);
    $page->set('promotionId', $foreign->id)->call('save')->assertForbidden();
    expect(Storyline::query()->count())->toBe(0);
});

test('a story needs a name and a premise within their limits', function (array $fields, string $error) {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $page = livewire(Create::class, ['promotionId' => $promotion->id, 'title' => 'The betrayal', 'premise' => 'Partners become rivals.']);
    foreach ($fields as $field => $value) {
        $page->set($field, $value);
    }

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors([$error]);
    expect(Storyline::query()->count())->toBe(0);
})->with([
    'no name' => [['title' => ''], 'title'],
    'overlong name' => [['title' => str_repeat('a', 161)], 'title'],
    'no premise' => [['premise' => ''], 'premise'],
    'overlong premise' => [['premise' => str_repeat('a', 4001)], 'premise'],
    'overlong payoff' => [['payoff' => str_repeat('a', 4001)], 'payoff'],
]);

test('an owner downgraded while the form is open creates nothing', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $page = livewire(Create::class, ['title' => 'Private', 'premise' => 'Private premise.']);
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member->value]);
    $context->forgetMemberships();

    // Act
    $page->call('save');

    // Assert
    $page->assertForbidden();
    expect(Storyline::query()->count())->toBe(0);
});
