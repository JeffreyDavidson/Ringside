@props(['title'])

<form {{ $attributes->class(['flex flex-col gap-6'])->merge(['method' => 'post']) }}>
    @csrf
    <header class="border-ringside-line border-b pb-6">
        <h1 class="font-display text-4xl leading-tight tracking-tight uppercase sm:text-5xl">{{ $title }}</h1>
        @isset($intro)
            <div class="text-ringside-muted mt-3 text-base leading-relaxed">{{ $intro }}</div>
        @endisset
    </header>
    @if (is_string(session('status')) && session('status') !== '')
        <p role="status" class="border-ringside-outline bg-ringside-surface-panel border p-4 text-base leading-relaxed">
            {{ session('status') }}
        </p>
    @endif
    {{ $slot }}
</form>
