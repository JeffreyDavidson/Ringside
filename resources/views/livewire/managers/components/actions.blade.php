<div class="flex flex-wrap gap-2">
    @can('employ', $manager)
        <x-buttons.success wire:click="employ">{{ __('Employ') }}</x-buttons.success>
    @endcan

    @can('release', $manager)
        <x-buttons.danger wire:click="release">{{ __('Release') }}</x-buttons.danger>
    @endcan

    @can('suspend', $manager)
        <x-buttons.warning wire:click="suspend">{{ __('Suspend') }}</x-buttons.warning>
    @endcan

    @can('reinstate', $manager)
        <x-buttons.success wire:click="reinstate">{{ __('Reinstate') }}</x-buttons.success>
    @endcan

    @can('injure', $manager)
        <x-buttons.warning wire:click="injure">{{ __('Injure') }}</x-buttons.warning>
    @endcan

    @can('clearFromInjury', $manager)
        <x-buttons.success wire:click="healFromInjury">{{ __('Heal') }}</x-buttons.success>
    @endcan

    @can('retire', $manager)
        <x-buttons.danger wire:click="retire">{{ __('Retire') }}</x-buttons.danger>
    @endcan

    @can('unretire', $manager)
        <x-buttons.success wire:click="unretire">{{ __('Unretire') }}</x-buttons.success>
    @endcan

    @can('restore', $manager)
        <x-buttons.success wire:click="restore">{{ __('Restore') }}</x-buttons.success>
    @endcan
</div>
