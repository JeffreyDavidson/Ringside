<div class="flex flex-wrap gap-2">
    @can('employ', $wrestler)
        <x-buttons.success wire:click="employ">{{ __('Employ') }}</x-buttons.success>
    @endcan

    @can('release', $wrestler)
        <x-buttons.danger wire:click="release">{{ __('Release') }}</x-buttons.danger>
    @endcan

    @can('suspend', $wrestler)
        <x-buttons.warning wire:click="suspend">{{ __('Suspend') }}</x-buttons.warning>
    @endcan

    @can('reinstate', $wrestler)
        <x-buttons.success wire:click="reinstate">{{ __('Reinstate') }}</x-buttons.success>
    @endcan

    @can('injure', $wrestler)
        <x-buttons.warning wire:click="injure">{{ __('Injure') }}</x-buttons.warning>
    @endcan

    @can('clearFromInjury', $wrestler)
        <x-buttons.success wire:click="healFromInjury">{{ __('Heal') }}</x-buttons.success>
    @endcan

    @can('retire', $wrestler)
        <x-buttons.danger wire:click="retire">{{ __('Retire') }}</x-buttons.danger>
    @endcan

    @can('unretire', $wrestler)
        <x-buttons.success wire:click="unretire">{{ __('Unretire') }}</x-buttons.success>
    @endcan

    @can('restore', $wrestler)
        <x-buttons.success wire:click="restore">{{ __('Restore') }}</x-buttons.success>
    @endcan
</div>
