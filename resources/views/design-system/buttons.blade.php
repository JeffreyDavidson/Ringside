<x-layouts.app>
    <div class="container max-w-6xl mx-auto py-8">
        <!-- Header with Breadcrumb -->
        <div class="mb-8">
            <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="{{ route('design-system') }}" class="hover:text-primary">Design System</a>
                <x-ui.icon name="arrow-right" size="sm" class="text-gray-400" />
                <span class="text-gray-900">Buttons</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Button Component</h1>
            <p class="text-lg text-gray-600">Interactive button components with multiple variants, sizes, and comprehensive icon support.</p>
        </div>

        <!-- Button Variants -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Variants</h2>

            <!-- Primary Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Primary</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="primary">Primary Button</x-ui.button>
                    <x-ui.button variant="primary" iconLeft="plus">With Left Icon</x-ui.button>
                    <x-ui.button variant="primary" iconRight="arrow-right">With Right Icon</x-ui.button>
                    <x-ui.button variant="primary" iconLeft="plus" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="primary"&gt;Primary Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" iconLeft="plus"&gt;With Left Icon&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" iconRight="arrow-right"&gt;With Right Icon&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" iconLeft="plus" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Secondary Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Secondary</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="secondary">Secondary Button</x-ui.button>
                    <x-ui.button variant="secondary" iconLeft="pencil">Edit Item</x-ui.button>
                    <x-ui.button variant="secondary" iconRight="arrow-right">Continue</x-ui.button>
                    <x-ui.button variant="secondary" iconLeft="pencil" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="secondary"&gt;Secondary Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="secondary" iconLeft="pencil"&gt;Edit Item&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="secondary" iconRight="arrow-right"&gt;Continue&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="secondary" iconLeft="pencil" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Destructive Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Destructive</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="destructive">Delete Item</x-ui.button>
                    <x-ui.button variant="destructive" iconLeft="trash">Remove</x-ui.button>
                    <x-ui.button variant="destructive" iconRight="cross">Cancel</x-ui.button>
                    <x-ui.button variant="destructive" iconLeft="trash" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="destructive"&gt;Delete Item&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="destructive" iconLeft="trash"&gt;Remove&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="destructive" iconRight="cross"&gt;Cancel&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="destructive" iconLeft="trash" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Mono Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Mono</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="mono">Mono Button</x-ui.button>
                    <x-ui.button variant="mono" iconLeft="setting-2">Settings</x-ui.button>
                    <x-ui.button variant="mono" iconRight="arrow-right">Next</x-ui.button>
                    <x-ui.button variant="mono" iconLeft="setting-2" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="mono"&gt;Mono Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="mono" iconLeft="setting-2"&gt;Settings&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="mono" iconRight="arrow-right"&gt;Next&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="mono" iconLeft="setting-2" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>

            <!-- Outline Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Outline</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button style="outline" variant="primary">Outline Primary</x-ui.button>
                    <x-ui.button style="outline" variant="default">Outline Default</x-ui.button>
                    <x-ui.button style="outline" variant="destructive">Outline Destructive</x-ui.button>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button style="outline" variant="primary"&gt;Outline Primary&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button style="outline" variant="default"&gt;Outline Default&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button style="outline" variant="destructive"&gt;Outline Destructive&lt;/x-ui.button&gt;<br>
                    </code>
                </div>
            </div>

            <!-- Ghost Buttons -->
            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Ghost</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button style="ghost" variant="primary">Ghost Primary</x-ui.button>
                    <x-ui.button style="ghost" variant="default">Ghost Default</x-ui.button>
                    <x-ui.button style="ghost" variant="destructive">Ghost Destructive</x-ui.button>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button style="ghost" variant="primary"&gt;Ghost Primary&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button style="ghost" variant="default"&gt;Ghost Default&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button style="ghost" variant="destructive"&gt;Ghost Destructive&lt;/x-ui.button&gt;<br>
                    </code>
                </div>
            </div>
        </section>

        <!-- Button Style Combinations -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Style + Variant Combinations</h2>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Examples of All Combinations</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Filled Style -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-700 mb-4">Filled (Default)</h4>
                        <div class="space-y-3">
                            <x-ui.button variant="primary" iconLeft="plus">Add Item</x-ui.button>
                            <x-ui.button variant="secondary" iconLeft="pencil">Edit</x-ui.button>
                            <x-ui.button variant="destructive" iconLeft="trash">Delete</x-ui.button>
                            <x-ui.button variant="mono" iconLeft="setting-2">Settings</x-ui.button>
                        </div>
                    </div>

                    <!-- Outline Style -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-700 mb-4">Outline Style</h4>
                        <div class="space-y-3">
                            <x-ui.button style="outline" variant="primary" iconLeft="plus">Add Item</x-ui.button>
                            <x-ui.button style="outline" variant="secondary" iconLeft="pencil">Edit</x-ui.button>
                            <x-ui.button style="outline" variant="destructive" iconLeft="trash">Delete</x-ui.button>
                            <x-ui.button style="outline" variant="mono" iconLeft="setting-2">Settings</x-ui.button>
                        </div>
                    </div>

                    <!-- Ghost Style -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-700 mb-4">Ghost Style</h4>
                        <div class="space-y-3">
                            <x-ui.button style="ghost" variant="primary" iconLeft="plus">Add Item</x-ui.button>
                            <x-ui.button style="ghost" variant="secondary" iconLeft="pencil">Edit</x-ui.button>
                            <x-ui.button style="ghost" variant="destructive" iconLeft="trash">Delete</x-ui.button>
                            <x-ui.button style="ghost" variant="mono" iconLeft="setting-2">Settings</x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Button Sizes -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Sizes</h2>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Text Buttons</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="primary" size="sm">Small Button</x-ui.button>
                    <x-ui.button variant="primary">Default Button</x-ui.button>
                    <x-ui.button variant="primary" size="lg">Large Button</x-ui.button>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="primary" size="sm"&gt;Small Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary"&gt;Default Button&lt;/x-ui.button&gt;<br>
                        &lt;x-ui.button variant="primary" size="lg"&gt;Large Button&lt;/x-ui.button&gt;
                    </code>
                </div>
            </div>

            <div class="mb-12">
                <h3 class="text-xl font-medium text-gray-800 mb-6">Icon-Only Buttons</h3>
                <div class="flex flex-wrap gap-4 items-center mb-4">
                    <x-ui.button variant="primary" size="sm" iconLeft="plus" iconOnly="true" />
                    <x-ui.button variant="primary" iconLeft="plus" iconOnly="true" />
                    <x-ui.button variant="primary" size="lg" iconLeft="plus" iconOnly="true" />
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <code class="text-sm text-gray-800">
                        &lt;x-ui.button variant="primary" size="sm" iconLeft="plus" iconOnly="true" /&gt;<br>
                        &lt;x-ui.button variant="primary" iconLeft="plus" iconOnly="true" /&gt;<br>
                        &lt;x-ui.button variant="primary" size="lg" iconLeft="plus" iconOnly="true" /&gt;
                    </code>
                </div>
            </div>
        </section>

        <!-- Usage Guidelines -->
        <section class="mb-16">
            <h2 class="text-2xl font-semibold text-gray-900 mb-8">Usage Guidelines</h2>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-4">Button Component API</h3>
                <div class="space-y-4">
                    <div>
                        <strong class="text-blue-800">style:</strong>
                        <span class="text-blue-700">filled | outline | ghost (default: filled)</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">variant:</strong>
                        <span class="text-blue-700">primary | secondary | destructive | mono (default: primary)</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">size:</strong>
                        <span class="text-blue-700">sm | (default) | lg</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">iconLeft:</strong>
                        <span class="text-blue-700">KeenIcons name (e.g., "plus", "pencil")</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">iconRight:</strong>
                        <span class="text-blue-700">KeenIcons name (e.g., "arrow-right")</span>
                    </div>
                    <div>
                        <strong class="text-blue-800">iconOnly:</strong>
                        <span class="text-blue-700">boolean - creates square icon-only button</span>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="font-medium text-blue-900 mb-2">Usage Examples:</h4>
                    <div class="bg-white p-4 rounded border mb-4">
                        <code class="text-sm text-gray-800">
                            &lt;x-ui.button variant="primary"&gt;Default filled primary&lt;/x-ui.button&gt;<br>
                            &lt;x-ui.button style="outline" variant="destructive"&gt;Outline destructive&lt;/x-ui.button&gt;<br>
                            &lt;x-ui.button style="ghost" variant="secondary"&gt;Ghost secondary&lt;/x-ui.button&gt;
                        </code>
                    </div>

                    <h4 class="font-medium text-blue-900 mb-2">Hover States:</h4>
                    <ul class="list-disc list-inside space-y-1 text-blue-800 mb-4">
                        <li><strong>Filled buttons</strong> darken on hover for depth</li>
                        <li><strong>Outline primary</strong> fills with primary color and foreground text on hover</li>
                        <li><strong>Outline destructive</strong> shows subtle destructive background tint on hover</li>
                        <li><strong>Outline secondary/mono</strong> use accent colors with proper contrast on hover</li>
                        <li><strong>Ghost buttons</strong> show subtle background tints on hover</li>
                    </ul>

                    <h4 class="font-medium text-blue-900 mb-2">Best Practices:</h4>
                    <ul class="list-disc list-inside space-y-1 text-blue-800">
                        <li>Use <strong>filled</strong> style for primary actions and call-to-action buttons</li>
                        <li>Use <strong>outline</strong> style for secondary or alternative actions</li>
                        <li>Use <strong>ghost</strong> style for subtle interactions and tertiary actions</li>
                        <li>Match variants to action importance: <strong>primary</strong> for main actions, <strong>destructive</strong> for dangerous actions</li>
                        <li>Icon-only buttons should have tooltips for accessibility</li>
                        <li>Test hover states to ensure adequate contrast and visual feedback</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
