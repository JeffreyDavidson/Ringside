<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\Crew\CrewRole;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-import-type Production from EventPack
 * @phpstan-import-type Assignment from EventPack
 * @phpstan-import-type Cue from EventPack
 */
class EventProductionValidationService
{
    public function __construct(private readonly LiveShowTimingService $timing) {}

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:10000'],
            'assignments' => ['present', 'array', 'max:100'],
            'assignments.*' => ['array:id,contact_id,name,role,station,call_at,timezone,offset,setup,during,strike,active,refresh_name'],
            'assignments.*.id' => ['required', 'uuid', 'distinct'],
            'assignments.*.contact_id' => ['required', 'integer', 'min:1'],
            'assignments.*.role' => ['required', Rule::enum(CrewRole::class)],
            'assignments.*.station' => ['nullable', 'string', 'max:160'],
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
            'cues.*.url' => ['nullable', 'url:https', 'max:2000'],
        ];
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function validateInput(array $input): array
    {
        array_walk_recursive($input, function (mixed &$value): void {
            if ($value === '') {
                $value = null;
            }
        });

        return Validator::make($input, $this->rules())->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  ?Production  $previous
     * @param  list<string>  $itemKeys
     * @return Production
     */
    public function validate(Event $event, array $input, ?array $previous, array $itemKeys): array
    {
        /** @var array{notes?: ?string, assignments: list<array{id: string, contact_id: int|numeric-string, role: string, station?: ?string, call_at?: ?string, timezone: string, offset?: ?string, setup?: ?string, during?: ?string, strike?: ?string, refresh_name?: bool}>, cues: list<array{id: string, item_key: string, subject: string, trigger: string, instruction: string, role?: ?string, owner_id?: ?string, pronunciation?: ?string, track?: ?string, url?: ?string}>} $input */
        $input = $this->validateInput($input);
        $old = collect($previous['assignments'] ?? [])->keyBy('id');
        $contacts = CrewContact::query()->where('promotion_id', $event->promotion_id)
            ->whereKey(array_column($input['assignments'], 'contact_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
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
