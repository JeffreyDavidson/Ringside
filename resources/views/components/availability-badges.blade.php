@props(['injured' => false, 'suspended' => false])

@if ($injured || $suspended)
    <span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-1']) }}>
        @if ($injured)
            <x-badge color="warning" data-test="availability-injured">{{ __('core.availability.injured') }}</x-badge>
        @endif
        @if ($suspended)
            <x-badge color="danger" data-test="availability-suspended">{{ __('core.availability.suspended') }}</x-badge>
        @endif
    </span>
@endif
