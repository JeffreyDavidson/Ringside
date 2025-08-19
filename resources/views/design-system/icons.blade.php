<x-layouts.app>
    <div class="container max-w-6xl mx-auto py-8">
        <!-- Header with Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="{{ route('design-system') }}" class="hover:text-primary">Design System</a>
                <x-ui.icon name="arrow-right" size="sm" class="text-gray-400" />
                <span class="text-gray-900">Icons</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Icon Component</h1>
            <p class="text-lg text-gray-600">KeenIcons icon system with multiple styles, color variants, and comprehensive sizing options.</p>
        </div>

        <!-- Icon Styles -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Styles</h2>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Icon Styles</h3>
                <div class="flex flex-wrap gap-8 items-center mb-4">
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="plus" style="outline" size="xl" />
                        <span class="text-sm text-gray-600">Outline</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="plus" style="filled" size="xl" />
                        <span class="text-sm text-gray-600">Filled</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="plus" style="solid" size="xl" />
                        <span class="text-sm text-gray-600">Solid</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="plus" style="duotone" size="xl" />
                        <span class="text-sm text-gray-600">Duotone</span>
                    </div>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.icon name="plus" style="outline" size="xl" /&gt;<br>
                        &lt;x-ui.icon name="plus" style="filled" size="xl" /&gt;<br>
                        &lt;x-ui.icon name="plus" style="solid" size="xl" /&gt;<br>
                        &lt;x-ui.icon name="plus" style="duotone" size="xl" /&gt;
                    </code>
                </div>
            </div>
        </section>

        <!-- Icon Variants -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Color Variants</h2>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Color Options</h3>
                <div class="flex flex-wrap gap-8 items-center mb-4">
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="check" style="outline" size="xl" />
                        <span class="text-sm text-gray-600">Default</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="check" style="outline" size="xl" variant="primary" />
                        <span class="text-sm text-gray-600">Primary</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="check" style="outline" size="xl" variant="success" />
                        <span class="text-sm text-gray-600">Success</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="check" style="outline" size="xl" variant="warning" />
                        <span class="text-sm text-gray-600">Warning</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="check" style="outline" size="xl" variant="danger" />
                        <span class="text-sm text-gray-600">Danger</span>
                    </div>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.icon name="check" style="outline" size="xl" /&gt;<br>
                        &lt;x-ui.icon name="check" style="outline" size="xl" variant="primary" /&gt;<br>
                        &lt;x-ui.icon name="check" style="outline" size="xl" variant="success" /&gt;<br>
                        &lt;x-ui.icon name="check" style="outline" size="xl" variant="warning" /&gt;<br>
                        &lt;x-ui.icon name="check" style="outline" size="xl" variant="danger" /&gt;
                    </code>
                </div>
            </div>
        </section>

        <!-- Icon Sizes -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Sizes</h2>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Size Options</h3>
                <div class="flex flex-wrap gap-8 items-center mb-4">
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="star" style="filled" size="sm" variant="primary" />
                        <span class="text-sm text-gray-600">Small (sm)</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="star" style="filled" size="md" variant="primary" />
                        <span class="text-sm text-gray-600">Medium (md)</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="star" style="filled" size="lg" variant="primary" />
                        <span class="text-sm text-gray-600">Large (lg)</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="star" style="filled" size="xl" variant="primary" />
                        <span class="text-sm text-gray-600">Extra Large (xl)</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="star" style="filled" size="2xl" variant="primary" />
                        <span class="text-sm text-gray-600">2X Large (2xl)</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="star" style="filled" size="3xl" variant="primary" />
                        <span class="text-sm text-gray-600">3X Large (3xl)</span>
                    </div>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.icon name="star" style="filled" size="sm" variant="primary" /&gt;<br>
                        &lt;x-ui.icon name="star" style="filled" size="md" variant="primary" /&gt;<br>
                        &lt;x-ui.icon name="star" style="filled" size="lg" variant="primary" /&gt;<br>
                        &lt;x-ui.icon name="star" style="filled" size="xl" variant="primary" /&gt;<br>
                        &lt;x-ui.icon name="star" style="filled" size="2xl" variant="primary" /&gt;<br>
                        &lt;x-ui.icon name="star" style="filled" size="3xl" variant="primary" /&gt;
                    </code>
                </div>
            </div>
        </section>

        <!-- Common Icons -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Common Icons</h2>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Frequently Used Icons</h3>
                <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-6 mb-6">
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="plus" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">plus</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="pencil" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">pencil</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="trash" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">trash</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="eye" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">eye</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="setting-2" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">gear</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="arrow-right" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">arrow-right</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="cross" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">cross</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="check" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">check</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="star" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">star</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="heart" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">heart</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="home" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">home</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-ui.icon name="magnifier" style="outline" size="xl" />
                        <span class="text-xs text-gray-600">search</span>
                    </div>
                </div>
            </div>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Icon Showcase - Different Styles</h3>
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <!-- Actions -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-4">Actions</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="plus" style="outline" size="lg" variant="success" />
                                    <span class="text-sm">Add / Create</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="pencil" style="outline" size="lg" variant="primary" />
                                    <span class="text-sm">Edit / Modify</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="trash" style="outline" size="lg" variant="danger" />
                                    <span class="text-sm">Delete / Remove</span>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-4">Navigation</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="arrow-right" style="outline" size="lg" />
                                    <span class="text-sm">Next / Forward</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="home" style="outline" size="lg" />
                                    <span class="text-sm">Home / Dashboard</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="magnifier" style="outline" size="lg" />
                                    <span class="text-sm">Search / Find</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-4">Status</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="check" style="filled" size="lg" variant="success" />
                                    <span class="text-sm">Success / Complete</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="cross" style="filled" size="lg" variant="danger" />
                                    <span class="text-sm">Error / Cancel</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="star" style="filled" size="lg" variant="warning" />
                                    <span class="text-sm">Favorite / Important</span>
                                </div>
                            </div>
                        </div>

                        <!-- Interface -->
                        <div>
                            <h4 class="font-medium text-gray-700 mb-4">Interface</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="eye" style="outline" size="lg" />
                                    <span class="text-sm">View / Show</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="setting-2" style="outline" size="lg" />
                                    <span class="text-sm">Settings / Config</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="heart" style="outline" size="lg" variant="danger" />
                                    <span class="text-sm">Like / Favorite</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Usage Guidelines -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Usage Guidelines</h2>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-4">Icon Component API</h3>
                <div class="space-y-4">
                    <div>
                        <strong class="text-blue-800">name:</strong>
                        <span class="text-blue-700">Icon name from KeenIcons (e.g., "plus", "pencil", "trash")</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">style:</strong>
                        <span class="text-blue-700">outline | filled | solid | duotone (default: outline)</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">variant:</strong>
                        <span class="text-blue-700">primary | success | warning | danger | null (default: null for current text color)</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">size:</strong>
                        <span class="text-blue-700">sm | md | lg | xl | 2xl | 3xl (default: md)</span>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="font-medium text-blue-900 mb-2">Best Practices:</h4>
                    <ul class="list-disc list-inside space-y-1 text-blue-800">
                        <li>Use <strong>outline</strong> style for most interface elements</li>
                        <li>Use <strong>filled</strong> style for active states or primary actions</li>
                        <li>Use <strong>solid</strong> style for high emphasis elements</li>
                        <li>Use <strong>duotone</strong> style for decorative or illustrative icons</li>
                        <li>Match icon variants with your content hierarchy (primary for main actions, etc.)</li>
                        <li>Ensure adequate color contrast for accessibility</li>
                        <li>Consider the context and use appropriate semantic colors (success for confirmations, danger for destructive actions)</li>
                        <li>Maintain consistency in icon style within the same interface section</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
