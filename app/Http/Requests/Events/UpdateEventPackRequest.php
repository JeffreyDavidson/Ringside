<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** @phpstan-import-type Content from EventPack */
class UpdateEventPackRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        $ownedMatch = Rule::exists('events_matches', 'id')
            ->where('event_id', $event->id);

        return [
            'complete' => ['required', 'accepted'],
            'version' => ['required', 'integer', 'min:0'],
            'fingerprint' => ['required', 'string', 'size:64'],

            'timezone' => ['required', 'timezone:all'],
            'doors_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'revision' => ['nullable', 'string', 'max:40'],
            'master' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'follow_up' => ['nullable', 'string', 'max:10000'],

            'reviewed' => ['sometimes', 'accepted'],
            'print_after' => ['nullable', 'boolean'],

            'matches' => ['nullable', 'array', 'max:500'],
            'matches.*' => ['array:match_id,planned_at,revised_at,minutes,notes'],
            'matches.*.match_id' => ['required', 'integer', 'distinct', $ownedMatch],
            'matches.*.planned_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'matches.*.revised_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'matches.*.minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'matches.*.notes' => ['nullable', 'string', 'max:2000'],

            'segments' => ['nullable', 'array', 'max:50'],
            'segments.*' => ['array:id,label,after_match_id,planned_at,revised_at,minutes,notes'],
            'segments.*.id' => ['required', 'uuid', 'distinct'],
            'segments.*.label' => ['required', 'string', 'max:160'],
            'segments.*.after_match_id' => ['nullable', 'integer', Rule::exists('events_matches', 'id')->where('event_id', $event->id)->whereNull('deleted_at')],
            'segments.*.planned_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'segments.*.revised_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'segments.*.minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'segments.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['complete.required' => __('event-pack.incomplete')];
    }

    /** @return Content */
    public function content(): array
    {
        /** @var array{timezone: string, doors_at?: ?string, revision?: ?string, master?: ?string, notes?: ?string, follow_up?: ?string, matches?: ?array<int, array{match_id: int|numeric-string, planned_at?: ?string, revised_at?: ?string, minutes?: int|numeric-string|null, notes?: ?string}>, segments?: ?array<int, array{id: string, label: string, after_match_id?: int|numeric-string|null, planned_at?: ?string, revised_at?: ?string, minutes?: int|numeric-string|null, notes?: ?string}>} $content */
        $content = $this->safe()->except(['complete', 'version', 'fingerprint', 'reviewed', 'print_after']);
        $content += [
            'doors_at' => null,
            'revision' => null,
            'master' => null,
            'notes' => null,
            'follow_up' => null,
        ];

        $content['matches'] = array_values($content['matches'] ?? []);
        $content['segments'] = array_values($content['segments'] ?? []);
        foreach ($content['matches'] as $index => $entry) {
            $entry += ['planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null];
            $entry['match_id'] = (int) $entry['match_id'];
            $entry['minutes'] = $entry['minutes'] === null ? null : (int) $entry['minutes'];
            $content['matches'][$index] = $entry;
        }

        foreach ($content['segments'] as $index => $segment) {
            $segment += ['after_match_id' => null, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null];
            $segment['after_match_id'] = $segment['after_match_id'] === null ? null : (int) $segment['after_match_id'];
            $segment['minutes'] = $segment['minutes'] === null ? null : (int) $segment['minutes'];
            $content['segments'][$index] = $segment;
        }

        return $content;
    }
}
