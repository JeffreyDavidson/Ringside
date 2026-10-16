<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\Crew\CrewRole;
use App\Models\Crew\CrewContact;
use App\Models\Events\EventPack;
use App\Services\Events\LiveShowTimingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The state rules of a saved production plan: every assignment names a crew contact of the event's promotion who is
 * not archived (unless the assignment already held that contact), call times exist on the wall clock, and every cue
 * belongs to a current card item and an assignment of the plan. The shape of the submitted input is validated by the
 * form that feeds the save.
 *
 * @phpstan-import-type Production from EventPack
 *
 * @phpstan-type Input array{notes?: ?string, assignments: list<array{id: string, contact_id: int|numeric-string, role: string, station?: ?string, call_at?: ?string, timezone: string, offset?: ?string, setup?: ?string, during?: ?string, strike?: ?string, refresh_name?: bool}>, cues: list<array{id: string, item_key: string, subject: string, trigger: string, instruction: string, role?: ?string, owner_id?: ?string, pronunciation?: ?string, track?: ?string, url?: ?string}>}
 */
final readonly class EventProductionRequirements
{
    public function __construct(private LiveShowTimingService $timing) {}

    /**
     * @param  array<string, mixed>  $input  The validated production form input
     * @param  ?Production  $previous
     * @param  list<string>  $itemKeys
     * @param  Collection<int, CrewContact>  $contacts  The locked contacts of the event's promotion, keyed by id
     * @return Production
     *
     * @throws ValidationException
     */
    public function ensureProduction(array $input, ?array $previous, array $itemKeys, Collection $contacts): array
    {
        /** @var Input $input */
        $old = collect($previous['assignments'] ?? [])->keyBy('id');
        $assignments = [];
        foreach ($input['assignments'] as $index => $entry) {
            $contact = $contacts->get((int) $entry['contact_id']);
            $retained = $old->get($entry['id']);
            $sameContact = $retained !== null && $retained['contact_id'] === (int) $entry['contact_id'];
            if ($contact === null || ($contact->archived_at !== null && (! $sameContact || ! $retained['active']))) {
                throw ValidationException::withMessages(["assignments.{$index}.contact_id" => __('production.invalid_contact')]);
            }
            if (($entry['role'] === CrewRole::Other->value && trim($entry['station'] ?? '') === '')
                || (($entry['call_at'] ?? null) !== null && ! $this->timing->wallTime($entry['call_at'], $entry['timezone'], $entry['offset'] ?? null) instanceof Carbon)) {
                throw ValidationException::withMessages(["assignments.{$index}.call_at" => __('production.invalid_assignment')]);
            }
            $assignments[] = ['id' => $entry['id'], 'contact_id' => $contact->id,
                'name' => $sameContact && ! ($entry['refresh_name'] ?? false) ? $retained['name'] : $contact->name,
                'role' => $entry['role'], 'station' => $entry['station'] ?? null, 'call_at' => $entry['call_at'] ?? null,
                'timezone' => $entry['timezone'], 'offset' => $entry['offset'] ?? null,
                'setup' => $entry['setup'] ?? null, 'during' => $entry['during'] ?? null, 'strike' => $entry['strike'] ?? null, 'active' => true];
            $old->forget($entry['id']);
        }
        foreach ($old as $entry) {
            $assignments[] = [...$entry, 'active' => false];
        }
        $owners = collect($assignments)->keyBy('id');
        $cues = [];
        $counts = [];
        foreach ($input['cues'] as $index => $cue) {
            if (! in_array($cue['item_key'], $itemKeys, true)) {
                throw ValidationException::withMessages(["cues.{$index}.item_key" => __('production.invalid_cue')]);
            }
            $counts[$cue['item_key']] = ($counts[$cue['item_key']] ?? 0) + 1;
            $owner = isset($cue['owner_id']) ? $owners->get($cue['owner_id']) : null;
            $url = isset($cue['url']) ? parse_url($cue['url']) : [];
            if ($counts[$cue['item_key']] > 20 || (isset($cue['owner_id']) && $owner === null)
                || $url === false || isset($url['user']) || isset($url['pass'])) {
                throw ValidationException::withMessages(["cues.{$index}.owner_id" => __('production.invalid_cue')]);
            }
            $cues[] = ['id' => $cue['id'], 'item_key' => $cue['item_key'], 'subject' => $cue['subject'],
                'trigger' => $cue['trigger'], 'instruction' => $cue['instruction'], 'role' => $cue['role'] ?? null,
                'owner_id' => $cue['owner_id'] ?? null, 'owner_name' => $owner['name'] ?? null, 'owner_station' => $owner['station'] ?? null, 'owner_role' => $owner['role'] ?? null, 'owner_contact_id' => $owner['contact_id'] ?? null,
                'pronunciation' => $cue['pronunciation'] ?? null, 'track' => $cue['track'] ?? null, 'url' => $cue['url'] ?? null];
        }
        $submitted = array_column($cues, 'id');
        foreach ($previous['cues'] ?? [] as $cue) {
            if (! in_array($cue['item_key'], $itemKeys, true) && ! in_array($cue['id'], $submitted, true)) {
                $cues[] = $cue;
            }
        }

        return ['notes' => $input['notes'] ?? null, 'assignments' => $assignments, 'cues' => $cues];
    }
}
