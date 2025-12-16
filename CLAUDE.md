# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) application with Filament v4 as the admin panel framework. Built on Laravel 12 with PHP ^8.2.

## Development Commands

### Setup & Installation
```bash
composer setup          # Full project setup (installs deps, generates key, runs migrations, builds assets)
```

### Development Server
```bash
composer dev            # Runs concurrent development servers:
                        # - Laravel server (php artisan serve)
                        # - Queue worker (php artisan queue:listen)
                        # - Log viewer (php artisan pail)
                        # - Vite dev server (npm run dev)
```

### Testing
```bash
composer test           # Clears config cache and runs PHPUnit tests
php artisan test        # Direct test execution
```

### Asset Management
```bash
npm run dev            # Vite development server with HMR
npm run build          # Production asset compilation
```

### Code Quality
```bash
php artisan pint       # Laravel Pint code formatter (included in dev deps)
```

### Theme Management
```bash
# Create new theme
php artisan make:theme "MyTheme" --author="Your Name" --description="Theme description"

# List available themes
php artisan theme:list                    # Simple list
php artisan theme:list --detailed         # Detailed information

# Build theme assets
php artisan theme:build                   # Build active theme
php artisan theme:build my-theme          # Build specific theme
php artisan theme:build --install         # Install dependencies first

# Activate theme
php artisan theme:activate my-theme
```

## Architecture Overview

### Filament Admin Panel
- **Path**: `/admin` (accessible at `/admin` URL)
- **Provider**: `app/Providers/Filament/AdminPanelProvider.php`
- **Theme**: Amber primary color scheme with custom CSS architecture
- **Authentication**: Built-in login system with full middleware stack

### Auto-Discovery Paths
Filament automatically discovers components in these directories (create as needed):
- **Resources**: `app/Filament/Resources/` (CRUD interfaces)
- **Pages**: `app/Filament/Pages/` (custom admin pages)
- **Widgets**: `app/Filament/Widgets/` (dashboard components)

### Block Styling System
TallCMS implements a unified block styling system that ensures consistency between admin preview and frontend display:

- **Admin Theme**: `resources/css/filament/admin/theme.css` - Custom Filament theme
- **Shared Blocks**: `resources/css/blocks.css` - Unified block styles for both admin and frontend
- **CSS Custom Properties**: Theme integration using `--block-*` custom properties
- **Build Process**: Both admin and frontend CSS are compiled via Vite, sharing the same blocks.css

### Database
- **Default**: SQLite (`database/database.sqlite`)
- **Testing**: In-memory SQLite
- **Migrations**: Standard Laravel migrations in `database/migrations/`

### Frontend Stack
- **CSS**: Tailwind CSS 4.0 (latest)
- **Build Tool**: Vite 7.0.7
- **Entry Points**: 
  - `resources/css/app.css` (frontend)
  - `resources/css/filament/admin/theme.css` (admin panel)
  - `resources/js/app.js`
- **Compiled Assets**: `public/build/`

## Development Workflow

### Creating Filament Resources
```bash
php artisan make:filament-resource ModelName
```

### Creating Models with Filament Resources
```bash
php artisan make:model ModelName -mfs
php artisan make:filament-resource ModelName --generate
```

### Queue Management
Queue worker runs automatically with `composer dev`. For manual queue work:
```bash
php artisan queue:work
```

### Web Installer
TallCMS includes a user-friendly web installer for non-technical users:
```bash
# Access installer at /install when:
# - No .env file exists, OR
# - INSTALLER_ENABLED=true in .env, OR  
# - No storage/installer.lock file exists
```

**Installer Features:**
- System requirements check (PHP version, extensions, permissions)
- Database configuration and testing
- Admin user creation
- Mail settings (optional)
- Automatic .env generation
- Security lockdown after completion

