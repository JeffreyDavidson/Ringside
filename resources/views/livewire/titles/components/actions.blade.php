<div class="flex flex-wrap gap-2">
    @can('debut', $title)
        <x-buttons.success wire:click="debut">{{ __('Debut') }}</x-buttons.success>
    @endcan

    @can('retire', $title)
        <x-buttons.danger wire:click="retire">{{ __('Retire') }}</x-buttons.danger>
    @endcan

    @can('unretire', $title)
        <x-buttons.success wire:click="unretire">{{ __('Unretire') }}</x-buttons.success>
    @endcan

    @can('pull', $title)
        <x-buttons.warning wire:click="deactivate">{{ __('Deactivate') }}</x-buttons.warning>
    @endcan

    @can('reinstate', $title)
        <x-buttons.success wire:click="reinstate">{{ __('Reinstate') }}</x-buttons.success>
    @endcan

    @can('restore', $title)
        <x-buttons.success wire:click="restore">{{ __('Restore') }}</x-buttons.success>
    @endcan
</div>
