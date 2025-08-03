#!/usr/bin/env node

// Icon Migration Plan: KeenIcons → Heroicons
const iconMapping = {
    // Navigation & UI
    'ki-black-left': 'chevron-left',
    'ki-black-left-line': 'chevron-left', 
    'ki-black-right': 'chevron-right',
    'ki-right': 'chevron-right',
    'ki-cross': 'x-mark',
    'ki-menu': 'bars-3',
    'ki-plus': 'plus',
    'ki-minus': 'minus',
    'ki-plus-squared': 'plus-circle',
    
    // Actions & Controls  
    'ki-copy': 'document-duplicate',
    'ki-copy-success': 'check-circle',
    'ki-dots-vertical': 'ellipsis-vertical',
    'ki-search-list': 'magnifying-glass',
    'ki-magnifier': 'magnifying-glass',
    'ki-pencil': 'pencil',
    'ki-trash': 'trash',
    'ki-setting-4': 'cog-6-tooth',
    
    // Content & Objects
    'ki-calendar': 'calendar-days',
    'ki-cup': 'trophy',
    'ki-home': 'home',
    'ki-home-3': 'building-office',
    'ki-people': 'users',
    'ki-icon': 'photo',
    
    // Layout & Grid
    'ki-element-11': 'squares-2x2',
    'ki-row-horizontal': 'bars-3-bottom-left'
};

console.log(`📋 KeenIcons → Heroicons Migration Plan\n`);

console.log(`🎯 Current State:`);
console.log(`- Using 25 unique KeenIcons`);
console.log(`- Total KeenIcons bundle: ~14MB`);
console.log(`- SVG files alone: ~4.9MB\n`);

console.log(`✨ Target State:`);
console.log(`- Switch to blade-heroicons package`);
console.log(`- Tree-shakeable: only load icons used`);
console.log(`- Expected size: <100KB total\n`);

console.log(`🔄 Icon Mappings:`);
Object.entries(iconMapping).forEach(([keen, hero]) => {
    console.log(`- ${keen} → ${hero}`);
});

console.log(`\n📦 Implementation Steps:`);
console.log(`1. Install blade-heroicons package`);
console.log(`2. Update all icon references in Blade templates`);
console.log(`3. Update CSS classes (ki-filled/ki-outline → heroicon)`);
console.log(`4. Remove KeenIcons assets and CSS`);
console.log(`5. Test all UI components`);

console.log(`\n🚀 Benefits:`);
console.log(`- ~13.9MB size reduction (99.3% smaller)`);
console.log(`- Better performance (tree-shakeable)`);
console.log(`- More consistent with Tailwind ecosystem`);
console.log(`- Better accessibility built-in`);
console.log(`- Actively maintained by Tailwind team`);

console.log(`\n⚠️  Manual Review Needed:`);
console.log(`- ki-icon: Generic icon, needs context-specific replacement`);
console.log(`- ki-cup: Mapped to trophy, verify appropriateness`);
console.log(`- ki-element-11: Grid icon, check if squares-2x2 fits use case`);

console.log(`\n📝 Commands to Run:`);
console.log(`composer require blade-ui-kit/blade-heroicons`);
console.log(`php artisan vendor:publish --tag=blade-heroicons`);

// Generate example transformations
console.log(`\n🔧 Example Transformations:`);
console.log(`Before: <i class="ki-filled ki-home"></i>`);
console.log(`After:  <x-heroicon-s-home class="size-5" />`);
console.log(``);
console.log(`Before: <i class="ki-outline ki-pencil text-gray-500"></i>`);
console.log(`After:  <x-heroicon-o-pencil class="size-5 text-gray-500" />`);