**Manual Installation Alternative:**
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan make:user  # Interactive admin user creation
```

## Key Conventions

### Service Providers
- Main providers registered in `bootstrap/providers.php`
- Filament panel configured in `app/Providers/Filament/AdminPanelProvider.php`

### Asset Compilation
- Vite handles all asset compilation
- Tailwind CSS configured with Vite plugin
- Hot reload available during development

### Development Guidelines
- **Tailwind CSS**: Always use utility classes when building blocks and themes
- **Block Styling**: Use centralized blocks.css files with CSS custom properties for theme integration
- **Admin/Frontend Consistency**: Ensure styling works in both admin preview and frontend
- **Utility-First**: Prefer Tailwind utilities for styling consistency and maintainability
- **Custom CSS**: When needed, use CSS custom properties to maintain theme compatibility

### Testing Environment
- Uses array drivers for cache and session during tests
- Database automatically resets between tests
- Feature and Unit test suites separated

## Current State

This is a Laravel 12 application with a complete TallCMS implementation using Filament v4. The CMS includes:

### CMS Structure
- **Pages** (`tallcms_pages`) - Static content pages (About, Contact, etc.) - no categories
- **Posts** (`tallcms_posts`) - Dynamic articles/blog posts with categories
- **Categories** (`tallcms_categories`) - Hierarchical categories for posts only
- **Post-Category Relationship** (`tallcms_post_category`) - Many-to-many pivot table

### Custom Blocks for Rich Editor
- **ContentBlock** - Article/blog content with subtitle, content width options, and heading levels
- **PricingBlock** - Comprehensive pricing tables with plans, features, and CTA buttons
- **HeroBlock** - Hero sections with background images and CTAs
- **CallToActionBlock** - Styled promotional sections with buttons  
- **ImageGalleryBlock** - Professional galleries with lightbox functionality

#### Block Development Guidelines
- Use CSS custom properties (`--block-*`) for theme integration
- Mirror styling between admin (`blocks.css`) and frontend for consistency
- Follow semantic HTML structure with proper heading hierarchy (H2/H3/H4)
- Leverage Tailwind utility classes for responsive design
- Test both admin preview and frontend display during development

### Merge Tags System
Dynamic content insertion using `{{tag_name}}` syntax:
- **Site Tags** - `{{site_name}}`, `{{current_year}}`, `{{site_url}}`
- **Page Tags** - `{{page_title}}`, `{{page_url}}`, `{{page_author}}`
- **Post Tags** - `{{post_author}}`, `{{post_categories}}`, `{{post_reading_time}}`
- **Contact Tags** - `{{contact_email}}`, `{{company_name}}`, `{{company_address}}`
- **Social Tags** - `{{social_facebook}}`, `{{social_twitter}}`, etc.

### Filament Resources
- **PageResource** - Manages static pages with rich content editor
- **PostResource** - Manages articles with categories, SEO, and author attribution
- **CategoryResource** - Manages hierarchical categories for posts

All tables use `tallcms_` prefix for future plugin compatibility.

## Multi-Theme System

### Overview
TallCMS includes a comprehensive multi-theme system that supports file-system based themes with Laravel-compliant directory structure, template overriding capabilities, and Tailwind CSS integration. The system provides theme discovery, activation, asset management, and seamless integration with the existing ThemeInterface system.

### Theme Architecture
```
themes/{slug}/                       # Theme root directory
├── theme.json                       # Theme metadata and configuration
├── package.json                     # NPM dependencies and scripts
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

### Theme Management Commands

#### Creating Themes
```bash
php artisan make:theme theme-name --description="Theme description" --author="Author Name"
```

Creates a complete theme structure with:
- Laravel-compliant directory layout
- Theme metadata (theme.json)
- Package configuration with proper Vite/Tailwind versions
- Complete block styles copied from main application
- Example templates and components
- Proper build configuration

#### Theme Operations
```bash
php artisan theme:list              # List all available themes
php artisan theme:activate {slug}   # Activate a theme
php artisan theme:build {slug}      # Build theme assets
```

### Theme Configuration Architecture

The multi-theme system uses a distributed configuration approach where each theme maintains its own configuration:

