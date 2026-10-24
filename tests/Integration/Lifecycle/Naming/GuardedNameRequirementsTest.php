<?php

declare(strict_types=1);

use App\Exceptions\Roster\Wrestlers\NameTakenException as WrestlerNameTakenException;
use App\Exceptions\Titles\NameTakenException;
use App\Lifecycle\Naming\GuardedNameRequirements;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * The titles of the promotion, the way the Title actions look for a name taken: deleted ones included.
 *
 * @return Builder<Title>
 */
function titlesOfPromotion(?int $promotionId): Builder
{
    return Title::query()
        ->withoutGlobalScope(PromotionContextScope::class)
        ->withTrashed()
        ->where('promotion_id', $promotionId);
}

describe('guarded name requirements', function (): void {
    it('refuses a name another record of the scope already uses', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        Title::factory()
            ->for($promotion)
            ->create(['name' => 'World Title']);

        // Act
        $act = fn () => resolve(GuardedNameRequirements::class)->ensureAvailable(
            titlesOfPromotion($promotion->id),
            'name',
            'World Title',
            fn (): NameTakenException => NameTakenException::name('World Title'),
        );

        // Assert
        expect($act)->toThrow(NameTakenException::class, __('titles.validation.name_taken', ['name' => 'World Title']));
    });

    it('allows a name only another promotion or no promotion uses', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        Title::factory()
            ->for(Promotion::factory())
            ->create(['name' => 'World Title']);
        Title::factory()
            ->create(['name' => 'World Title', 'promotion_id' => null]);

        // Act
        $act = fn () => resolve(GuardedNameRequirements::class)->ensureAvailable(
            titlesOfPromotion($promotion->id),
            'name',
            'World Title',
            fn (): NameTakenException => NameTakenException::name('World Title'),
        );

        // Assert
        expect($act)->not->toThrow(NameTakenException::class);
    });

    it('matches a record without a promotion only against records without one', function (): void {
        // Arrange
        Title::factory()
            ->create(['name' => 'World Title', 'promotion_id' => null]);
        Title::factory()
            ->for(Promotion::factory())
            ->create(['name' => 'Tag Title']);
        $requirements = resolve(GuardedNameRequirements::class);

        // Act
        $sameName = fn () => $requirements->ensureAvailable(
            titlesOfPromotion(null),
            'name',
            'World Title',
            fn (): NameTakenException => NameTakenException::name('World Title'),
        );
        $otherPromotionsName = fn () => $requirements->ensureAvailable(
            titlesOfPromotion(null),
            'name',
            'Tag Title',
            fn (): NameTakenException => NameTakenException::name('Tag Title'),
        );

        // Assert
        expect($sameName)->toThrow(NameTakenException::class)
            ->and($otherPromotionsName)->not->toThrow(NameTakenException::class);
    });

    it('counts a deleted record only when the query includes deleted ones', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        Title::factory()
            ->for($promotion)
            ->trashed()
            ->create(['name' => 'World Title']);
        $requirements = resolve(GuardedNameRequirements::class);
        $taken = fn (): NameTakenException => NameTakenException::name('World Title');

        // Act
        $withoutDeleted = fn () => $requirements->ensureAvailable(
            Title::query()->withoutGlobalScope(PromotionContextScope::class)->where('promotion_id', $promotion->id),
            'name',
            'World Title',
            $taken,
        );
        $withDeleted = fn () => $requirements->ensureAvailable(titlesOfPromotion($promotion->id), 'name', 'World Title', $taken);

        // Assert
        expect($withoutDeleted)->not->toThrow(NameTakenException::class)
            ->and($withDeleted)->toThrow(NameTakenException::class);
    });

    it('ignores the record being updated', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $own = Title::factory()
            ->for($promotion)
            ->create(['name' => 'World Title']);

        // Act
        $act = fn () => resolve(GuardedNameRequirements::class)->ensureAvailable(
            titlesOfPromotion($promotion->id),
            'name',
            'World Title',
            fn (): NameTakenException => NameTakenException::name('World Title'),
            except: $own,
        );

        // Assert
        expect($act)->not->toThrow(NameTakenException::class);
    });

    it('still refuses a name another record uses when it ignores the record being updated', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        $own = Title::factory()
            ->for($promotion)
            ->create(['name' => 'World Title']);
        Title::factory()
            ->for($promotion)
            ->create(['name' => 'World Title']);

        // Act
        $act = fn () => resolve(GuardedNameRequirements::class)->ensureAvailable(
            titlesOfPromotion($promotion->id),
            'name',
            'World Title',
            fn (): NameTakenException => NameTakenException::name('World Title'),
            except: $own,
        );

        // Assert
        expect($act)->toThrow(NameTakenException::class);
    });

    it('checks the column it is given, so a signature move is guarded like a name', function (): void {
        // Arrange
        $promotion = Promotion::factory()
            ->create();
        Wrestler::factory()
            ->for($promotion)
            ->create(['name' => 'Rick', 'signature_move' => 'Sunset Flip']);
        $requirements = resolve(GuardedNameRequirements::class);
        $wrestlers = fn (): Builder => Wrestler::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->withTrashed()
            ->where('promotion_id', $promotion->id);

        // Act
        $moveTaken = fn () => $requirements->ensureAvailable(
            $wrestlers(),
            'signature_move',
            'Sunset Flip',
            fn (): WrestlerNameTakenException => WrestlerNameTakenException::signatureMove('Sunset Flip'),
        );
        $moveFree = fn () => $requirements->ensureAvailable(
            $wrestlers(),
            'signature_move',
            'Moonsault',
            fn (): WrestlerNameTakenException => WrestlerNameTakenException::signatureMove('Moonsault'),
        );

        // Assert
        expect($moveTaken)->toThrow(WrestlerNameTakenException::class, __('wrestlers.validation.signature_move_taken', ['signature_move' => 'Sunset Flip']))
            ->and($moveFree)->not->toThrow(WrestlerNameTakenException::class);
    });
});
