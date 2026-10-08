@props([
    'model',
    'name',
    'menuLabel',
    'showUrl',
    'formModal',
    'gateView' => false,
    'removable' => true,
])

@php
    $itemClass = 'hover:bg-ringside-surface-hover focus-visible:outline-ringside-ink flex min-h-11 items-center gap-3 px-3 text-sm focus-visible:outline-2';
@endphp

@if ($model->trashed())
    @can('restore', $model)
        <x-tables.row-actions-menu
            :label="__('core.row_actions.actions_for', ['name' => $name])"
            :menu-label="$menuLabel"
        >
            <li class="m-0 flex flex-col p-0">
                <button
                    type="button"
                    class="{{ $itemClass }} w-full text-start"
                    x-on:click="open = false"
                    wire:click="restore({{ $model->id }})"
                    wire:confirm="{{ __('core.lifecycle_confirmations.restore', ['name' => $name]) }}"
                >
                    <x-heroicon-m-arrow-uturn-left class="text-ringside-muted size-5" aria-hidden="true" />
                    <span>{{ __('core.row_actions.restore') }}</span>
                </button>
            </li>
        </x-tables.row-actions-menu>
    @endcan
@else
    <x-tables.row-actions-menu :label="__('core.row_actions.actions_for', ['name' => $name])" :menu-label="$menuLabel">
        @if (! $gateView || auth()->user()?->can('view', $model))
            <li class="m-0 flex flex-col p-0">
                <a class="{{ $itemClass }}" x-on:click="open = false" href="{{ $showUrl }}">
                    <x-heroicon-m-eye class="text-ringside-muted size-5" aria-hidden="true" />
                    <span>{{ __('core.row_actions.view') }}</span>
                </a>
            </li>
        @endif
        @can('update', $model)
            <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
            <li class="m-0 flex flex-col p-0">
                <button
                    type="button"
                    class="{{ $itemClass }} w-full text-start"
                    x-on:click="open = false"
                    wire:click="$dispatch('openModal', { component: '{{ $formModal }}', arguments: { modelId: {{ $model->id }} } })"
                >
                    <x-heroicon-m-pencil-square class="text-ringside-muted size-5" aria-hidden="true" />
                    <span>{{ __('core.row_actions.edit') }}</span>
                </button>
            </li>
        @endcan
        @if ($removable)
            @can('delete', $model)
                <li aria-hidden="true" class="border-ringside-line my-1 border-t"></li>
                <li class="m-0 flex flex-col p-0">
                    <button
                        type="button"
                        class="text-ringside-signal-soft {{ $itemClass }} w-full text-start"
                        x-on:click="open = false"
                        wire:click="delete({{ $model->id }})"
                        wire:confirm="{{ __('core.lifecycle_confirmations.remove', ['name' => $name]) }}"
                    >
                        <x-heroicon-m-trash class="size-5" aria-hidden="true" />
                        <span>{{ __('core.row_actions.remove') }}</span>
                    </button>
                </li>
            @endcan
        @endif
        {{ $slot }}
    </x-tables.row-actions-menu>
@endif
