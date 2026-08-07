@props([
    'enum' => [],
])

<h1 class="text-xl leading-none font-medium text-gray-900">{{ Str::title($this->resourceName) }}</h1>

<div class="flex flex-wrap items-center gap-1.5 font-medium">
    <span class="text-md text-gray-600"> All {{ Str::title($this->resourceName) }}: </span>
    <span class="text-md gray-800 me-2 font-semibold"> {{ $this->builder()->count() }} </span>
    @if ($enum)
        @foreach ($enum::cases() as $status)
            <span class="text-md text-gray-600"> {{ $status->label() }} </span>
            <span class="text-md gray-800 font-semibold">
                {{ $this->builder()->where('status', $status->value)->count() }}
            </span>
        @endforeach
    @endif
</div>
