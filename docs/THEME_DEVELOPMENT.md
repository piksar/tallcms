# TallCMS Theme Development Guide

Essential guide for creating themes in TallCMS's unified CSS architecture.

## ✅ Unified CSS Architecture

**Admin Preview & Frontend Consistency:**
- ✅ **Colors**: Both admin and frontend use identical CSS from blocks.css
- ✅ **Spacing**: Tailwind classes work consistently in both contexts  
- ✅ **Responsive**: Accurate breakpoints in admin preview and frontend
- ✅ **Theme Integration**: CSS custom properties ensure color consistency

**Solution**: Custom Filament theme with shared blocks.css provides perfect consistency.

## 🎯 CSS Architecture Overview

### File Structure
```
resources/css/
├── app.css                           # Frontend entry point
├── blocks.css                        # Shared block styles for both admin and frontend
└── filament/admin/
    └── theme.css                     # Admin entry point (imports shared blocks.css)
```

### Build Process
- **Frontend**: `app.css` imports shared `blocks.css` → compiles to `public/build/assets/app-*.css`
- **Admin**: `theme.css` imports shared `blocks.css` → compiles to `public/build/assets/theme-*.css`
- **Vite**: Automatically processes both entry points with Tailwind purging
- **Perfect Consistency**: Both builds use the exact same `blocks.css` file

### CSS Custom Properties Integration
```php
// In block templates (content-block.blade.php example)
@php
    $textPreset = theme_text_presets()['primary'];
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'],
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb')
    ])->join('; ') . ';';
@endphp

<article style="{{ $customProperties }}">
    <div class="content-block">
        <!-- CSS from blocks.css uses var(--block-heading-color) etc. -->
    </div>
</article>
```

### Why This Works
1. **Custom Filament Theme**: Imports blocks.css with widened `@source` paths
2. **Shared CSS**: Both admin and frontend use identical block styling
3. **Scoped Properties**: Each block instance sets its own CSS custom properties
4. **Tailwind Integration**: Classes are properly purged for both builds
5. **No Conflicts**: Inline custom properties prevent block color bleeding

## 🏗️ Architecture Essentials

### Block-Only System
TallCMS uses pure block composition - no mixed HTML/block content.

**Available Blocks:**
- Content Block (articles/blog with subtitle, content width, heading levels)
- Hero Block (full-screen with CTAs)
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

### CSS Custom Properties Pattern (Required)
**Use CSS custom properties for theme integration:**

```blade
@php
    $textPreset = theme_text_presets()['primary'];
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'],
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb')
    ])->join('; ') . ';';
@endphp

<section class="py-16" style="{{ $customProperties }}">
    <div class="content-block">
        <h1 class="text-4xl font-bold">{{ $title }}</h1>
        <!-- CSS from blocks.css handles styling via var(--block-*) -->
    </div>
</section>
```

**Why CSS custom properties?**
- Perfect admin/frontend consistency
- No inline style bloat 
- Proper theme integration
- No conflicts between blocks

### Use Theme Presets
```php
// In block views
$textPresets = theme_text_presets();
$buttonPresets = theme_button_presets();

// Build scoped custom properties for this block
$textPreset = $textPresets['primary'];
$customProperties = collect([
    '--block-heading-color: ' . $textPreset['heading'],
    '--block-text-color: ' . $textPreset['description']
])->join('; ');
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
- [ ] **CSS custom properties** for theme integration
- [ ] **Theme presets** (`theme_text_presets()`, `theme_button_presets()`)
- [ ] **Navigation spacing** (pt-32 for first sections)
- [ ] **Fluid typography** (clamp() functions)
- [ ] **Grid compatible** (works inside TipTap grids)
- [ ] **Shared blocks.css styling** (unified for both admin and frontend)
- [ ] **Build verification** (test both admin preview and frontend)

## 🎯 CSS Architecture File Structure

```
resources/css/
├── app.css                           # Frontend entry (imports blocks.css)
├── blocks.css                        # Shared block styles for admin and frontend
└── filament/admin/
    └── theme.css                     # Custom Filament theme entry (imports shared blocks.css)

themes/my-theme/
├── views/
│   ├── layouts/app.blade.php          # Main layout
│   ├── cms/blocks/                    # Block template overrides
│   │   ├── content-block.blade.php
│   │   ├── hero-block.blade.php
│   │   └── call-to-action-block.blade.php
│   └── components/menu.blade.php      # Navigation
└── config/theme.php                   # Theme color/preset config
```

## 🚀 Quick Start

1. **Implement ThemeInterface** with complete color scales
2. **Update blocks.css** files with your theme's CSS custom properties
3. **Use CSS custom properties** in all block templates (scoped inline)
4. **Build assets** with `npm run build` to compile both admin and frontend
5. **Test consistency** in both admin preview and live frontend

## 📚 Key Files to Study

- `app/Support/ThemeColors.php` - Default theme implementation
- `resources/views/cms/blocks/content-block.blade.php` - CSS custom properties example
- `resources/css/app.css` - Frontend entry point and color system
- `resources/css/blocks.css` - Shared block styling for both admin and frontend
- `resources/css/filament/admin/theme.css` - Custom Filament theme entry (imports shared blocks.css)

**Remember**: Perfect admin/frontend consistency achieved through unified CSS architecture with custom Filament theme and shared blocks.css styling.