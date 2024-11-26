@props(['component', 'tableName', 'primaryKey', 'isTailwind', 'isBootstrap', 'isBootstrap4', 'isBootstrap5'])
<div wire:key="{{ $tableName }}-wrapper">
    <div {{ $attributes->merge($this->getComponentWrapperAttributes()) }}
        @if ($this->hasRefresh()) wire:poll{{ $this->getRefreshOptions() }} @endif
        @if ($this->isFilterLayoutSlideDown()) wire:ignore.self @endif>
        {{ $slot }}
    </div>
</div>
