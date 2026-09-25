@props(['title', 'description', 'icon' => 'heroicon-o-inbox', 'descriptionClass' => ''])

<div {{ $attributes->class(['border-ringside-line flex flex-col items-center gap-3 border-t px-6 py-14 text-center']) }}>
    <x-dynamic-component :component="$icon" class="text-ringside-muted mb-1 size-8" aria-hidden="true" />
    <h2 class="text-ringside-ink m-0 text-base font-semibold">{{ $title }}</h2>
    <p @class(['text-ringside-muted m-0 max-w-sm text-sm leading-6', $descriptionClass])>{{ $description }}</p>
    {{ $slot }}
</div>
