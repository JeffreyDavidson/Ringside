<div>
    @isset($jsPath)
        <script>{!! file_get_contents($jsPath) !!}</script>
    @endisset
    @isset($cssPath)
        <style>{!! file_get_contents($cssPath) !!}</style>
    @endisset

    <div
        wire:loading.delay
        wire:target="openModal"
        class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-black/70 p-4 sm:p-6"
        role="status"
        aria-busy="true"
        data-test="modal-loading-placeholder"
    >
        <span class="sr-only">{{ __('core.loading_form') }}</span>

        <div class="border-ringside-line bg-ringside-surface-panel w-full max-w-[800px] border shadow-2xl motion-safe:animate-pulse" aria-hidden="true">
            <header class="border-ringside-line border-b px-5 py-4 lg:px-6">
                <div class="bg-ringside-line/60 h-6 w-40 max-w-full"></div>
            </header>

            <div class="grid gap-4 px-5 py-5 lg:grid-cols-2 lg:px-6">
                @foreach (range(1, 4) as $field)
                    <div class="flex flex-col gap-2">
                        <div class="bg-ringside-line/50 h-3 w-24"></div>
                        <div class="border-ringside-line bg-ringside-surface h-11 border"></div>
                    </div>
                @endforeach
            </div>

            <footer class="border-ringside-line flex justify-end gap-3 border-t px-5 py-4 lg:px-6">
                <div class="border-ringside-line bg-ringside-surface h-10 w-24 border"></div>
                <div class="bg-ringside-line/60 h-10 w-24"></div>
            </footer>
        </div>
    </div>

    <div
        x-data="LivewireUIModal()"
        x-on:close.stop="setShowPropertyTo(false)"
        x-on:keydown.escape.window="show && closeModalOnEscape()"
        x-show="show"
        class="fixed inset-0 z-[60] overflow-y-auto"
        style="display: none;"
    >
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
            <div
                x-show="show"
                x-on:click="closeModalOnClickAway()"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/70 transition-opacity"
                aria-hidden="true"
            ></div>

            <div
                x-show="show && showActiveComponent"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-bind:class="modalWidth"
                class="relative inline-flex w-full align-middle text-left"
                id="modal-container"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
                x-trap.noscroll.inert="show && showActiveComponent"
            >
                @forelse($components as $id => $component)
                    <div x-show.immediate="activeComponent == '{{ $id }}'" x-ref="{{ $id }}" wire:key="{{ $id }}" class="w-full">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</div>
