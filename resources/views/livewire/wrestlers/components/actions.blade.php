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
            :wire:confirm="__('core.lifecycle_confirmations.release', ['name' => $wrestler->name])"
            wire:loading.attr="disabled"
            wire:target="release"
        >
            {{ __('core.lifecycle_actions.release') }}
        </x-buttons.danger>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Suspend))
        <x-buttons.warning
            wire:click="suspend"
            :wire:confirm="__('core.lifecycle_confirmations.suspend', ['name' => $wrestler->name])"
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

    @if ($this->canPerform(RosterLifecycleAction::Injure))
        <x-buttons.warning
            wire:click="injure"
            :wire:confirm="__('core.lifecycle_confirmations.injure', ['name' => $wrestler->name])"
            wire:loading.attr="disabled"
            wire:target="injure"
        >
            {{ __('core.lifecycle_actions.injure') }}
        </x-buttons.warning>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::ClearFromInjury))
        <x-buttons.success wire:click="clearFromInjury" wire:loading.attr="disabled" wire:target="clearFromInjury">
            {{ __('core.lifecycle_actions.clear_from_injury') }}
        </x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Retire))
        <x-buttons.danger
            wire:click="retire"
            :wire:confirm="__('core.lifecycle_confirmations.retire', ['name' => $wrestler->name])"
            wire:loading.attr="disabled"
            wire:target="retire"
        >
            {{ __('core.lifecycle_actions.retire') }}
        </x-buttons.danger>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Unretire))
        <x-buttons.success wire:click="unretire" wire:loading.attr="disabled" wire:target="unretire">
            {{ __('core.lifecycle_actions.unretire') }}
        </x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Restore))
        <x-buttons.success wire:click="restore" wire:loading.attr="disabled" wire:target="restore">
            {{ __('core.lifecycle_actions.restore') }}
        </x-buttons.success>
    @endif
</div>
