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

## CSS Architecture & Theme Development

### Overview
TallCMS uses a unified CSS architecture that ensures consistent styling between the Filament admin panel and frontend. This architecture leverages CSS custom properties for theme integration and centralized block styling.

### File Structure
```
resources/css/
├── app.css                          # Frontend entry point
├── blocks.css                       # Shared block styles for both admin and frontend
└── filament/admin/
    └── theme.css                    # Admin panel entry point (imports shared blocks.css)
```

### CSS Custom Properties System
All blocks use CSS custom properties for theme integration:

```css
:root {
    --block-heading-color: #111827;
    --block-text-color: #374151;
    --block-link-color: #2563eb;
    --block-link-hover-color: #1d4ed8;
    --block-border-color: #e5e7eb;
    --block-background-light: #f9fafb;
}
```

### Block Template Integration
Block templates set theme-specific custom properties as inline styles to avoid conflicts:

```php
@php
    // Build inline CSS custom properties for this block instance
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'], 
        '--block-link-color: ' . ($textPreset['link'] ?? '#2563eb'),
        '--block-link-hover-color: ' . ($textPreset['link_hover'] ?? '#1d4ed8')
    ])->join('; ') . ';';
@endphp

<article style="{{ $customProperties }}">
    <!-- block content -->
</article>
```

### Development Workflow

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

### Build Process
- **Frontend**: `resources/css/app.css` imports shared `blocks.css` and compiles to `public/build/assets/app-*.css`
- **Admin**: `resources/css/filament/admin/theme.css` imports shared `blocks.css` and compiles to `public/build/assets/theme-*.css`
- **Shared CSS**: Both builds use the same `blocks.css` ensuring perfect consistency
- **Vite Configuration**: Automatically detects and processes both entry points
- **Hot Reload**: Changes to the shared blocks.css file update both admin and frontend automatically