@props([
    'class' => '',
])

<header {{ $attributes->merge(['class' => 'app-header flex items-center justify-between bg-white shadow-sm border-b border-gray-200 px-6 py-4 lg:px-8 ' . $class]) }}>
    {{-- Logo Section --}}
    @isset($logo)
        <div class="flex items-center">
            {{ $logo }}
        </div>
    @else
        <div class="flex items-center">
            <div class="text-xl font-bold text-gray-900">Logo</div>
        </div>
    @endisset

    {{-- Navigation Section --}}
    @isset($navigation)
        <nav class="hidden md:flex items-center space-x-6 flex-1 justify-center">
            {{ $navigation }}
        </nav>
    @else
        <nav class="hidden md:flex items-center space-x-6 flex-1 justify-center">
            {{-- Default navigation will be empty --}}
        </nav>
    @endisset

    {{-- Actions Section --}}
    @isset($actions)
        <div class="flex items-center space-x-4">
            {{ $actions }}
        </div>
    @else
        <div class="flex items-center space-x-4">
            {{-- Default empty actions area --}}
        </div>
    @endisset

    {{-- Mobile Menu Button --}}
    <div class="md:hidden">
        <button type="button" class="text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-2">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
</header>