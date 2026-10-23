<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\Crew\CrewRole;
use App\Models\Crew\CrewContact;
use App\Models\Events\EventPack;
use App\Rules\Events\HttpsUrlWithoutCredentials;
use App\Services\Events\LiveShowTimingService;
use App\ValueObjects\ProductionPlan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The state rules of a saved production plan: every assignment names a crew contact of the event's promotion who is
 * not archived (unless the assignment already held that contact), call times exist on the wall clock, and every cue
 * belongs to a current card item and an assignment of the plan. The shape of the submitted input is checked first, by the
 * same rules the Production form shows on its fields, so a save is safe from any caller.
 *
 * @phpstan-import-type Production from EventPack
 * @phpstan-import-type Assignment from EventPack
 * @phpstan-import-type Cue from EventPack
 *
 * @phpstan-type InputAssignment array{id: string, contact_id: int|numeric-string, role: string, station?: ?string, call_at?: ?string, timezone: string, offset?: ?string, setup?: ?string, during?: ?string, strike?: ?string, refresh_name?: bool}
 * @phpstan-type InputCue array{id: string, item_key: string, subject: string, trigger: string, instruction: string, role?: ?string, owner_id?: ?string, pronunciation?: ?string, track?: ?string, url?: ?string}
 * @phpstan-type Input array{notes?: ?string, assignments: list<InputAssignment>, cues: list<InputCue>}
 */
