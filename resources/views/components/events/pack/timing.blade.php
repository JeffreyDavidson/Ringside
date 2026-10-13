@props(['prefix', 'entry' => []])
<div class="pack-timings">
    <x-events.pack.field
        :name="$prefix.'[planned_at]'"
        :label="__('event-pack.planned')"
        type="datetime-local"
        :value="$entry['planned_at'] ?? null"
    />
    <x-events.pack.field
        :name="$prefix.'[revised_at]'"
        :label="__('event-pack.revised')"
        type="datetime-local"
        :value="$entry['revised_at'] ?? null"
    />
    <x-events.pack.field
        :name="$prefix.'[minutes]'"
        :label="__('event-pack.minutes')"
        type="number"
        :value="$entry['minutes'] ?? null"
    />
</div>
<x-events.pack.field
    :name="$prefix.'[notes]'"
    :label="__('event-pack.row_notes')"
    type="textarea"
    :value="$entry['notes'] ?? null"
    :max="2000"
/>
