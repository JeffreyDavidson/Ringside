@props(['component', 'tableName', 'primaryKey', 'isTailwind', 'isBootstrap','isBootstrap4', 'isBootstrap5'])
<div class="testing" wire:key="{{ $tableName }}-wrapper" >
    <div {{ $attributes->merge($this->getComponentWrapperAttributes()) }}
        @if ($this->hasRefresh()) wire:poll{{ $this->getRefreshOptions() }} @endif
        @if ($this->isFilterLayoutSlideDown()) wire:ignore.self @endif>

        <x-card.header class="flex-wrap gap-2">
        @if ($this->debugIsEnabled())
            @include('livewire-tables::includes.debug')
        @endif
        @if ($this->offlineIndicatorIsEnabled())
            @include('livewire-tables::includes.offline')
        @endif

            {{ $slot }}
        </x-card.header>
    </div>
</div>
