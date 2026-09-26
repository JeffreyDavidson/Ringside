@php($metadata = $this->metadata)

<div class="text-ringside-muted flex flex-wrap items-center gap-x-5 gap-y-2 text-sm" data-test="table-metadata">
    <span
        >All {{ Str::title($this->resourceName) }}
        <strong class="text-ringside-ink ms-1 font-semibold">{{ $metadata['total'] }}</strong></span>
    @foreach ($metadata['statuses'] as $status)
        <span
            >{{ $status['label'] }}
            <strong class="text-ringside-ink ms-1 font-semibold">{{ $status['count'] }}</strong></span>
    @endforeach
</div>
