<div class="flex flex-wrap gap-2">
    @can('employ', $tagTeam)
        <x-buttons.success wire:click="employ">{{ __('Employ') }}</x-buttons.success>
    @endcan

    @can('release', $tagTeam)
        <x-buttons.danger wire:click="release">{{ __('Release') }}</x-buttons.danger>
    @endcan

    @can('suspend', $tagTeam)
        <x-buttons.warning wire:click="suspend">{{ __('Suspend') }}</x-buttons.warning>
    @endcan

    @can('reinstate', $tagTeam)
        <x-buttons.success wire:click="reinstate">{{ __('Reinstate') }}</x-buttons.success>
    @endcan

    @can('retire', $tagTeam)
        <x-buttons.warning wire:click="retire">{{ __('Retire') }}</x-buttons.warning>
    @endcan

    @can('unretire', $tagTeam)
        <x-buttons.success wire:click="unretire">{{ __('Unretire') }}</x-buttons.success>
    @endcan

    @can('delete', $tagTeam)
        <x-buttons.danger wire:click="delete">{{ __('Delete') }}</x-buttons.danger>
    @endcan

    @can('restore', $tagTeam)
        <x-buttons.success wire:click="restore">{{ __('Restore') }}</x-buttons.success>
    @endcan
</div>
