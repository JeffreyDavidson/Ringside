<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\Events\PublicAnnouncement;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Rules\Events\PublicDoorsTime;
use App\Rules\Events\PublicTicketUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/** @phpstan-import-type Draft from EventPublication */
class SaveEventPublicationRequest extends FormRequest
{
    /** Browsers can send an empty multipart part for an untouched optional file control. */
    protected function prepareForValidation(): void
    {
        $file = $this->file('poster');

        if ($file instanceof UploadedFile && $file->getClientOriginalName() === '' && $file->getSize() === 0) {
            $this->files->remove('poster');
            $this->convertedFiles = [];
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        $event->load(['promotion', 'venue']);

        $timezone = $event->venue->timezone ?? $event->promotion->timezone ?? config()->string('app.timezone');
        $ownedMatch = Rule::exists('events_matches', 'id')
            ->where('event_id', $event->id)
            ->whereNull('deleted_at');
        $offset = $this->input('doors_offset');

        return [
            'complete' => ['required', 'accepted'],
            'version' => ['required', 'integer', 'min:0'],
            'fingerprint' => ['required', 'string', 'size:64'],

            'announcement' => ['required', Rule::enum(PublicAnnouncement::class)],
            'summary' => ['nullable', 'string', 'max:2000'],
            'notice' => ['required_if:announcement,cancelled,postponed,rescheduled', 'nullable', 'string', 'max:1000'],
            'address' => ['sometimes', 'boolean'],
            'doors_at' => ['nullable', 'date_format:Y-m-d\TH:i', new PublicDoorsTime($timezone, is_string($offset) ? $offset : '')],
            'doors_offset' => ['required_with:doors_at', 'nullable', 'string', 'max:6'],
            'tickets' => ['nullable', 'url:https', 'max:2048', new PublicTicketUrl],

            'matches' => ['nullable', 'array', 'max:500'],
            'matches.*' => ['array:id,selected,type,stipulation,titles'],
            'matches.*.selected' => ['sometimes', 'boolean'],
            'matches.*.id' => ['required', 'integer', 'distinct', $ownedMatch],
            'matches.*.type' => ['sometimes', 'boolean'],
            'matches.*.stipulation' => ['sometimes', 'boolean'],
            'matches.*.titles' => ['sometimes', 'boolean'],
            'results' => ['nullable', 'array', 'max:500'],
            'results.*' => ['integer', 'distinct', $ownedMatch],

            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'poster_alt' => ['required_with:poster', 'nullable', 'string', 'max:400'],
            'poster_caption' => ['nullable', 'string', 'max:1000'],
            'remove_poster' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'complete.required' => __('public-events.incomplete'),
            'complete.accepted' => __('public-events.incomplete'),
        ];
    }

    /** @return Draft */
    public function draft(): array
    {
        /** @var Event $event */
        $event = $this->route('event');
        $validated = $this->safe();
        $poster = EventPublication::query()
            ->where('event_id', $event->id)
            ->first()?->draft['poster'] ?? null;

        if ($validated->boolean('remove_poster')) {
            $poster = null;
        }

        if ($poster !== null) {
            $poster['alt'] = $this->nullableString('poster_alt') ?: $poster['alt'];
            $poster['caption'] = $this->nullableString('poster_caption');
        }

        /** @var list<array{id: int|numeric-string, type?: bool|numeric-string, stipulation?: bool|numeric-string, titles?: bool|numeric-string, selected?: bool|numeric-string}> $matches */
        $matches = $this->validated('matches') ?? [];
        $matches = array_values(array_filter($matches, fn (array $match): bool => (bool) ($match['selected'] ?? false)));
        /** @var list<int|numeric-string> $results */
        $results = $this->validated('results') ?? [];

        return [
            'announcement' => $validated->string('announcement')->toString(),
            'summary' => $this->nullableString('summary'),
            'notice' => $this->nullableString('notice'),
            'address' => $validated->boolean('address'),
            'doors_at' => $this->nullableString('doors_at'),
            'doors_offset' => $this->nullableString('doors_offset'),
            'tickets' => $this->nullableString('tickets'),
            'poster' => $poster,
            'matches' => array_map(fn (array $match): array => [
                'id' => (int) $match['id'],
                'type' => (bool) ($match['type'] ?? false),
                'stipulation' => (bool) ($match['stipulation'] ?? false),
                'titles' => (bool) ($match['titles'] ?? false),
            ], $matches),
            'results' => array_map(fn (int|string $id): int => (int) $id, $results),
        ];
    }

    private function nullableString(string $key): ?string
    {
        $validated = $this->safe();

        return $validated->filled($key) ? $validated->string($key)->toString() : null;
    }
}
