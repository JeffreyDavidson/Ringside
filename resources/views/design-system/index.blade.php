<x-layouts.app>
    <div class="container max-w-6xl mx-auto py-8">
        <!-- Header -->
        <div class="mb-12">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Design System</h1>
            <p class="text-lg text-gray-600">A comprehensive collection of reusable UI components and design tokens for consistent user interfaces.</p>
        </div>

        <!-- Component Navigation -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <!-- Buttons Card -->
            <a href="{{ route('design-system.buttons') }}" class="group block bg-white rounded-lg border border-gray-200 hover:border-primary hover:shadow-md transition-all duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-primary-light rounded-lg flex items-center justify-center mr-4">
                            <x-ui.icon name="click" style="outline" size="lg" variant="primary" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-primary transition-colors">Buttons</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Interactive button components with multiple variants, sizes, and icon support.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">11 Variants</span>
                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">3 Sizes</span>
                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">Icon Support</span>
                    </div>
                    <div class="mt-4 flex items-center text-primary text-sm font-medium">
                        View Documentation
                        <x-ui.icon name="arrow-right" style="outline" size="sm" class="ml-1" />
                    </div>
                </div>
            </a>

            <!-- Icons Card -->
            <a href="{{ route('design-system.icons') }}" class="group block bg-white rounded-lg border border-gray-200 hover:border-primary hover:shadow-md transition-all duration-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-primary-light rounded-lg flex items-center justify-center mr-4">
                            <x-ui.icon name="star" style="outline" size="lg" variant="primary" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 group-hover:text-primary transition-colors">Icons</h3>
                    </div>
                    <p class="text-gray-600 mb-4">KeenIcons icon system with multiple styles, variants, and sizes.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">4 Styles</span>
                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">5 Variants</span>
                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">6 Sizes</span>
                    </div>
                    <div class="mt-4 flex items-center text-primary text-sm font-medium">
                        View Documentation
                        <x-ui.icon name="arrow-right" style="outline" size="sm" class="ml-1" />
                    </div>
                </div>
            </a>

            <!-- Future Components Card -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg border-dashed">
                <div class="p-6 text-center">
                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <x-ui.icon name="plus" style="outline" size="lg" class="text-gray-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">More Components</h3>
                    <p class="text-gray-500">Additional components will be added as they are developed.</p>
                </div>
            </div>
        </div>

        <!-- Design System Guidelines -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-8 mb-12">
            <h2 class="text-2xl font-semibold text-blue-900 mb-6">Design System Guidelines</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-medium text-blue-800 mb-3">Component Usage</h3>
                    <ul class="space-y-2 text-blue-700">
                        <li>• Use components consistently across the application</li>
                        <li>• Follow the documented API for each component</li>
                        <li>• Prefer component props over custom CSS classes</li>
                        <li>• Test components in different states and sizes</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-blue-800 mb-3">Accessibility</h3>
                    <ul class="space-y-2 text-blue-700">
                        <li>• Ensure adequate color contrast ratios</li>
                        <li>• Use semantic HTML elements when possible</li>
                        <li>• Provide alt text for icons when appropriate</li>
                        <li>• Test with keyboard navigation</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Component Architecture -->
        <div class="bg-white border border-gray-200 rounded-lg p-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-6">Component Architecture</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <x-ui.icon name="abstract-14" style="outline" size="xl" class="text-green-600" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Blade Components</h3>
                    <p class="text-gray-600 text-sm">Built with Laravel Blade component architecture for reusability and maintainability.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <x-ui.icon name="paintbucket" style="outline" size="xl" class="text-blue-600" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tailwind CSS</h3>
                    <p class="text-gray-600 text-sm">Styled with Tailwind CSS utility classes for consistent design and easy customization.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <x-ui.icon name="code" style="outline" size="xl" class="text-purple-600" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Type Safe</h3>
                    <p class="text-gray-600 text-sm">Components include proper prop validation and documentation for reliable usage.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>