@use('App\Enums\Stables\StableLifecycleAction')

<div class="flex flex-wrap gap-2">
    @if ($this->canPerform(StableLifecycleAction::Establish))
        <x-buttons.success wire:click="establish">{{ __('core.lifecycle_actions.establish') }}</x-buttons.success>
    @endif

    @if ($this->canPerform(StableLifecycleAction::Disband))
        <x-buttons.danger wire:click="disband">{{ __('core.lifecycle_actions.disband') }}</x-buttons.danger>
    @endif

    @if ($this->canPerform(StableLifecycleAction::Retire))
        <x-buttons.warning wire:click="retire">{{ __('core.lifecycle_actions.retire') }}</x-buttons.warning>
    @endif

    @if ($this->canPerform(StableLifecycleAction::Unretire))
        <x-buttons.success wire:click="unretire">{{ __('core.lifecycle_actions.unretire') }}</x-buttons.success>
    @endif
</div>
