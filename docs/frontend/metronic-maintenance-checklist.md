# Metronic Template Maintenance Checklist

## Quick Quality Assurance (Run before each deployment)

### 1. Color Validation (2 minutes)
```bash
node validate-metronic-colors.js
```
**Expected Result**: 100% color match

### 2. Visual Component Check (5 minutes)
- Open `visual-comparison-checklist.html` in browser
- Check all checkboxes while testing your live application
- Verify KeenIcons are displaying properly (not showing as squares)

### 3. Core Component Spot Check (3 minutes)
**Cards**: Navigate to any wrestler/manager show page
- [ ] Card shadows render correctly
- [ ] Border styling matches template
- [ ] General info cards display properly

**Buttons**: Check any form or action area
- [ ] Primary button color is #1B84FF
- [ ] Hover states work smoothly  
- [ ] Disabled states are properly styled

**Forms**: Open any create/edit modal
- [ ] Input focus states use primary blue border
- [ ] Validation errors show in red (#F8285A)
- [ ] Form spacing looks consistent

### 4. Layout Dimensions (2 minutes)
**Desktop** (screen > 1024px):
- [ ] Sidebar width: 280px
- [ ] Header height: 70px
- [ ] Sidebar collapses to 80px when toggled

**Mobile** (screen < 1024px):
- [ ] Header height: 60px
- [ ] Sidebar slides properly
- [ ] Navigation remains accessible

## Monthly Deep Validation

### Template Version Check
1. Check if Metronic has released updates
2. Compare against `metronic-components-used.md` list
3. Only update components you actually use

### Performance Review
- [ ] KeenIcons font loading efficiently
- [ ] No unused Metronic CSS/JS files
- [ ] Tailwind config includes only necessary components

### Component Consistency Audit
Run through all major pages:
- [ ] Dashboard
- [ ] Wrestler/Manager listings
- [ ] Create/Edit forms
- [ ] Show pages

Look for:
- Inconsistent spacing
- Color variations
- Different shadow styles
- Icon misalignments

## When Adding New Features

### Before Implementation
1. Check if component exists in your current system
2. Reference `metronic-components-used.md` for available patterns
3. Use existing component library before creating new ones

### After Implementation
1. Run `node validate-metronic-colors.js`
2. Test visual consistency with existing components
3. Update component inventory if new patterns added

## Emergency Fixes

### KeenIcons Not Loading
**Symptoms**: Icons appear as squares or missing
**Check**: `/resources/vendors/keenicons/styles.bundle.css`
**Fix**: Verify file exists and is imported in `app.js`

### Colors Look Wrong
**Symptoms**: Blues/greens/reds don't match template
**Check**: `tailwind.config.js` color definitions
**Fix**: Run validation script and compare with `metronic-components-used.md`

### Layout Broken
**Symptoms**: Sidebar too wide/narrow, header wrong height
**Check**: CSS custom properties in `resources/css/app.css`
**Fix**: Verify `--sidebar-default-width` and `--header-height` values

### Responsive Issues
**Symptoms**: Mobile layout looks wrong
**Check**: Tailwind responsive classes (lg:, md:)
**Fix**: Compare breakpoint usage with working template pages

## File Locations Reference

**Essential Files to Monitor**:
- `/tailwind.config.js` - Color definitions
- `/resources/css/app.css` - Layout variables
- `/resources/vendors/keenicons/` - Icon system
- `/resources/views/components/` - Component library

**Generated Files**:
- `metronic-components-used.md` - Component inventory
- `validate-metronic-colors.js` - Validation script  
- `visual-comparison-checklist.html` - Visual testing tool

## Success Metrics

**Perfect Implementation**:
- ✅ 100% color accuracy validation
- ✅ All visual checkboxes pass
- ✅ No console errors related to fonts/icons
- ✅ Consistent spacing across all pages
- ✅ Responsive behavior matches template

**Good Implementation**:
- ✅ 95%+ color accuracy
- ✅ Minor visual inconsistencies only
- ✅ Core functionality works perfectly

**Needs Attention**:
- ❌ Color validation below 95%
- ❌ Major visual inconsistencies
- ❌ Icons not loading
- ❌ Layout broken on mobile