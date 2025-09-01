@props([
    'title' => '',
    'class' => '',
])

<main {{ $attributes->merge(['class' => 'app-main flex-1 min-h-screen bg-gray-50 ' . $class]) }}>
    <div class="p-6 space-y-6 lg:p-8">
        {{-- Breadcrumb Section --}}
        @isset($breadcrumb)
            <div class="breadcrumb-section">
                {{ $breadcrumb }}
            </div>
        @endisset

        {{-- Page Header with Title --}}
        @if($title || isset($header))
            <header class="page-header">
                @if($title)
                    <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">
                        {{ $title }}
                    </h1>
                @endif
                
                @isset($header)
                    <div class="mt-2">
                        {{ $header }}
                    </div>
                @endisset
            </header>
        @endif

        {{-- Main Content Area --}}
        <div class="main-content">
            {{ $slot }}
        </div>
    </div>
</main>