<div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <x-layouts.table-header :title="$title" />

    <form wire:submit="save" class="border-ringside-line divide-ringside-line border-y">
        <section
            class="grid gap-5 py-6 sm:grid-cols-[minmax(12rem,0.7fr)_minmax(0,1.3fr)] sm:gap-10"
            aria-labelledby="wrestler-profile-heading"
        >
            <div>
                <h2 id="wrestler-profile-heading" class="font-display text-ringside-ink text-lg leading-tight">
                    Profile
                </h2>
                <p class="text-ringside-muted mt-1 text-sm">The name and hometown shown on the roster.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-test="wrestler-profile-grid">
                <x-form.inputs.text appearance="ringside" label="{{ __('wrestlers.name') }}" wire:model="form.name" />
                <x-form.inputs.text
                    appearance="ringside"
                    label="{{ __('wrestlers.hometown') }}"
                    wire:model="form.hometown"
                />
            </div>
        </section>

        <section
            class="border-ringside-line grid gap-5 border-t py-6 sm:grid-cols-[minmax(12rem,0.7fr)_minmax(0,1.3fr)] sm:gap-10"
            aria-labelledby="wrestler-physical-heading"
        >
            <div>
                <h2 id="wrestler-physical-heading" class="font-display text-ringside-ink text-lg leading-tight">
                    Physical details
                </h2>
                <p class="text-ringside-muted mt-1 text-sm">
                    Measurements used in wrestler listings and match records.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3" data-test="wrestler-physical-details-grid">
                <x-form.inputs.text
                    appearance="ringside"
                    label="{{ __('wrestlers.feet') }}"
                    wire:model="form.height_feet"
                    inputmode="numeric"
                />
                <x-form.inputs.text
                    appearance="ringside"
                    label="{{ __('wrestlers.inches') }}"
                    wire:model="form.height_inches"
                    inputmode="numeric"
                />
                <x-form.inputs.text
                    appearance="ringside"
                    label="{{ __('wrestlers.weight') }}"
                    wire:model="form.weight"
                    inputmode="numeric"
                />
            </div>
        </section>

        <section
            class="border-ringside-line grid gap-5 border-t py-6 sm:grid-cols-[minmax(12rem,0.7fr)_minmax(0,1.3fr)] sm:gap-10"
            aria-labelledby="wrestler-career-heading"
        >
            <div>
                <h2 id="wrestler-career-heading" class="font-display text-ringside-ink text-lg leading-tight">
                    Career details
                </h2>
                <p class="text-ringside-muted mt-1 text-sm">
                    Optional details that help distinguish a wrestler's identity and tenure.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-test="wrestler-career-details-grid">
                <x-form.inputs.text
                    appearance="ringside"
                    label="{{ __('wrestlers.signature_move') }}"
                    wire:model="form.signature_move"
                />
                <x-form.inputs.date
                    appearance="ringside"
                    label="{{ __('employments.started_at') }}"
                    wire:model="form.employment_date"
                />
            </div>
        </section>

        <div class="border-ringside-line flex flex-col-reverse gap-3 border-t py-5 sm:flex-row sm:justify-end">
            <x-button variant="secondary" tag="a" href="{{ route('wrestlers.index') }}">Cancel</x-button>
            <x-button variant="ringside" type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save Wrestler</span>
                <span wire:loading wire:target="save">Saving…</span>
            </x-button>
        </div>
    </form>
</div>
