<x-layouts.app>
    <x-container-fluid>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1">
                <div class="grid gap-5 lg:gap-7.5">
                    <x-managers.show.general-info :$manager />
                </div>
            </div>
            <div class="col-span-2">
                <div class="flex flex-col gap-5 lg:gap-7.5">
                    <livewire:managers.tables.previous-wrestlers :managerId="$manager->id" />
                    <livewire:managers.tables.previous-tag-teams :managerId="$manager->id" />
                    <livewire:managers.tables.previous-stables :managerId="$manager->id" />
                </div>
            </div>
        </div>
    </x-container-fluid>
</x-layouts.app>
