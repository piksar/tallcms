# TallCMS Theme Development Guide

Complete guide for creating and customizing themes in TallCMS using the file-based multi-theme system.

## 🏗️ Theme System Architecture

TallCMS uses a **file-based theme system** where each theme is a self-contained directory with its own configuration, assets, and template overrides. Themes are automatically discovered and can override any template in the application.

### Core Concepts

- **File-Based Themes**: Themes live in `themes/{slug}/` directories with Laravel-compliant structure
- **Template Override Resolution**: Themes override templates by path priority (theme → app default)
- **Asset Management**: Each theme has its own Vite build process with automatic asset compilation
- **Configuration**: Themes use `theme.json` for metadata and Tailwind/Vite configs for build setup
- **Zero Code Changes**: Existing blocks and templates work unchanged with theme overrides

### Directory Structure

```
themes/{slug}/                       # Theme root directory
├── theme.json                       # Theme metadata and configuration
├── package.json                     # NPM dependencies and build scripts  
├── vite.config.js                   # Vite build configuration
├── tailwind.config.js               # Theme-specific Tailwind configuration
├── public/                          # Static assets (images, fonts, etc.)
│   ├── css/
│   ├── js/
│   ├── img/
│   └── build/                       # Compiled assets (auto-generated)
│       ├── manifest.json            # Vite asset manifest
│       └── assets/                  # Hashed asset files
└── resources/                       # Source files
    ├── views/                       # Template overrides
    │   ├── layouts/                 # Layout templates
    │   ├── cms/blocks/              # Block template overrides
    │   └── components/              # Component overrides
    ├── css/
    │   ├── app.css                  # Main stylesheet
    │   └── blocks.css               # Block-specific styles
    ├── js/
    │   └── app.js                   # JavaScript entry point
    └── img/                         # Source images
```

## 🚀 Quick Start

### 1. Create a New Theme

#### Interactive Mode (Recommended)
```bash
php artisan make:theme
```

The interactive mode will guide you through creating a theme with prompts for:
- Theme name and description
- Author information
- Color scheme selection (11 built-in palettes + custom hex)
- Feature selection (dark mode, animations, custom fonts)
- Supported blocks selection

#### Non-Interactive Mode
```bash
php artisan make:theme my-awesome-theme \
    --description="A modern, clean theme for business websites" \
    --author="Your Name" \
    --theme-version="1.0.0"
```

#### Force Interactive Mode
```bash
php artisan make:theme my-theme --interactive
```

This creates a complete theme structure with:
- Complete Laravel-compliant directory layout
- Properly populated theme metadata (`theme.json`)
- Package configuration with proper Vite/Tailwind versions
- Complete block styles copied from main application
- Color palette based on your selection
- Feature-based dependency management
- Example templates and components
- Proper build configuration

### 2. Customize Theme Configuration

Edit `themes/my-awesome-theme/theme.json`:

```json
{
    "name": "My Awesome Theme",
    "slug": "my-awesome-theme",
    "version": "1.0.0",
    "description": "A modern, clean theme for business websites",
    "author": "Your Name",
    "tailwind": {
        "colors": {
            "primary": {
                "50": "#f0f9ff",
                "100": "#e0f2fe", 
                "200": "#bae6fd",
                "300": "#7dd3fc",
                "400": "#38bdf8",
                "500": "#0ea5e9",
                "600": "#0284c7",
                "700": "#0369a1",
                "800": "#075985",
                "900": "#0c4a6e",
                "950": "#082f49"
            },
            "secondary": {
                "500": "#6b7280",
                "600": "#4b5563"
            }
        }
    },
    "supports": {
        "dark_mode": true,
        "responsive": true,
        "blocks": [
            "content",
            "hero", 
            "pricing",
            "call-to-action",
            "gallery"
        ]
    },
    "build": {
        "css": "resources/css/app.css",
        "js": "resources/js/app.js",
        "output": "public"
    }
}
```

### 3. Customize Colors

Update your theme's Tailwind configuration (`themes/my-awesome-theme/tailwind.config.js`):

```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        '../../resources/views/**/*.blade.php', // Include main app views
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#f0f9ff',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    900: '#0c4a6e',
                },
                // Add more custom colors
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },
})
```

### 4. Build and Activate

