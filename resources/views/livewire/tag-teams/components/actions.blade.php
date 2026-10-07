@use('App\Enums\Roster\RosterLifecycleAction')

<div class="flex flex-wrap gap-2">
    @if ($this->canPerform(RosterLifecycleAction::Employ))
        <x-buttons.success wire:click="employ" wire:loading.attr="disabled" wire:target="employ">
            {{ __('core.lifecycle_actions.employ') }}
        </x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Release))
        <x-buttons.danger
            wire:click="release"
            :wire:confirm="__($bookedEvents === '' ? 'core.lifecycle_confirmations.release' : 'core.lifecycle_confirmations.release_booked', ['name' => $tagTeam->name, 'events' => $bookedEvents])"
            wire:loading.attr="disabled"
            wire:target="release"
        >
            {{ __('core.lifecycle_actions.release') }}
        </x-buttons.danger>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Suspend))
        <x-buttons.warning
            wire:click="suspend"
            :wire:confirm="__('core.lifecycle_confirmations.suspend', ['name' => $tagTeam->name])"
            wire:loading.attr="disabled"
            wire:target="suspend"
        >
            {{ __('core.lifecycle_actions.suspend') }}
        </x-buttons.warning>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Reinstate))
        <x-buttons.success wire:click="reinstate" wire:loading.attr="disabled" wire:target="reinstate">
            {{ __('core.lifecycle_actions.reinstate') }}
        </x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Retire))
        <x-buttons.warning
            wire:click="retire"
            :wire:confirm="__($bookedEvents === '' ? 'core.lifecycle_confirmations.retire' : 'core.lifecycle_confirmations.retire_booked', ['name' => $tagTeam->name, 'events' => $bookedEvents])"
            wire:loading.attr="disabled"
            wire:target="retire"
        >
            {{ __('core.lifecycle_actions.retire') }}
        </x-buttons.warning>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Unretire))
        <x-buttons.success wire:click="unretire" wire:loading.attr="disabled" wire:target="unretire">
            {{ __('core.lifecycle_actions.unretire') }}
        </x-buttons.success>
    @endif
</div>
