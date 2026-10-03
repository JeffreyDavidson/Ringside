{{--
    The form is captured when the modal opens. Clear has nothing to do while the form is unchanged, and asks before it
    discards anything that was typed, auto-filled or kept after a failed save.
--}}
<div
    data-form-footer
    x-data="{ initialForm: JSON.stringify($wire.form) }"
    class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"
>
    <div class="flex">
        @env('local')
            @empty($this->modelForm->formModel)
                <x-button
                    variant="secondary"
                    class="!border-ringside-line !text-ringside-muted hover:!bg-ringside-surface hover:!text-ringside-ink !h-9 !rounded-none !border !bg-transparent"
                    wire:click="fillDummyFields"
                    wire:loading.attr="disabled"
                    wire:target="save, clear, fillDummyFields"
                >
                    {{ __('core.form.auto_fill') }}
                </x-button>
            @endempty
        @endenv
    </div>
    <div class="flex justify-end gap-2">
        <x-button
            variant="secondary"
            class="!border-ringside-line !text-ringside-muted hover:!bg-ringside-surface hover:!text-ringside-ink !h-9 !rounded-none !border !bg-transparent"
            :data-confirm-message="__('core.form.confirm_clear')"
            x-on:click="
                if (JSON.stringify($wire.form) !== initialForm && confirm($el.dataset.confirmMessage)) $wire.clear();
            "
            wire:loading.attr="disabled"
            wire:target="save, clear, fillDummyFields"
        >
            {{ __('core.form.clear') }}
        </x-button>
        <x-button
            variant="ringside"
            size="md"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save, clear, fillDummyFields"
        >
            {{ __('core.form.save') }}
        </x-button>
    </div>
</div>