```bash
# Navigate to theme directory
cd themes/my-awesome-theme

# Install dependencies
npm install

# Build assets
npm run build

# Return to project root and activate theme
cd ../..
php artisan theme:activate my-awesome-theme
```

## 🎨 Theme Customization

### Template Overrides

Override any template by creating files in the same relative path structure:

```bash
# Override main layout
themes/my-theme/resources/views/layouts/app.blade.php

# Override specific blocks
themes/my-theme/resources/views/cms/blocks/content-block.blade.php
themes/my-theme/resources/views/cms/blocks/hero-block.blade.php
themes/my-theme/resources/views/cms/blocks/pricing-block.blade.php

# Override components  
themes/my-theme/resources/views/components/hero-section.blade.php
```

**Template Resolution Priority:**
1. **Theme Template**: `themes/{active}/resources/views/cms/blocks/pricing-block.blade.php`
2. **Application Default**: `resources/views/cms/blocks/pricing-block.blade.php`

### Block Styling

Customize block appearance by editing `themes/my-theme/resources/css/blocks.css`:

```css
/* Theme-specific block customizations */

/* Content Block Styling */
.content-block {
    /* Your custom styles here */
    @apply bg-white rounded-lg shadow-sm;
}

.content-block h2 {
    /* Override heading styles */
    @apply text-3xl font-bold text-primary-900 mb-6;
}

/* Hero Block Styling */
.hero-block {
    @apply bg-gradient-to-br from-primary-50 to-primary-100;
}

.hero-block .hero-heading {
    @apply text-5xl font-extrabold text-primary-900;
}

/* Pricing Block Styling */
.pricing-block .pricing-card {
    @apply bg-white border border-primary-200 rounded-xl shadow-sm hover:shadow-md transition-shadow;
}

.pricing-block .pricing-card.featured {
    @apply border-primary-500 shadow-lg scale-105;
}
```

### Asset Management

Use theme-specific assets in templates:

```blade
{{-- Use @themeVite for compiled assets --}}
@themeVite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Use @themeAsset for static assets --}}
<img src="@themeAsset('images/logo.png')" alt="Logo">

{{-- Or use the helper function --}}
<img src="{{ theme_asset('images/hero-bg.jpg') }}" alt="Hero Background">
```

### CSS Custom Properties Integration

Themes automatically integrate with the CSS custom properties system:

```blade
{{-- In block templates --}}
@php
    $textPreset = theme_text_presets()['primary'];
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'], 
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb'),
        '--block-link-hover-color: ' . ($textPreset['link_hover'] ?? '#1d4ed8')
    ])->join('; ') . ';';
@endphp

<article class="content-block" style="{{ $customProperties }}">
    <h2 style="color: var(--block-heading-color);">{{ $heading }}</h2>
    <p style="color: var(--block-text-color);">{{ $content }}</p>
    <a href="#" style="color: var(--block-link-color);">Read more</a>
</article>
```

## 🛠️ Development Workflow

### Development Mode

For active development with hot reloading:

```bash
cd themes/my-awesome-theme
npm run dev
```

This starts Vite's development server with hot module replacement for rapid iteration.

### Production Build

For production-ready assets:

```bash
cd themes/my-awesome-theme
npm run build
```

This creates optimized, hashed assets in the `public/build/` directory.

### Theme Commands

```bash
# List available themes
php artisan theme:list
php artisan theme:list --detailed

# Build theme assets
php artisan theme:build my-theme
php artisan theme:build --install    # Install dependencies first

# Activate theme  
php artisan theme:activate my-theme
```

## 🎯 Best Practices

### Design Guidelines

1. **Tailwind-First Approach**: Use utility classes for rapid development and consistency
2. **Semantic HTML Structure**: Follow proper heading hierarchy (H1 → H2 → H3 → H4)
3. **Responsive Design**: Design mobile-first, enhance for larger screens
4. **Accessibility**: Ensure proper contrast ratios and keyboard navigation
5. **Performance**: Optimize images and minimize custom CSS

### Development Guidelines

1. **Follow Laravel Conventions**: Use standard directory structure and naming
2. **Theme Inheritance**: Override only what needs to change, inherit the rest
3. **CSS Custom Properties**: Use the theme system for colors and spacing
4. **Asset Optimization**: Leverage Vite for efficient asset compilation
5. **Testing**: Test theme in both admin preview and frontend display

