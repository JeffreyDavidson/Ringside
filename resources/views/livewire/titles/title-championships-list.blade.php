<div class="card">
    <div class="py-4 card-body">
        <div class="table-responsive">
            <x-table class="table-row-dashed fs-6 gy-5 dataTable no-footer">
                <x-slot name="head">
                    <x-table.heading class="min-w-125px sorting_disabled">Champion</x-table.heading>
                    <x-table.heading class="min-w-70px sorting_disabled">Date of Reign</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @forelse ($titleChampionships as $titleChampionship)
                        <x-table.row :class="$loop->odd ? 'odd' : 'even'" wire:loading.class.delay="opacity-50" wire:key="row-{{ $titleChampionship->id }}">
                            <x-table.cell>
                                <a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('wrestlers.show', $titleChampionship->champion) }}">{{ $titleChampionship->champion->name }}</a>
                            </x-table.cell>

                            <x-table.cell>
                                @if ($titleChampionship->lost_at)
                                     {{ $titleChampionship->won_at->format('M d, Y') }} - {{ $titleChampionship->lost_at->format('M d, Y') }}
                                    ({{ $titleChampionship->won_at->diffInDays($titleChampionship->lost_at) }}) days
                                @else
                                    {{ $titleChampionship->won_at->format('M d, Y') }} - PRESENT
                                    ({{ $titleChampionship->won_at->diffInDays(now()) }}) days
                                @endif

                            </x-table.cell>

                        </x-table.row>
                    @empty
                        <x-table.row>
                            <x-table.cell colspan="6">
                                <div class="flex items-center justify-center space-x-2">
                                    <span class="py-8 text-xl font-medium text-cool-gray-400">No title championships found...</span>
                                </div>
                            </x-table.cell>
                        </x-table.row>
                    @endforelse
                </x-slot>
            </x-table>
        </div>

        <div class="row">
            <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start"></div>
            <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                {{ $titleChampionships->links() }}
            </div>
        </div>
    </div>
</div>
