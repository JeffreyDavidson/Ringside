#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

// List of icons actually used in our codebase
const usedIcons = [
    'black-left',
    'black-left-line', 
    'black-right',
    'calendar',
    'copy',
    'copy-success',
    'cross',
    'cup',
    'dots-vertical',
    'element-11',
    'home',
    'home-3',
    'icon',
    'magnifier',
    'menu',
    'minus',
    'pencil',
    'people',
    'plus',
    'plus-squared',
    'right',
    'row-horizontal',
    'search-list',
    'setting-4',
    'trash'
];

console.log(`\n📊 Icon Usage Analysis:`);
console.log(`- Icons used in codebase: ${usedIcons.length}`);

// Check SVG files for total icon counts
const variants = ['filled', 'outline', 'solid', 'duotone'];
const iconCounts = {};

variants.forEach(variant => {
    const svgPath = `./resources/vendors/keenicons/fonts/keenicons-${variant}.svg`;
    if (fs.existsSync(svgPath)) {
        const content = fs.readFileSync(svgPath, 'utf8');
        const matches = content.match(/glyph-name="[^"]*"/g);
        iconCounts[variant] = matches ? matches.length : 0;
    }
});

console.log(`\n📈 Current Icon Counts by Variant:`);
Object.entries(iconCounts).forEach(([variant, count]) => {
    console.log(`- ${variant}: ${count} icons`);
});

// Calculate savings potential
const totalCurrentIcons = Object.values(iconCounts).reduce((sum, count) => sum + count, 0);
const potentialSavings = ((totalCurrentIcons - (usedIcons.length * variants.length)) / totalCurrentIcons * 100).toFixed(1);

console.log(`\n💾 Optimization Potential:`);
console.log(`- Total current icons: ${totalCurrentIcons}`);
console.log(`- Icons we actually use: ${usedIcons.length}`);
console.log(`- Potential size reduction: ~${potentialSavings}%`);

// Check current file sizes
console.log(`\n📁 Current File Sizes:`);
variants.forEach(variant => {
    const fontDir = './resources/vendors/keenicons/fonts';
    const files = [`keenicons-${variant}.svg`, `keenicons-${variant}.woff`, `keenicons-${variant}.ttf`];
    
    files.forEach(file => {
        const filePath = path.join(fontDir, file);
        if (fs.existsSync(filePath)) {
            const stats = fs.statSync(filePath);
            const sizeKB = (stats.size / 1024).toFixed(1);
            console.log(`- ${file}: ${sizeKB}KB`);
        }
    });
});

console.log(`\n🎯 Recommendations:`);
console.log(`1. Switch to tree-shakeable icon system (Heroicons, Lucide)`);
console.log(`2. Or create minimal KeenIcons subset with only ${usedIcons.length} used icons`);
console.log(`3. Consider removing unused variants (duotone has only ${iconCounts.duotone} icons)`);
console.log(`4. Biggest impact: Replace 1.6MB filled.svg + 1.3MB outline.svg`);

// Generate minimal icon list for reference
console.log(`\n📝 Used Icons List:`);
usedIcons.forEach(icon => console.log(`- ki-${icon}`));