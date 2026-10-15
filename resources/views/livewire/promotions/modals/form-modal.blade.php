<div>
    <x-modal class="!border-border !bg-surface-panel [&_h3]:!text-foreground [&_label]:!text-muted-foreground [&_input]:!border-outline [&_input]:!bg-surface [&_input]:!text-foreground !rounded-none !border">
        <x-modal.body>
            <div class="flex flex-col gap-4">
                <x-form.inputs.text
                    :label="__('promotions.name')"
                    appearance="ringside"
                    wire:model="form.name"
                    required
                    initial-focus
                />

                <x-form.inputs.text
                    :label="__('promotions.slug')"
                    appearance="ringside"
                    wire:model="form.slug"
                    required
                />

                <x-form.inputs.select
                    :label="__('promotions.timezone')"
                    :description="$timezoneHint"
                    wire:model="form.timezone"
                    :options="$timezones"
                />
            </div>
        </x-modal.body>

        <x-slot:footer>
            <div data-form-footer class="flex w-full justify-end gap-2">
                <x-buttons.light wire:click="$dispatch('closeModal')" wire:loading.attr="disabled" wire:target="save">
                    {{ __('core.form.cancel') }}
                </x-buttons.light>
                <x-buttons.primary
                    class="!bg-primary hover:!bg-primary-hover active:!bg-primary-pressed !rounded-none !text-white"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    {{ __('core.form.save') }}
                </x-buttons.primary>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
