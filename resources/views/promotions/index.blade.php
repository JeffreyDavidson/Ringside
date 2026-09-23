<x-layouts.app>
    <x-layouts.workspace-canvas class="flex flex-col gap-5 p-4 lg:p-7">
        <header class="border-ringside-line flex flex-wrap items-end justify-between gap-5 border-b pb-5">
            <div>
                <p class="text-ringside-signal text-xs font-bold tracking-[0.08em] uppercase">Platform directory</p>
                <h1 class="font-display text-ringside-ink mt-2 text-4xl leading-none tracking-tight uppercase">
                    Promotions
                </h1>
                <p class="text-ringside-muted mt-2 max-w-2xl text-sm">Every promotion on the Ringside platform.</p>
            </div>
            <div class="flex items-center gap-4">
                <p class="text-ringside-muted text-sm">
                    <span class="text-ringside-ink font-semibold">{{ $promotions->count() }}</span>
                    {{ str('promotion')->plural($promotions->count()) }}
                </p>
                @can('create', \App\Models\Promotions\Promotion::class)
                    <x-button
                        variant="ringside"
                        size="sm"
                        @click="$dispatch('openModal', { component: 'promotions.modals.form-modal' })"
                    >
                        {{ __('promotions.create') }}
                    </x-button>
                @endcan
            </div>
        </header>

        <div x-data x-on:promotion-saved.window="window.location.reload()">
            @if ($promotions->isEmpty())
                <div class="border-ringside-line bg-ringside-surface-panel flex min-h-64 flex-col items-center justify-center border border-dashed px-6 py-12 text-center">
                    <x-heroicon-o-building-office-2 class="text-ringside-muted size-8" />
                    <h2 class="font-display text-ringside-ink mt-4 text-2xl tracking-tight uppercase">
                        No promotions yet
                    </h2>
                    <p class="text-ringside-muted mt-2 max-w-md text-sm">
                        Promotions created on the platform will appear here.
                    </p>
                </div>
            @else
                <div class="border-ringside-line overflow-x-auto border">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead class="bg-ringside-surface-index text-ringside-muted border-ringside-line border-b text-xs tracking-[0.08em] uppercase">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Promotion</th>
                                <th class="px-4 py-3 font-semibold">Slug</th>
                                <th class="px-4 py-3 font-semibold">Members</th>
                                <th class="px-4 py-3 font-semibold">Created</th>
                                @can('update', $promotions->first())
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="divide-ringside-line divide-y">
                            @foreach ($promotions as $promotion)
                                <tr class="hover:bg-ringside-surface-hover">
                                    <td class="text-ringside-ink px-4 py-4 font-semibold">
                                        <a
                                            href="{{ route('promotions.show', $promotion) }}"
                                            class="hover:text-ringside-signal focus-visible:outline-ringside-white focus-visible:outline-2 focus-visible:outline-offset-4"
                                        >
                                            {{ $promotion->name }}
                                        </a>
                                    </td>
                                    <td class="text-ringside-muted px-4 py-4">{{ $promotion->slug }}</td>
                                    <td class="text-ringside-muted px-4 py-4">{{ $promotion->users_count }}</td>
                                    <td class="text-ringside-muted px-4 py-4">
                                        {{ $promotion->created_at?->toFormattedDateString() }}
                                    </td>
                                    @can('update', $promotion)
                                        <td class="px-4 py-4 text-right">
                                            <x-buttons.light
                                                size="sm"
                                                @click="$dispatch('openModal', { component: 'promotions.modals.form-modal', arguments: { modelId: {{ $promotion->id }} } })"
                                            >
                                                {{ __('promotions.edit') }}
                                            </x-buttons.light>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-layouts.workspace-canvas>
</x-layouts.app>
