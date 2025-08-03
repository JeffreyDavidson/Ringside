#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { glob } from 'glob';

// Icon mapping: KeenIcon name → Heroicon name
const iconMapping = {
    'black-left': 'chevron-left',
    'black-left-line': 'chevron-left', 
    'black-right': 'chevron-right',
    'right': 'chevron-right',
    'cross': 'x-mark',
    'menu': 'bars-3',
    'plus': 'plus',
    'minus': 'minus',
    'plus-squared': 'plus-circle',
    'copy': 'document-duplicate',
    'copy-success': 'check-circle',
    'dots-vertical': 'ellipsis-vertical',
    'search-list': 'magnifying-glass',
    'magnifier': 'magnifying-glass',
    'pencil': 'pencil',
    'trash': 'trash',
    'setting-4': 'cog-6-tooth',
    'calendar': 'calendar-days',
    'cup': 'trophy',
    'home': 'home',
    'home-3': 'building-office',
    'people': 'users',
    'icon': 'photo',
    'element-11': 'squares-2x2',
    'row-horizontal': 'bars-3-bottom-left'
};

// Convert ki-filled/ki-outline to heroicon component
function convertIconToHeroicon(match, variant, iconName, classes = '') {
    const heroIconName = iconMapping[iconName];
    if (!heroIconName) {
        console.warn(`⚠️  No mapping found for: ki-${iconName}`);
        return match; // Return original if no mapping
    }
    
    // Convert variant: filled -> s (solid), outline -> o (outline)
    const heroVariant = variant === 'filled' ? 's' : 'o';
    
    // Extract and clean classes
    const classMatch = classes.match(/class="([^"]*)"/);
    let classList = classMatch ? classMatch[1] : '';
    
    // Remove ki- classes and add appropriate size if none specified
    classList = classList
        .replace(/ki-[a-zA-Z0-9-]+/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    
    // Add default size if no size classes present
    if (!classList.match(/\b(size-|w-|h-|text-)/)) {
        classList = `size-5 ${classList}`.trim();
    }
    
    return `<x-heroicon-${heroVariant}-${heroIconName}${classList ? ` class="${classList}"` : ''} />`;
}

// Convert individual files
async function convertFile(filePath) {
    try {
        const content = fs.readFileSync(filePath, 'utf8');
        let hasChanges = false;
        
        // Pattern to match: <i class="ki-(filled|outline) ki-iconname other-classes"></i>
        const iconPattern = /<i\s+[^>]*class="[^"]*ki-(filled|outline)\s+ki-([a-zA-Z0-9-]+)[^"]*"[^>]*><\/i>/g;
        
        const newContent = content.replace(iconPattern, (match, variant, iconName) => {
            hasChanges = true;
            
            // Extract full class attribute
            const classMatch = match.match(/class="([^"]*)"/);
            const fullClass = classMatch ? classMatch[1] : '';
            
            return convertIconToHeroicon(match, variant, iconName, `class="${fullClass}"`);
        });
        
        if (hasChanges) {
            fs.writeFileSync(filePath, newContent, 'utf8');
            console.log(`✅ Converted icons in: ${filePath}`);
            return 1;
        }
        
        return 0;
    } catch (error) {
        console.error(`❌ Error processing ${filePath}:`, error.message);
        return 0;
    }
}

// Main conversion function
async function convertAllIcons() {
    console.log('🔄 Starting KeenIcons → Heroicons conversion...\n');
    
    // Find all Blade template files
    const bladeFiles = await glob('resources/views/**/*.blade.php');
    
    let totalConverted = 0;
    let filesChanged = 0;
    
    for (const file of bladeFiles) {
        const conversions = await convertFile(file);
        if (conversions > 0) {
            filesChanged++;
            totalConverted += conversions;
        }
    }
    
    console.log(`\n📊 Conversion Summary:`);
    console.log(`- Files processed: ${bladeFiles.length}`);
    console.log(`- Files changed: ${filesChanged}`);
    console.log(`- Icons converted: ${totalConverted}`);
    
    if (filesChanged > 0) {
        console.log(`\n✨ Icon conversion complete!`);
        console.log(`\nNext steps:`);
        console.log(`1. Review converted files for accuracy`);
        console.log(`2. Remove KeenIcons assets`);
        console.log(`3. Update CSS to remove ki- references`);
        console.log(`4. Test all UI components`);
    } else {
        console.log(`\n✨ No icons found to convert.`);
    }
}

// Run if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
    convertAllIcons().catch(console.error);
}

export { convertAllIcons, convertFile, iconMapping };