<x-layouts.app>
    <x-container-fluid>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-referees.show.general-info :$referee />
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    <livewire:referees.tables.previous-matches :refereeId="$referee->id" />
                </div>
            </div>
        </div>
    </x-container-fluid>
</x-layouts.app>
