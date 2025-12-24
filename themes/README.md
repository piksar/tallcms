# TallCMS Theme Development Guide

This guide covers everything you need to know to create and maintain themes for TallCMS.

## Quick Start

### Creating a New Theme

```bash
php artisan make:theme "My Theme" --author="Your Name" --description="Theme description"
cd themes/my-theme
npm install && npm run build
cd ../..
php artisan theme:activate my-theme
```

New themes are automatically configured with all required dependencies and imports.

### Theme Structure

```
themes/your-theme/
├── theme.json                 # Theme metadata and configuration
├── package.json               # NPM dependencies
├── vite.config.js             # Vite build configuration
├── tailwind.config.js         # Tailwind CSS configuration
├── public/                    # Static assets (images, fonts)
│   ├── screenshot.png         # Theme screenshot (1200×900px recommended)
│   └── build/                 # Compiled assets (auto-generated)
└── resources/
    ├── views/                 # Blade templates
    │   ├── layouts/           # Layout overrides
    │   └── cms/blocks/        # Block template overrides
    ├── css/
    │   └── app.css            # Theme styles
    └── js/
        └── app.js             # Theme JavaScript
```

## TallCMS Core Components

TallCMS includes native Alpine.js components for built-in blocks (contact forms, galleries, etc.). Themes must import these for native blocks to function.

### New Themes (v1.0+)

New themes created with `make:theme` automatically include the required import. No action needed.

### Existing/Upgraded Themes

If your theme was created before core components were introduced, add this import to your theme's `resources/js/app.js`:

```javascript
// TallCMS Core Components - Required for native blocks
import '../../../../resources/js/tallcms';

// Your theme-specific code below...
```

Then rebuild:

```bash
cd themes/your-theme
npm run build
```

### What Happens Without This Import?

Native blocks like the contact form will render HTML but won't be interactive. You'll see console errors like:

```
Alpine Expression Error: contactForm is not defined
```

## Template Overrides

Themes can override any template by creating files that mirror the main application structure.

### Priority

1. `themes/your-theme/resources/views/` (highest priority)
2. `resources/views/` (fallback)

### Examples

| Override | Theme Path |
|----------|------------|
| Main layout | `resources/views/layouts/app.blade.php` |
| Contact form block | `resources/views/cms/blocks/contact-form.blade.php` |
| Menu component | `resources/views/components/menu.blade.php` |

## Styling

### Theme Colors

Define your color palette in `theme.json`:

```json
{
  "tailwind": {
    "colors": {
      "primary": {
        "50": "#eff6ff",
        "500": "#3b82f6",
        "600": "#2563eb",
        "700": "#1d4ed8",
        "900": "#1e3a8a"
      }
    }
  }
}
```

### CSS Custom Properties

Blocks use CSS custom properties for theme integration:

```css
/* These are set by blocks automatically */
--block-heading-color
--block-text-color
--block-button-bg
--block-button-text
```

### Theme Presets

Access theme colors in Blade templates:

```php
$textPresets = theme_text_presets();
$buttonPresets = theme_button_presets();
$colorPalette = theme_color_palette();
```

## Building Assets

### Development

```bash
cd themes/your-theme
npm run dev
```

### Production

```bash
cd themes/your-theme
npm run build
```

Or from project root:

```bash
php artisan theme:build your-theme
```

## Menu System

Themes should implement these menu locations:

| Location | Purpose | Recommended Style |
|----------|---------|-------------------|
| `header` | Main navigation | `horizontal` |
| `footer` | Footer links | `footer` |
| `mobile` | Mobile menu (falls back to header) | `mobile` |

### Usage

```blade
<x-menu location="header" style="horizontal" />
<x-menu location="footer" style="footer" />
```

## Theme Activation

```bash
# Activate a theme
php artisan theme:activate your-theme

# List available themes
php artisan theme:list

# Build before activating
php artisan theme:build your-theme
php artisan theme:activate your-theme
```

## Troubleshooting

### Alpine.js Errors

**Symptom:** Console errors like `contactForm is not defined`

**Solution:** Add the core components import to your theme's `app.js`:

```javascript
import '../../../../resources/js/tallcms';
```

### Styles Not Updating

**Solution:** Rebuild theme assets:

```bash
cd themes/your-theme && npm run build
```

### Theme Not Appearing

**Solution:** Clear theme cache:

```bash
php artisan theme:cache:clear
```

## Version Compatibility

| TallCMS Version | Theme Requirements |
|-----------------|-------------------|
| 1.0+ | Must import `tallcms` core components |

When upgrading TallCMS, rebuild your themes to pick up new core components:

```bash
cd themes/your-theme && npm run build
```
