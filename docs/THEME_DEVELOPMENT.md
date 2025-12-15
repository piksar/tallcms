# TallCMS Theme Development Guide

Essential guide for creating themes in TallCMS's block-based architecture.

## 🚨 Critical Limitations

**Admin Preview vs. Live Frontend:**
- ❌ **Colors**: Admin previews use default colors only
- ❌ **Spacing**: Tailwind classes don't work in admin previews  
- ❌ **Responsive**: Mobile/tablet breakpoints not accurate in preview
- ✅ **Live Frontend**: Always renders actual theme styling perfectly

**Solution**: Use hybrid styling (Tailwind + inline CSS) for block compatibility.

## 🏗️ Architecture Essentials

### Block-Only System
TallCMS uses pure block composition - no mixed HTML/block content.

**Available Blocks:**
- Hero Block (full-screen with CTAs)
- Content Block (title + rich text)
- Call-to-Action Block (conversion focused)
- Image Gallery Block (lightbox galleries)

### Fixed Navigation
All themes must account for fixed overlay navigation:
```blade
{{-- Navigation: 80px height, absolute positioned --}}
<nav class="absolute top-0 z-50 h-20 bg-white/95 backdrop-blur-md">

{{-- First section needs extra padding --}}
<section class="pt-32"> <!-- Avoid navigation overlap -->
```

## 🎨 Color System (Required)

### Theme Interface
All themes must implement `ThemeInterface`:

```php
namespace App\Themes;

use App\Contracts\ThemeInterface;

class MyTheme implements ThemeInterface
{
    public function getColorPalette(): array
    {
        return [
            'primary' => [
                50 => 'rgb(245, 250, 255)',
                600 => 'rgb(37, 99, 235)', // Main button color
                700 => 'rgb(29, 78, 216)', // Hover state
                // ... complete 50-950 scale required
            ],
            // secondary, success, warning, danger, neutral required
        ];
    }
    
    public function getButtonPresets(): array { /* Button color combinations */ }
    public function getTextPresets(): array { /* Text contrast levels */ }
    public function getPaddingPresets(): array { /* Spacing options */ }
}
```

### CSS Integration
**Frontend CSS must match PHP values exactly:**
```css
:root {
    --color-primary-600: rgb(37, 99, 235); /* Must match PHP array */
    --color-primary-700: rgb(29, 78, 216);
    /* Complete color scale required */
}
```

**Filament Admin:**
```php
// AdminPanelProvider.php
->colors([
    'primary' => MyTheme::getColorPalette()['primary'],
])
```

## 🧱 Block Development Requirements

### Hybrid Styling Pattern (Critical)
**Always include both Tailwind AND inline styles:**

```blade
<section class="py-16 bg-gray-50" 
         style="padding: 4rem 1.5rem; background-color: #f9fafb;">
    <h1 class="text-4xl font-bold" 
        style="font-size: clamp(2rem, 4vw, 3rem); font-weight: bold;">
        {{ $title }}
    </h1>
</section>
```

**Why hybrid?**
- Tailwind: Frontend responsiveness, hover states
- Inline CSS: Admin preview compatibility

### Use Theme Presets
```php
// In block views
use App\Support\ThemeColors;

$buttonPresets = ThemeColors::getStaticButtonPresets();
$textPresets = ThemeColors::getStaticTextPresets();
$paddingPresets = ThemeColors::getStaticPaddingPresets();

// Use preset values for both preview and frontend
$buttonColor = $buttonPresets['primary']['bg']; // rgb(37, 99, 235)
```

### Responsive Typography
```css
/* Use clamp() for fluid scaling */
font-size: clamp(1.5rem, 4vw, 3rem);    /* Headings */
font-size: clamp(1rem, 2vw, 1.125rem);  /* Buttons */
```

### Container Patterns
```blade
{{-- Full-width section --}}
<section class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16">
    {{-- Content container --}}
    <div class="max-w-4xl mx-auto">
        <!-- Content here -->
    </div>
</section>
```

## 📐 Grid Layout Support

Required CSS for TipTap grid compatibility:

```css
.grid-layout {
    display: grid;
    grid-template-columns: var(--cols);
    gap: 1.5rem;
    margin: 1rem 0;
}

.grid-layout-col {
    grid-column: var(--col-span);
}

/* Mobile breakpoint */
@media (max-width: 768px) {
    .grid-layout[data-from-breakpoint="lg"] {
        grid-template-columns: 1fr !important;
    }
}
```

## ✅ Block Development Checklist

**Required for every block:**
- [ ] **Hybrid styling** (Tailwind + inline CSS)
- [ ] **Theme color presets** (ThemeColors::getStaticButtonPresets())
- [ ] **Navigation spacing** (pt-32 for first sections)
- [ ] **Fluid typography** (clamp() functions)
- [ ] **Grid compatible** (works inside TipTap grids)
- [ ] **User disclaimers** about preview limitations

**Example disclaimer:**
```
⚠️ Preview uses default colors - live site shows actual theme colors
```

## 🎯 Theme File Structure

```
themes/my-theme/
├── views/
│   ├── layouts/app.blade.php          # Main layout
│   ├── cms/blocks/                    # Block overrides
│   │   ├── hero.blade.php
│   │   └── call-to-action.blade.php
│   └── components/menu.blade.php      # Navigation
├── css/theme.css                      # Theme styles
└── config/theme.php                   # Theme config
```

## 🚀 Quick Start

1. **Implement ThemeInterface** with complete color scales
2. **Match CSS variables** to PHP color arrays exactly  
3. **Use hybrid styling** in all block templates
4. **Include navigation spacing** for fixed overlay
5. **Test in both admin preview and live frontend**

## 📚 Key Files to Study

- `app/Support/ThemeColors.php` - Default theme implementation
- `resources/views/cms/blocks/call-to-action.blade.php` - Hybrid styling example
- `resources/css/app.css` - Color system and responsive patterns
- `docs/CUSTOM_BLOCK_STYLING.md` - Block creation guide

**Remember**: Admin previews are approximations - focus on perfect frontend experience with clear user communication about limitations.