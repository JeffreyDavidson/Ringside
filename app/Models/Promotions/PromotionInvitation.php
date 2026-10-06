<?php

declare(strict_types=1);

namespace App\Models\Promotions;

use App\Builders\Promotions\PromotionInvitationBuilder;
use App\Enums\Promotions\MembershipRole;
use Database\Factories\Promotions\PromotionInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A pending invitation to join a promotion, keyed by the invited email address rather than by an account.
 * It grants nothing: whoever signs in with that email may accept it (AcceptPromotionInvitationAction).
 *
 * @property int $id
 * @property int $promotion_id
 * @property string $email
 * @property MembershipRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Promotion $promotion
 */
#[Fillable('promotion_id', 'email', 'role')]
#[UseEloquentBuilder(PromotionInvitationBuilder::class)]
#[UseFactory(PromotionInvitationFactory::class)]
class PromotionInvitation extends Model
{
    /** @use HasFactory<PromotionInvitationFactory> */
    use HasFactory;

    /** The stored form of an email address: trimmed and lowercase, like User::email, so lookups never depend on case. */
    public static function normalizeEmail(string $email): string
    {
        return Str::lower(mb_trim($email));
    }

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizeEmail($value));
    }

    /** @return array<string, string> */
    #[\Override]
    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
        ];
    }
}
