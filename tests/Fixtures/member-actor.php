<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;

/** Sign in as an active member of the event's promotion, who may not see production data. */
function actAsMemberOf(Event $event): void
{
    $member = basicUser();
    $promotion = Promotion::query()->findOrFail($event->promotion_id);
    $promotion->users()->attach($member, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($member);

    $context = resolve(PromotionContextService::class);
    $context->clear();
    $context->set($promotion);
    $context->enforce();
}
