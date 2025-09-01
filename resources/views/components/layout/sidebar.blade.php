@props([
    'collapsed' => false,
    'class' => '',
])

<aside 
    x-data="{ collapsed: @js($collapsed), mobileOpen: false }"
    {{ $attributes->merge(['class' => 'app-sidebar bg-white shadow-lg border-r border-gray-200 transition-all duration-300 ' . $class]) }}
    :class="{ 'w-64': !collapsed, 'w-16': collapsed }"
>
    {{-- Desktop Sidebar --}}
    <div class="hidden md:flex flex-col h-full">
        {{-- Sidebar Header with Toggle --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <div x-show="!collapsed" class="text-lg font-semibold text-gray-900">
                Menu
            </div>
            <button 
                @click="collapsed = !collapsed"
                class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        {{-- Profile Area --}}
        @isset($profile)
            <div class="p-4 border-b border-gray-200" x-show="!collapsed" x-transition>
                {{ $profile }}
            </div>
        @endisset

        {{-- Menu Items --}}
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            @isset($menu)
                {{ $menu }}
            @else
                <div class="text-sm text-gray-500 text-center" x-show="!collapsed">
                    No menu items
                </div>
            @endisset
        </nav>

        {{-- Sidebar Footer --}}
        @isset($footer)
            <div class="p-4 border-t border-gray-200">
                {{ $footer }}
            </div>
        @endisset
    </div>

    {{-- Mobile Sidebar Overlay --}}
    <div x-show="mobileOpen" x-transition.opacity class="fixed inset-0 z-50 md:hidden">
        {{-- Backdrop --}}
        <div 
            class="fixed inset-0 bg-black bg-opacity-50"
            @click="mobileOpen = false"
        ></div>
        
        {{-- Mobile Sidebar Panel --}}
        <div 
            class="fixed left-0 top-0 h-full w-64 bg-white shadow-xl transform transition-transform"
            x-show="mobileOpen"
            x-transition:enter="transition-transform duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
        >
            {{-- Mobile Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="text-lg font-semibold text-gray-900">Menu</div>
                <button 
                    @click="mobileOpen = false"
                    class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Mobile Profile --}}
            @isset($profile)
                <div class="p-4 border-b border-gray-200">
                    {{ $profile }}
                </div>
            @endisset

            {{-- Mobile Menu --}}
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                @isset($menu)
                    {{ $menu }}
                @else
                    <div class="text-sm text-gray-500 text-center">
                        No menu items
                    </div>
                @endisset
            </nav>

            {{-- Mobile Footer --}}
            @isset($footer)
                <div class="p-4 border-t border-gray-200">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>

    {{-- Mobile Toggle Script (to be triggered from header) --}}
    <script>
        window.toggleMobileSidebar = function() {
            Alpine.store('sidebar').mobileOpen = !Alpine.store('sidebar').mobileOpen;
        }
        
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', {
                mobileOpen: false
            });
        });
    </script>
</aside>