#### Global Configuration (`config/theme.php`)
```php
return [
    // Only contains the active theme slug
    // Automatically managed by ThemeManager
    'active' => 'current-theme-slug',
];
```

#### Individual Theme Configuration (`themes/{slug}/theme.json`)
```json
{
    "name": "Theme Name",
    "slug": "theme-slug",
    "version": "1.0.0",
    "description": "Theme description",
    "author": "Author Name",
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
    },
    "supports": {
        "dark_mode": true,
        "content_block": true,
        "hero_block": true,
        "pricing_block": true
    },
    "build": {
        "entries": ["resources/css/app.css", "resources/js/app.js"]
    }
}
```

**Configuration Source of Truth:**
- **Active Theme**: `config/theme.php['active']` (managed by ThemeManager)
- **Theme Discovery**: Automatic scanning of `themes/` directory
- **Theme Metadata**: Individual `theme.json` files in each theme
- **No Central Registry**: Themes are discovered dynamically, no need to register them manually

### Template Override System
Themes can override any template by creating files in the same relative path structure:

#### Priority Resolution
1. **Theme Templates**: `themes/{active}/resources/views/`
2. **Application Templates**: `resources/views/`

#### Example Overrides
- Override layout: `themes/my-theme/resources/views/layouts/app.blade.php`
- Override block: `themes/my-theme/resources/views/cms/blocks/content-block.blade.php`
- Override component: `themes/my-theme/resources/views/components/hero-section.blade.php`

### Asset Management

#### Vite Integration
Each theme has its own Vite configuration and build process:
```javascript
// themes/{slug}/vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            publicDirectory: 'public',
        }),
    ],
    build: {
        outDir: 'public/build',
        manifest: true,
    },
});
```

#### Theme Assets in Templates
Use the `@themeVite` directive for theme-specific assets:
```blade
@themeVite(['resources/css/app.css', 'resources/js/app.js'])
```

For static assets:
```blade
@themeAsset('images/logo.png')
```

#### Asset Publishing
The system automatically:
- Creates symlinks from `themes/{slug}/public` to `public/themes/{slug}`
- Falls back to copying if symlinks fail
- Manages asset compilation and manifest generation

### Theme Development Workflow

#### 1. Create Theme Structure
```bash
php artisan make:theme my-custom-theme
```

#### 2. Customize Theme Metadata
Edit `themes/my-custom-theme/theme.json` to define:
- Color palette in Tailwind configuration
- Supported blocks and features
- Theme information

#### 3. Build and Activate
```bash
cd themes/my-custom-theme
npm install
npm run build
cd ../..
php artisan theme:activate my-custom-theme
```

#### 4. Template Customization
Override any template by creating files in `resources/views/` following the same structure as the main application.

### ThemeInterface Integration
File-based themes integrate seamlessly with the existing ThemeInterface system:

```php
// Access current theme
$themeManager = app('theme.manager');
$activeTheme = $themeManager->getActiveTheme();

// Get theme presets (automatically uses file-based theme)
$textPresets = theme_text_presets();
$buttonPresets = theme_button_presets();
$colorPalette = theme_color_palette();
```

### CSS Architecture & Block Styling

#### Unified Block Styling
Themes maintain consistency with the main application through:
- **Shared Block Styles**: Complete `blocks.css` copied during theme generation
- **CSS Custom Properties**: Theme integration via `--block-*` variables
- **Theme-Specific Overrides**: Additional styling in theme's blocks.css

#### Block Template Integration
```php
@php
    $textPreset = theme_text_presets()['primary'];
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'], 
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb'),
        '--block-link-hover-color: ' . ($textPreset['link_hover'] ?? '#1d4ed8')
    ])->join('; ') . ';';
@endphp

<article style="{{ $customProperties }}">
    <!-- block content uses CSS custom properties -->
</article>
```

#### File Structure
```
# Main Application
resources/css/
├── app.css                          # Frontend entry point
├── blocks.css                       # Shared block styles
└── filament/admin/
    └── theme.css                    # Admin panel entry point

# Theme Structure  
themes/{slug}/resources/css/
├── app.css                          # Theme entry point (imports blocks.css)
└── blocks.css                       # Complete block styles + theme customizations
```

