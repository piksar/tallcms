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
- **Theme**: Amber primary color scheme
- **Authentication**: Built-in login system with full middleware stack

### Auto-Discovery Paths
Filament automatically discovers components in these directories (create as needed):
- **Resources**: `app/Filament/Resources/` (CRUD interfaces)
- **Pages**: `app/Filament/Pages/` (custom admin pages)
- **Widgets**: `app/Filament/Widgets/` (dashboard components)

### Database
- **Default**: SQLite (`database/database.sqlite`)
- **Testing**: In-memory SQLite
- **Migrations**: Standard Laravel migrations in `database/migrations/`

### Frontend Stack
- **CSS**: Tailwind CSS 4.0 (latest)
- **Build Tool**: Vite 7.0.7
- **Entry Points**: 
  - `resources/css/app.css`
  - `resources/js/app.js`
- **Compiled Assets**: `public/css/` and `public/js/`

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
- **Native Approach**: Stay as native to Tailwind as possible, avoiding custom CSS
- **Utility-First**: Prefer Tailwind utilities for styling consistency and maintainability

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
- **HeroBlock** - Hero sections with background images and CTAs
- **CallToActionBlock** - Styled promotional sections with buttons  
- **ImageGalleryBlock** - Professional galleries with lightbox functionality

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