### File Organization

```bash
# Keep source files organized
resources/
├── css/
│   ├── app.css              # Main entry point
│   ├── blocks.css           # Block-specific styles
│   └── components/          # Component-specific styles
│       ├── hero.css
│       └── navigation.css
├── js/
│   ├── app.js               # Main entry point
│   └── components/          # Component scripts
└── views/
    ├── layouts/             # Layout overrides
    ├── cms/blocks/          # Block overrides
    └── components/          # Component overrides
```

## 🔧 Advanced Configuration

### Custom Vite Configuration

Extend the default Vite setup for advanced needs:

```javascript
// themes/my-theme/vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/blocks.css',
                'resources/js/app.js'
            ],
            publicDirectory: 'public',
        }),
    ],
    build: {
        outDir: 'public/build',
        manifest: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs'],
                },
            },
        },
    },
    css: {
        postcss: {
            plugins: [
                require('autoprefixer'),
            ],
        },
    },
});
```

### Theme-Specific JavaScript

Add theme-specific JavaScript functionality:

```javascript
// themes/my-theme/resources/js/app.js
import Alpine from 'alpinejs'

// Theme-specific Alpine components
Alpine.data('heroSlider', () => ({
    currentSlide: 0,
    slides: [],
    
    init() {
        this.slides = this.$refs.slides.children
        setInterval(() => {
            this.nextSlide()
        }, 5000)
    },
    
    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length
    }
}))

// Initialize Alpine
Alpine.start()
```

## 🚨 Troubleshooting

### Common Issues

**Theme not activating:**
- Ensure `theme.json` exists and is valid JSON
- Check that build assets exist (`npm run build`)
- Verify symlinks are created in `public/themes/{slug}/`

**Styles not loading:**
- Check that Vite manifest exists (`public/build/manifest.json`)
- Ensure `@themeVite` directive is used in layout
- Verify CSS files are properly imported in `app.css`

**Template overrides not working:**
- Check file path matches exactly (`cms/blocks/content-block.blade.php`)
- Ensure theme is properly activated
- Clear view cache: `php artisan view:clear`

**Asset compilation errors:**
- Check Node.js version compatibility
- Clear node_modules and reinstall: `rm -rf node_modules && npm install`
- Verify Tailwind configuration is valid

### Debug Commands

```bash
# Check theme status
php artisan theme:list --detailed

# Clear caches
php artisan view:clear
php artisan config:clear

# Check symlinks
ls -la public/themes/

# Verify theme configuration
php artisan tinker
>>> $theme = app('theme.manager')->getActiveTheme()
>>> ['slug' => $theme->slug, 'name' => $theme->name, 'version' => $theme->version]
```

## 📚 Reference

### Helper Functions

```php
// Theme Management
$themeManager = app('theme.manager');
$activeTheme = active_theme();
$themeAssetUrl = theme_asset('images/logo.png');

// Theme Presets (automatically uses file-based theme)
$textPresets = theme_text_presets();
$buttonPresets = theme_button_presets(); 
$colorPalette = theme_colors();
$paddingPresets = theme_padding_presets();
```

### Blade Directives

```blade
{{-- Theme assets with automatic fallback --}}
@themeVite(['resources/css/app.css', 'resources/js/app.js'])
@themeAsset('images/logo.png')

{{-- Access theme information --}}
@theme
@theme('version')
@theme('author')
```

### File Structure Reference

```
themes/my-theme/
├── theme.json                       # Required: Theme metadata
├── package.json                     # Required: NPM configuration
├── vite.config.js                   # Required: Build configuration  
├── tailwind.config.js               # Required: Tailwind configuration
├── .gitignore                       # Recommended: Git ignore rules
├── public/                          # Static assets
│   ├── build/                       # Generated: Compiled assets
│   ├── images/                      # Theme images
│   └── fonts/                       # Custom fonts
└── resources/                       # Source files
    ├── css/
    │   ├── app.css                  # Required: Main stylesheet
    │   └── blocks.css               # Required: Block styles  
    ├── js/
    │   └── app.js                   # Required: JavaScript entry
    └── views/                       # Optional: Template overrides
        ├── layouts/
        ├── cms/blocks/
        └── components/
```

This guide provides everything you need to create beautiful, functional themes for TallCMS using the modern file-based theme system.