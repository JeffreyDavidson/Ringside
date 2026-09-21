@php($metadata = $this->metadata)

<h1 class="text-xl leading-none font-medium text-gray-900">{{ Str::title($this->resourceName) }}</h1>

<div class="flex flex-wrap items-center gap-1.5 font-medium">
    <span class="text-md text-gray-600"> All {{ Str::title($this->resourceName) }}: </span>
    <span class="text-md gray-800 me-2 font-semibold"> {{ $metadata['total'] }} </span>
    @foreach ($metadata['statuses'] as $status)
        <span class="text-md text-gray-600"> {{ $status['label'] }} </span>
        <span class="text-md gray-800 font-semibold"> {{ $status['count'] }} </span>
    @endforeach
</div>
