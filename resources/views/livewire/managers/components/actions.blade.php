@use('App\Enums\Roster\RosterLifecycleAction')

<div class="flex flex-wrap gap-2">
    @if ($this->canPerform(RosterLifecycleAction::Employ))
        <x-buttons.success wire:click="employ">{{ __('core.lifecycle_actions.employ') }}</x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Release))
        <x-buttons.danger wire:click="release">{{ __('core.lifecycle_actions.release') }}</x-buttons.danger>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Suspend))
        <x-buttons.warning wire:click="suspend">{{ __('core.lifecycle_actions.suspend') }}</x-buttons.warning>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Reinstate))
        <x-buttons.success wire:click="reinstate">{{ __('core.lifecycle_actions.reinstate') }}</x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Injure))
        <x-buttons.warning wire:click="injure">{{ __('core.lifecycle_actions.injure') }}</x-buttons.warning>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::ClearFromInjury))
        <x-buttons.success wire:click="clearFromInjury">
            {{ __('core.lifecycle_actions.clear_from_injury') }}</x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Retire))
        <x-buttons.danger wire:click="retire">{{ __('core.lifecycle_actions.retire') }}</x-buttons.danger>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Unretire))
        <x-buttons.success wire:click="unretire">{{ __('core.lifecycle_actions.unretire') }}</x-buttons.success>
    @endif

    @if ($this->canPerform(RosterLifecycleAction::Restore))
        <x-buttons.success wire:click="restore">{{ __('core.lifecycle_actions.restore') }}</x-buttons.success>
    @endif
</div>
