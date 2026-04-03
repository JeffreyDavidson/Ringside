<div class="flex flex-wrap gap-2">
    @can('employ', $referee)
        <x-buttons.success wire:click="employ">{{ __('Employ') }}</x-buttons.success>
    @endcan

    @can('release', $referee)
        <x-buttons.danger wire:click="release">{{ __('Release') }}</x-buttons.danger>
    @endcan

    @can('suspend', $referee)
        <x-buttons.warning wire:click="suspend">{{ __('Suspend') }}</x-buttons.warning>
    @endcan

    @can('reinstate', $referee)
        <x-buttons.success wire:click="reinstate">{{ __('Reinstate') }}</x-buttons.success>
    @endcan

    @can('injure', $referee)
        <x-buttons.warning wire:click="injure">{{ __('Injure') }}</x-buttons.warning>
    @endcan

    @can('clearFromInjury', $referee)
        <x-buttons.success wire:click="healFromInjury">{{ __('Heal') }}</x-buttons.success>
    @endcan

    @can('retire', $referee)
        <x-buttons.danger wire:click="retire">{{ __('Retire') }}</x-buttons.danger>
    @endcan

    @can('unretire', $referee)
        <x-buttons.success wire:click="unretire">{{ __('Unretire') }}</x-buttons.success>
    @endcan

    @can('restore', $referee)
        <x-buttons.success wire:click="restore">{{ __('Restore') }}</x-buttons.success>
    @endcan
</div>
