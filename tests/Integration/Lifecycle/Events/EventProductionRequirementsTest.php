<?php

declare(strict_types=1);

use App\Lifecycle\Events\EventProductionRequirements;
use App\Models\Crew\CrewContact;
use Illuminate\Validation\ValidationException;

/**
 * Run a production input through both steps of the save and return the validation errors it raised.
 *
 * @param  array<string, mixed>  $input
 * @param  list<string>  $itemKeys
 * @return array<string, list<string>>
 */
function productionErrors(array $input, array $itemKeys, ?CrewContact $contact): array
{
    $requirements = resolve(EventProductionRequirements::class);
    $contacts = collect($contact === null ? [] : [$contact->id => $contact]);

    try {
        $requirements->ensureProduction($requirements->validatedInput($input), null, $itemKeys, $contacts);
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

/** @return array{id: string, contact_id: int, role: string, timezone: string} */
function productionAssignment(CrewContact $contact): array
{
    return ['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001', 'contact_id' => $contact->id, 'role' => 'sound', 'timezone' => 'UTC'];
}

/** @return array{id: string, item_key: string, subject: string, trigger: string, instruction: string} */
function productionCue(int $number = 1): array
{
    return ['id' => sprintf('eed36a82-90cb-4a79-b2cc-%012d', $number), 'item_key' => 'match:1', 'subject' => 'Harbor Hero',
        'trigger' => 'Caller signal', 'instruction' => 'Play entrance'];
}

test('a valid plan raises no errors', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create();
    $input = ['assignments' => [productionAssignment($contact)], 'cues' => [[...productionCue(), 'owner_id' => productionAssignment($contact)['id']]]];

    // Act
    $errors = productionErrors($input, ['match:1'], $contact);

    // Assert
    expect($errors)->toBeEmpty();
});

test('each state refusal names its field and message', function (string $case, string $field, string $message): void {
    // Arrange
    $contact = CrewContact::factory()->create();
    $input = ['assignments' => [productionAssignment($contact)], 'cues' => [productionCue()]];
    $known = $contact;

    if ($case === 'contact outside the promotion') {
        $known = null;
    }
    if ($case === 'archived contact') {
        $contact->archived_at = now();
    }
    if ($case === 'repeated hour without offset') {
        $input['assignments'][0]['timezone'] = 'America/New_York';
        $input['assignments'][0]['call_at'] = '2026-11-01T01:30';
    }
    if ($case === 'skipped hour') {
        $input['assignments'][0]['timezone'] = 'America/New_York';
        $input['assignments'][0]['call_at'] = '2026-03-08T02:30';
    }
    if ($case === 'cue on an item that is not on the card') {
        $input['cues'][0]['item_key'] = 'match:99';
    }
    if ($case === 'cue owner that is not an assignment') {
        $input['cues'][0]['owner_id'] = '4e833c54-5f60-48b8-91c5-dbf85ca51111';
    }
    if ($case === 'twenty-first cue on one item') {
        $input['cues'] = array_map(productionCue(...), range(1, 21));
    }

    // Act
    $errors = productionErrors($input, ['match:1'], $known);

    // Assert
    expect($errors)->toBe([$field => [__($message)]]);
})->with([
    'contact outside the promotion' => ['contact outside the promotion', 'assignments.0.contact_id', 'production.invalid_contact'],
    'archived contact' => ['archived contact', 'assignments.0.contact_id', 'production.invalid_contact'],
    'repeated hour without offset' => ['repeated hour without offset', 'assignments.0.call_at', 'production.invalid_assignment'],
    'skipped hour' => ['skipped hour', 'assignments.0.call_at', 'production.invalid_assignment'],
    'cue on an item that is not on the card' => ['cue on an item that is not on the card', 'cues.0.item_key', 'production.invalid_cue'],
    'cue owner that is not an assignment' => ['cue owner that is not an assignment', 'cues.0.owner_id', 'production.invalid_cue'],
    'twenty-first cue on one item' => ['twenty-first cue on one item', 'cues.20.owner_id', 'production.invalid_cue'],
]);

test('an Other role without a station is refused on the station field', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create();
    $input = ['assignments' => [[...productionAssignment($contact), 'role' => 'other', 'station' => '   ']], 'cues' => []];

    // Act
    $errors = productionErrors($input, [], $contact);

    // Assert
    expect($errors)->toBe(['assignments.0.station' => [__('production.invalid_assignment')]]);
});

test('an Other role with a station is accepted', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create();
    $input = ['assignments' => [[...productionAssignment($contact), 'role' => 'other', 'station' => 'Mixer']], 'cues' => []];

    // Act
    $errors = productionErrors($input, [], $contact);

    // Assert
    expect($errors)->toBeEmpty();
});

test('a cue link with credentials is refused on the link field', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create();
    $input = ['assignments' => [productionAssignment($contact)], 'cues' => [[...productionCue(), 'url' => 'https://secret:password@example.com/track']]];

    // Act
    $errors = productionErrors($input, ['match:1'], $contact);

    // Assert
    expect($errors)->toBe(['cues.0.url' => [__('production.invalid_cue')]]);
});

test('an input-shape error is raised before a state error', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create();
    $input = ['assignments' => [[...productionAssignment($contact), 'role' => 'other']], 'cues' => [[...productionCue(), 'item_key' => 'match:99']]];

    // Act
    $errors = productionErrors($input, ['match:1'], null);

    // Assert
    expect($errors)->toBe(['assignments.0.station' => [__('production.invalid_assignment')]]);
});

test('an assignment error wins over a cue error', function (): void {
    // Arrange
    $contact = CrewContact::factory()->create();
    $input = ['assignments' => [productionAssignment($contact)], 'cues' => [[...productionCue(), 'item_key' => 'match:99']]];

    // Act
    $errors = productionErrors($input, ['match:1'], null);

    // Assert
    expect($errors)->toBe(['assignments.0.contact_id' => [__('production.invalid_contact')]]);
});
