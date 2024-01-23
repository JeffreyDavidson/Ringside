<div
    x-data="{
            open: false,
            toggle() {
                if (this.open) {
                    return this.close()
                }

                this.$refs.button.focus()

                this.open = true
            },
            close(focusAfter) {
                if (! this.open) return

                this.open = false

                focusAfter && focusAfter.focus()
            }
        }"
    x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
    x-id="['dropdown-button']"
    class="relative"
>
    <button
        x-ref="button"
        x-on:click="toggle()"
        :aria-expanded="open"
        :aria-controls="$id('dropdown-button')"
        type="button" class="btn btn-light-primary me-3">
        <i class="ki-duotone ki-filter fs-2"><span class="path1"></span><span class="path2"></span></i> Filter
    </button>

    <div
        x-ref="panel"
        x-show="open"
        x-transition.origin.top.left
        x-on:click.outside="close($refs.button)"
        :id="$id('dropdown-button')"
        class="menu menu-sub menu-sub-dropdown w-300px w-md-325px show absolute"
    >
        <div class="px-7 py-5">
            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
        </div>

        <x-separator class="border-gray-200"/>

        <form
            x-on:submit="toggle()">
            <div class="px-7 py-5">
                {{ $slot }}
                <div class="d-flex justify-content-end">
                    <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6">
                        Reset
                    </button>
                    <button type="submit" class="btn btn-primary fw-semibold px-6" wire:click="apply">
                        Apply
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
