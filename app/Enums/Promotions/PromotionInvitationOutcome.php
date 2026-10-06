<?php

declare(strict_types=1);

namespace App\Enums\Promotions;

enum PromotionInvitationOutcome: string
{
    case Invited = 'invited';
    case AlreadyInvited = 'already_invited';
    case AlreadyMember = 'already_member';
}
