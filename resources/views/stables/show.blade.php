<x-layouts.app>
    <x-container-fixed>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-stables.show.general-info :$stable />
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    {{-- <livewire:stables.show.previous-wrestlers-list :stable="$stable" />
                    <livewire:stables.show.previous-tag-teams-list :stable="$stable" />
                    <livewire:stables.show.previous-managers-list :stable="$stable" /> --}}
                </div>
            </div>
        </div>
    </x-container-fixed>
</x-layouts.app>
