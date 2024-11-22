<x-container-fixed>
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-gray-900">
                Tag Teams
            </h1>
            <div class="flex items-center flex-wrap gap-1.5 font-medium">
                <span class="text-md text-gray-600">
                    All Tag Teams:
                </span>
                <span class="text-md gray-800 font-semibold me-2">
                    {{ $this->builder()->count() }}
                </span>
                @foreach (\App\Enums\TagTeamStatus::cases() as $status)
                    <span class="text-md text-gray-600">
                        {{ $status->label() }}
                    </span>
                    <span class="text-md gray-800 font-semibold">
                        {{ $this->builder()->where('status', $status->value)->count() }}
                    </span>
                @endforeach
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <x-buttons.primary size="sm">Add Tag Team</x-buttons.primary>
        </div>
    </div>
</x-container-fixed>
