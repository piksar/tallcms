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
- Mail settings (SMTP, SES, PHP Mail, Sendmail)
- Cloud storage (S3-compatible: AWS, DigitalOcean, Cloudflare R2, etc.)
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
    'active' => 'current-theme-slug',  // Managed by ThemeManager
    'cache_enabled' => false,           // Theme discovery caching
    'cache_ttl' => 3600,               // Cache duration in seconds
    'preview_duration' => 30,          // Preview session duration (minutes)
    'rollback_duration' => 24,         // Rollback availability (hours)
    'allow_uploads' => true,           // Enable ZIP-based theme uploads
];
```

#### TallCMS Version (`config/tallcms.php`)
```php
return [
    'version' => '1.0.0',  // Single source of truth for compatibility checks
    'contact_email' => env('TALLCMS_CONTACT_EMAIL'),
    'company_name' => env('TALLCMS_COMPANY_NAME'),
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
- **TallCMS Version**: `config/tallcms.php['version']` (used by Theme and ThemeValidator)
- **Theme Discovery**: Automatic scanning of `themes/` directory
- **Theme Metadata**: Individual `theme.json` files in each theme
- **No Central Registry**: Themes are discovered dynamically, no need to register them manually

### Theme Management Admin UI

The admin panel includes a WordPress-like theme management interface at **Appearance > Themes**.

#### Admin Page (`app/Filament/Pages/ThemeManager.php`)
- Visual theme gallery with screenshots and metadata
- One-click theme activation with preflight validation
- Live preview in new tab (30-minute session)
- One-click rollback to previous theme (24-hour window)
- ZIP-based theme upload (when `allow_uploads` enabled)
- Delete themes with Filament confirmation modal
- Shield permission: `View:ThemeManager`

#### Theme Validation (`app/Services/ThemeValidator.php`)
Validates themes before activation with comprehensive checks:
- **Required files**: `theme.json` must exist
- **Required fields**: `name`, `slug`, `version`, `description`, `author`
- **Forbidden files**: PHP files (except `.blade.php`), `.htaccess`, `.env`
- **PHP detection**: Catches double extensions like `foo.php.txt`
- **Path traversal**: Blocks `..`, encoded traversal, absolute paths
- **Compatibility**: PHP version, extensions, TallCMS version
- **Build state**: Verifies manifest exists and referenced files exist
- **ZIP validation**: Size limits (100MB), file count (5000), traversal protection
- **Slug validation**: Lowercase alphanumeric + hyphens only, max 64 characters

#### Theme Upload Security
The theme upload system includes multiple security layers:
- **Server-side config guard**: Verifies `allow_uploads` config before processing
- **Slug sanitization**: Prevents path traversal via malicious slugs in theme.json
- **Pre-built themes**: Skips npm build if `public/build/manifest.json` exists
- **Cleanup on failure**: Removes both `themes/{slug}` and `public/themes/{slug}` on any error
- **Graceful error handling**: Logs full stack trace, shows user-friendly message
- **50MB upload limit**: Configured in `config/livewire.php`

#### Theme Preview Middleware (`app/Http/Middleware/ThemePreviewMiddleware.php`)
Handles temporary theme previews via `?theme_preview={slug}`:
- Session-based preview with configurable expiration
- Validates theme is built and meets requirements before preview
- Overrides view paths, namespaces, and ThemeInterface binding
- Stores preview errors in session for user feedback
- Skips admin routes automatically

#### Theme Compatibility (`theme.json`)
```json
{
    "compatibility": {
        "tallcms": "^1.0",      // TallCMS version requirement (enforced)
        "php": "^8.2",          // PHP version requirement (enforced)
        "extensions": ["gd"],   // Required PHP extensions (enforced)
        "prebuilt": true        // Source themes blocked in production
    }
}
```

#### Screenshots
Theme screenshots must be placed in `public/` directory to be web-accessible:
- Primary: `public/screenshot.png` or configured in `theme.json`
- Gallery: `screenshots.gallery` array in `theme.json` (paths relative to `public/`)

#### Key Services
- **ThemeManager** (`app/Services/ThemeManager.php`): Theme discovery, activation, rollback, asset management, ZIP extraction, theme deletion
- **ThemeValidator** (`app/Services/ThemeValidator.php`): Preflight validation, ZIP scanning, slug validation, compatibility checks
- **Theme** (`app/Models/Theme.php`): Theme data model with compatibility/requirements methods
- **FileBasedTheme** (`app/Services/FileBasedTheme.php`): Adapts file themes to ThemeInterface

#### Implementation Notes
- **View Finder**: Always use `View::getFinder()` not `app('view.finder')` - Laravel has two different instances
- **Theme Activation**: Clears opcache, file stat cache, view cache, and compiled views for immediate effect
- **Rollback**: Stored in cache with configurable TTL; validates theme still exists before rollback
- **Preview**: Uses reflection to override ThemeManager singleton; clears namespaces to prevent accumulation

### Template Override System
Themes can override any template by creating files in the same relative path structure:

#### Priority Resolution
1. **Theme Templates**: `themes/{active}/resources/views/`
2. **Application Templates**: `resources/views/`

#### Example Overrides
- Override layout: `themes/my-theme/resources/views/layouts/app.blade.php`
- Override block: `themes/my-theme/resources/views/cms/blocks/content-block.blade.php`
- Override component: `themes/my-theme/resources/views/components/hero-section.blade.php`

### Menu System

TallCMS provides a flexible menu system with multiple locations. Themes should implement these menu locations for a complete user experience.

#### Menu Locations
| Location | Purpose | Recommended Style |
|----------|---------|------------------|
| `header` | Main navigation in the header | `horizontal` |
| `footer` | Footer navigation links | `footer` |
| `mobile` | Mobile navigation (falls back to header if not defined) | `mobile` |
| `sidebar` | Sidebar navigation for layouts with sidebars | `sidebar` |

#### Using Menus in Themes
```blade
{{-- Header menu (horizontal style) --}}
<x-menu location="header" style="horizontal" />

{{-- Footer menu --}}
<x-menu location="footer" style="footer" />

{{-- Mobile menu with fallback to header --}}
@if(menu('mobile'))
    <x-menu location="mobile" style="mobile" />
@else
    <x-menu location="header" style="mobile" />
@endif

{{-- Sidebar menu --}}
<x-menu location="sidebar" style="sidebar" />
```

#### Available Menu Styles
- **horizontal**: Inline menu items with dropdown support (for headers)
- **vertical**: Stacked menu items with nesting (for sidebars)
- **footer**: Compact inline links (for footers)
- **mobile**: Touch-optimized with larger tap targets
- **sidebar**: Collapsible sections with icons

#### Menu Helper Function
```php
// Get menu data by location
$menuItems = menu('header');

// Check if menu exists before rendering
if (menu('mobile')) {
    // Mobile menu exists
}
```

#### Menu Item Structure
Each menu item has these properties:
- `id` - Unique identifier
- `label` - Display text
- `url` - Resolved URL
- `type` - Item type (`link`, `page`, `header`, `separator`)
- `target` - Link target (`_self`, `_blank`)
- `icon` - Icon class (e.g., Heroicon class)
- `css_class` - Custom CSS classes
- `children` - Nested menu items (array)

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

### AWS / Storage Functions
```php
// Get the configured media storage disk ('s3' or 'public')
$disk = cms_media_disk();

// Get visibility setting for media uploads
$visibility = cms_media_visibility();

// Check if S3 storage is configured
if (cms_uses_s3()) {
    // Handle S3-specific logic
}
```

## Cloud Storage Integration

TallCMS supports S3-compatible cloud storage for file uploads. This works with multiple providers:
- **Amazon S3** - AWS's object storage service
- **DigitalOcean Spaces** - S3-compatible object storage
- **Cloudflare R2** - Zero egress fee storage
- **Backblaze B2** - Affordable cloud storage
- **Wasabi** - Hot cloud storage
- **MinIO** - Self-hosted S3-compatible storage
- **Any S3-compatible provider**

### Configuration via Web Installer

The web installer includes a Cloud Storage section where users can configure:
- **Storage Provider** - Select from common providers or "Other S3-Compatible"
- **Access Key ID** - Your provider's access key
- **Secret Access Key** - Your provider's secret key
- **Region** - Storage region (e.g., `us-east-1`, `nyc3`, `auto`)
- **Bucket Name** - Your storage bucket name
- **Endpoint URL** - Required for non-AWS providers

When a bucket is provided, the installer automatically sets `FILESYSTEM_DISK=s3`.

### Manual Configuration

Add these environment variables to your `.env` file:

```env
# S3-Compatible Storage Credentials
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
FILESYSTEM_DISK=s3

# For non-AWS providers, add endpoint:
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
# AWS_USE_PATH_STYLE_ENDPOINT=true  # For MinIO or path-style APIs
```

### Provider-Specific Examples

**DigitalOcean Spaces:**
```env
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=my-space-name
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
FILESYSTEM_DISK=s3
```

**Cloudflare R2:**
```env
AWS_ACCESS_KEY_ID=your-r2-access-key
AWS_SECRET_ACCESS_KEY=your-r2-secret-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=my-bucket
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
FILESYSTEM_DISK=s3
```

**MinIO (Self-Hosted):**
```env
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
FILESYSTEM_DISK=s3
```

### What Uses Cloud Storage

When configured, all CMS file uploads automatically use cloud storage:
- Media library uploads
- Featured images for pages and posts
- Hero block background images
- Image gallery images
- Site logo and favicon

### Amazon SES Email

For AWS users, TallCMS also supports Amazon SES for email delivery:
1. Select "Amazon SES" as the mailer in the installer
2. Configure AWS credentials (same as S3)
3. Verify your domain/email in SES console
4. Request production access (SES starts in sandbox mode)

```env
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your Site Name"
```

### Fallback Behavior

If cloud storage is not configured:
- File storage defaults to local `public` disk
- All functionality remains fully operational
- No code changes required

### Helper Functions

```php
// Determine which disk to use for media uploads
$disk = cms_media_disk();  // Returns 's3' or 'public'

// Check if using cloud storage
if (cms_uses_s3()) {
    // Cloud storage-specific handling
}

// Use in Blade templates
@if(Storage::disk(cms_media_disk())->exists($path))
    <img src="{{ Storage::disk(cms_media_disk())->url($path) }}">
@endif
```

### Bucket Configuration Tips

**Public Access:** Ensure your bucket allows public read access for uploaded files.

**CORS Configuration:** For direct uploads, configure CORS on your bucket:
```json
[
    {
        "AllowedOrigins": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST"],
        "AllowedHeaders": ["*"]
    }
]
```

**CDN:** For better performance, consider using a CDN (CloudFront, Cloudflare, etc.) in front of your storage.