### Production Considerations

#### Error Handling
The system includes comprehensive error handling:
- **Theme Not Found**: Falls back to default theme or original templates
- **Build Failures**: Prevents theme activation if assets can't be compiled
- **Asset Missing**: Graceful fallback to main application assets
- **Symlink Failures**: Automatic fallback to directory copying

#### Performance
- **Asset Caching**: Vite generates hashed filenames for cache busting
- **Manifest Reading**: Real-time asset path resolution from build manifest
- **View Path Caching**: Efficient template resolution with Laravel's view system
- **Config Caching**: Proper cache management during theme switches

#### Development vs Production
- **Development**: Uses `npm run dev` with hot reloading
- **Production**: Uses `npm run build` with optimized, hashed assets
- **Asset URLs**: Automatically resolves to correct hashed filenames

#### Theme Development & Hot Reloading
- **Theme Dev Server**: Each theme can run its own dev server with `npm run dev` from theme directory
- **Hot File Location**: Theme hot files written to `public/themes/{slug}/hot` (via symlink from `themes/{slug}/public/hot`)
- **Dev Server Priority**: Theme-local hot file takes precedence over main app hot file
- **Import Resolution**: Manifest imports resolved within same theme manifest; parent theme chunks not automatically pulled in
- **Fallback Chain**: Dev server → Theme manifests → Main app manifest → Static CSS fallback
- **Development Workflow**: 
  1. Run theme dev server: `cd themes/{slug} && npm run dev`
  2. Or run main app dev server: `npm run dev` (from project root)
  3. Theme-specific changes use theme dev server, global changes use main dev server

### Block Development Guidelines (Legacy CSS Architecture)

For projects not using the multi-theme system, follow these guidelines for block development:

#### Creating New Blocks
1. **Create Block Component**: Use Filament form components with theme integration
2. **Create Template**: Build responsive template with semantic HTML
3. **Add Block Styles**: Update both `blocks.css` files with consistent styling
4. **Use Custom Properties**: Integrate with theme system via `--block-*` properties
5. **Build Assets**: Run `npm run build` to compile both admin and frontend CSS
6. **Test Consistency**: Verify styling in both admin preview and frontend

#### Modifying Existing Blocks  
1. **Update Template**: Modify the block template file
2. **Update Styles**: Sync changes in both admin and frontend `blocks.css` files
3. **Build & Test**: Compile assets and test in both contexts

#### Adding Theme Integration
- Use `theme_text_presets()` helper to get current theme colors
- Set CSS custom properties in block templates
- Reference custom properties in CSS classes
- Ensure fallback values for all custom properties

### Legacy Build Process
- **Frontend**: `resources/css/app.css` imports shared `blocks.css` and compiles to `public/build/assets/app-*.css`
- **Admin**: `resources/css/filament/admin/theme.css` imports shared `blocks.css` and compiles to `public/build/assets/theme-*.css`
- **Shared CSS**: Both builds use the same `blocks.css` ensuring perfect consistency
- **Vite Configuration**: Automatically detects and processes both entry points
- **Hot Reload**: Changes to the shared blocks.css file update both admin and frontend automatically

## Helper Functions

### Theme Functions
```php
// Multi-theme system helpers
$themeManager = app('theme.manager');
$activeTheme = active_theme();
$themeAssetUrl = theme_asset('images/logo.png');

// Legacy theme system helpers
$textPresets = theme_text_presets();
$buttonPresets = theme_button_presets();
$colorPalette = theme_color_palette();
$paddingPresets = theme_padding_presets();
```

### Merge Tags  
Use `{{tag_name}}` syntax for dynamic content:
```php
// Available merge tags
{{site_name}}, {{current_year}}, {{site_url}}
{{page_title}}, {{page_url}}, {{page_author}}
{{post_author}}, {{post_categories}}, {{post_reading_time}}
{{contact_email}}, {{company_name}}, {{social_facebook}}
```