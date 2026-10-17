<?php

declare(strict_types=1);

use App\Services\Events\PublicEventCardService;

/*
 * Released snapshots store these fingerprints, so the pinned hashes below were produced by the classes that existed
 * before the public event source was split into queries and services. A change to what goes into a fingerprint
 * makes every released page look edited.
 */

/** @return array{fingerprint: string, schedule: string, title: string, promotion: string, date: ?string, timezone: string, venue: ?string, address: ?string, matches: list<array{id: int, label: string, type: string, stipulation: ?string, titles: string, finish: ?string, winner: ?string}>} */
function publicCardSource(): array
{
    return ['fingerprint' => 'source', 'schedule' => 'schedule', 'title' => 'Harbor Night', 'promotion' => 'Harbor Wrestling',
        'date' => '2026-10-17T19:00:00+00:00', 'timezone' => 'America/New_York', 'venue' => 'Harbor Arena', 'address' => '1 Dock Road, Portland, ME',
        'matches' => [
            ['id' => 11, 'label' => 'Robin North vs Casey Shore', 'type' => 'Singles', 'stipulation' => 'Cage Match', 'titles' => 'World Title', 'finish' => 'Pinfall', 'winner' => 'Robin North'],
            ['id' => 12, 'label' => 'Ada Vale & Bo Reed vs Cy Hart & Di Moor', 'type' => 'Tag Team', 'stipulation' => null, 'titles' => '', 'finish' => null, 'winner' => null],
        ]];
}

/** @return list<array{id: int, type: bool, stipulation: bool, titles: bool}> */
function publicCardSelection(): array
{
    return [['id' => 11, 'type' => true, 'stipulation' => true, 'titles' => false], ['id' => 12, 'type' => true, 'stipulation' => false, 'titles' => true]];
}

describe('public event card service', function (): void {
    it('hashes a value list as a sha256 of its serialization', function (): void {
        // Arrange
        $values = ['2026-10-17T19:00:00+00:00', 5, 'Harbor Arena', 'America/New_York', '1 Dock Road, Portland, ME'];

        // Act
        $fingerprint = resolve(PublicEventCardService::class)->fingerprint($values);

        // Assert
        expect($fingerprint)->toBe(hash('sha256', serialize($values)))
            ->and($fingerprint)->toBe('7695ccd4c468df0203fdc6b1e43a1bb34cd983f2b928ddbbdb3d81c34021b67d');
    });

    it('prints only the parts of each selected match the editor chose, in card order', function (): void {
        // Arrange
        $selection = array_reverse(publicCardSelection());

        // Act
        $rows = resolve(PublicEventCardService::class)->rows(publicCardSource(), $selection);

        // Assert
        expect($rows)->toBe([
            ['label' => 'Robin North vs Casey Shore', 'details' => 'Singles · Cage Match', 'outcome' => null],
            ['label' => 'Ada Vale & Bo Reed vs Cy Hart & Di Moor', 'details' => 'Tag Team', 'outcome' => null],
        ]);
    });

    it('leaves out matches that are not selected', function (): void {
        // Arrange
        $selection = [['id' => 12, 'type' => false, 'stipulation' => false, 'titles' => true]];

        // Act
        $rows = resolve(PublicEventCardService::class)->rows(publicCardSource(), $selection);

        // Assert
        expect($rows)->toBe([['label' => 'Ada Vale & Bo Reed vs Cy Hart & Di Moor', 'details' => '', 'outcome' => null]]);
    });

    it('fingerprints the title, promotion and selected rows', function (): void {
        // Arrange
        $source = publicCardSource();

        // Act
        $fingerprint = resolve(PublicEventCardService::class)->cardFingerprint($source, publicCardSelection());

        // Assert
        expect($fingerprint)->toBe('9c377ff84969fbdaf9265b75dc5b700cec0deeb680dab0dabfe49321ac96ea7e')
            ->and($fingerprint)->toBe(hash('sha256', serialize(['Harbor Night', 'Harbor Wrestling', [
                ['label' => 'Robin North vs Casey Shore', 'details' => 'Singles · Cage Match', 'outcome' => null],
                ['label' => 'Ada Vale & Bo Reed vs Cy Hart & Di Moor', 'details' => 'Tag Team', 'outcome' => null],
            ]])));
    });

    it('picks the matches whose results are released', function (): void {
        // Arrange
        $cards = resolve(PublicEventCardService::class);

        // Act
        $results = $cards->resultSource(publicCardSource(), [11]);

        // Assert
        expect(array_column($results, 'id'))->toBe([11])
            ->and($cards->fingerprint($results))->toBe('5775808d04ca5b1b621b26566cd31e09b441e0cb845b8e7d6468b54a81a73054');
    });

    it('builds the released card from a scheduled draft', function (): void {
        // Arrange
        $draft = ['announcement' => 'scheduled', 'summary' => 'S', 'notice' => null, 'address' => true, 'doors_at' => '2026-10-17T18:00',
            'doors_offset' => '-04:00', 'tickets' => 'T', 'poster' => null, 'matches' => publicCardSelection(), 'results' => []];

        // Act
        $card = resolve(PublicEventCardService::class)->card(publicCardSource(), $draft);

        // Assert
        expect($card)->toBe(['title' => 'Harbor Night', 'promotion' => 'Harbor Wrestling', 'announcement' => 'scheduled', 'summary' => 'S', 'notice' => null,
            'date' => '2026-10-17T19:00:00+00:00', 'timezone' => 'America/New_York', 'venue' => 'Harbor Arena', 'address' => '1 Dock Road, Portland, ME',
            'doors' => '2026-10-17T18:00:00-04:00', 'tickets' => 'T', 'poster' => null, 'rows' => [
                ['label' => 'Robin North vs Casey Shore', 'details' => 'Singles · Cage Match', 'outcome' => null],
                ['label' => 'Ada Vale & Bo Reed vs Cy Hart & Di Moor', 'details' => 'Tag Team', 'outcome' => null],
            ]]);
    });

    it('hides the date, doors, tickets and rows of a notice', function (): void {
        // Arrange
        $draft = ['announcement' => 'postponed', 'summary' => null, 'notice' => 'Moved indoors.', 'address' => false, 'doors_at' => '2026-10-17T18:00',
            'doors_offset' => '-04:00', 'tickets' => 'T', 'poster' => null, 'matches' => publicCardSelection(), 'results' => []];

        // Act
        $card = resolve(PublicEventCardService::class)->card(publicCardSource(), $draft);

        // Assert
        expect($card['date'])->toBeNull()
            ->and($card['doors'])->toBeNull()
            ->and($card['tickets'])->toBeNull()
            ->and($card['address'])->toBeNull()
            ->and($card['rows'])->toBeEmpty()
            ->and($card['notice'])->toBe('Moved indoors.');
    });
});