final readonly class EventProductionRequirements
{
    public function __construct(private LiveShowTimingService $timing) {}

    /**
     * Turn blank strings into null, then check the shape of the submitted input: allowed keys, uuid ids, lengths, enums
     * and https-only URLs.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validatedInput(array $input): array
    {
        array_walk_recursive($input, function (mixed &$value): void {
            if ($value === '') {
                $value = null;
            }
        });

        return Validator::make($input, [
            'notes' => ['nullable', 'string', 'max:10000'],
            'assignments' => ['present', 'array', 'max:100'],
            'assignments.*' => ['array:id,contact_id,name,role,station,call_at,timezone,offset,setup,during,strike,active,refresh_name'],
            'assignments.*.id' => ['required', 'uuid', 'distinct'],
            'assignments.*.contact_id' => ['required', 'integer', 'min:1'],
            'assignments.*.role' => ['required', Rule::enum(CrewRole::class)],
            'assignments.*.station' => ['required_if:assignments.*.role,other', 'nullable', 'string', 'max:160'],
            'assignments.*.call_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'assignments.*.timezone' => ['required', 'timezone:all'],
            'assignments.*.offset' => ['nullable', 'regex:/^[+-]\d{2}:\d{2}$/'],
            'assignments.*.setup' => ['nullable', 'string', 'max:2000'],
            'assignments.*.during' => ['nullable', 'string', 'max:2000'],
            'assignments.*.strike' => ['nullable', 'string', 'max:2000'],
            'assignments.*.refresh_name' => ['sometimes', 'boolean'],
            'cues' => ['present', 'array', 'max:1000'],
            'cues.*' => ['array:id,item_key,subject,trigger,instruction,role,owner_id,owner_name,owner_station,owner_role,owner_contact_id,pronunciation,track,url'],
            'cues.*.id' => ['required', 'uuid', 'distinct'],
            'cues.*.item_key' => ['required', 'string', 'max:100'],
            'cues.*.subject' => ['required', 'string', 'max:160'],
            'cues.*.trigger' => ['required', 'string', 'max:160'],
            'cues.*.instruction' => ['required', 'string', 'max:2000'],
            'cues.*.role' => ['nullable', Rule::enum(CrewRole::class)],
            'cues.*.owner_id' => ['nullable', 'uuid'],
            'cues.*.pronunciation' => ['nullable', 'string', 'max:500'],
            'cues.*.track' => ['nullable', 'string', 'max:500'],
            'cues.*.url' => ['nullable', 'url:https', 'max:2000', new HttpsUrlWithoutCredentials],
        ], ['assignments.*.station.required_if' => __('production.invalid_assignment')])->validate();
    }

    /**
     * @param  array<string, mixed>  $input  The input returned by validatedInput()
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
        $plan = ProductionPlan::fromArray($previous);
        $assignments = $this->assignments($input['assignments'], $plan, $contacts);

        return ['notes' => $input['notes'] ?? null, 'assignments' => $assignments, 'cues' => $this->cues($input['cues'], $plan, $assignments, $itemKeys)];
    }

    /**
     * The saved assignments: each submitted one pinned to a usable contact with a call time that exists, then the
     * previous ones that were left out, kept as inactive.
     *
     * @param  list<InputAssignment>  $entries
     * @param  Collection<int, CrewContact>  $contacts
     * @return list<Assignment>
     *
     * @throws ValidationException
     */
    private function assignments(array $entries, ProductionPlan $plan, Collection $contacts): array
    {
        $old = collect($plan->assignments())->keyBy('id');
        $assignments = [];

        foreach ($entries as $index => $entry) {
            $contact = $contacts->get((int) $entry['contact_id']);
            $retained = $old->get($entry['id']);
            $sameContact = $retained !== null && $retained['contact_id'] === (int) $entry['contact_id'];

            if ($contact === null || ($contact->archived_at !== null && (! $sameContact || ! $retained['active']))) {
                throw ValidationException::withMessages(["assignments.{$index}.contact_id" => __('production.invalid_contact')]);
            }

            if (($entry['call_at'] ?? null) !== null && ! $this->timing->wallTime($entry['call_at'], $entry['timezone'], $entry['offset'] ?? null) instanceof Carbon) {
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

        return $assignments;
    }

    /**
     * The saved cues: each submitted one on a current card item, within the 20-cue limit per item, owned by one of
     * the saved assignments (their name, station and role are copied onto the cue), then the previous cues of items
     * that are no longer on the card and were not resubmitted.
     *
     * @param  list<InputCue>  $entries
     * @param  list<Assignment>  $assignments
     * @param  list<string>  $itemKeys
     * @return list<Cue>
     *
     * @throws ValidationException
     */
    private function cues(array $entries, ProductionPlan $plan, array $assignments, array $itemKeys): array
    {
        $owners = collect($assignments)->keyBy('id');
        $cues = [];
        $counts = [];

        foreach ($entries as $index => $cue) {
            if (! in_array($cue['item_key'], $itemKeys, true)) {
                throw ValidationException::withMessages(["cues.{$index}.item_key" => __('production.invalid_cue')]);
            }

            $counts[$cue['item_key']] = ($counts[$cue['item_key']] ?? 0) + 1;
            $owner = isset($cue['owner_id']) ? $owners->get($cue['owner_id']) : null;

            if ($counts[$cue['item_key']] > 20 || (isset($cue['owner_id']) && $owner === null)) {
                throw ValidationException::withMessages(["cues.{$index}.owner_id" => __('production.invalid_cue')]);
            }

            $cues[] = ['id' => $cue['id'], 'item_key' => $cue['item_key'], 'subject' => $cue['subject'],
                'trigger' => $cue['trigger'], 'instruction' => $cue['instruction'], 'role' => $cue['role'] ?? null,
                'owner_id' => $cue['owner_id'] ?? null, 'owner_name' => $owner['name'] ?? null, 'owner_station' => $owner['station'] ?? null, 'owner_role' => $owner['role'] ?? null, 'owner_contact_id' => $owner['contact_id'] ?? null,
                'pronunciation' => $cue['pronunciation'] ?? null, 'track' => $cue['track'] ?? null, 'url' => $cue['url'] ?? null];
        }

        $submitted = array_column($cues, 'id');

        foreach ($plan->cues() as $cue) {
            if (! in_array($cue['item_key'], $itemKeys, true) && ! in_array($cue['id'], $submitted, true)) {
                $cues[] = $cue;
            }
        }

        return $cues;
    }
}
