# TallCMS

A powerful, modern Content Management System built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) with Filament v4 admin panel.

## ✨ Features

### 🎨 **Rich Content Editor**
- **TipTap Rich Editor** with custom blocks support
- **Merge Tags** for dynamic content insertion
- **Live Preview** - see exactly what your content will look like
- **Auto-Discovery** - custom blocks are automatically registered

### 🧱 **Custom Block System**
- **Hybrid Styling** - Tailwind classes + inline styles for perfect compatibility
- **Single Template** approach - one template for both preview and frontend
- **Auto-Generated** blocks via custom Artisan command
- **Plug & Play** - create once, works everywhere immediately

### 🏠 **Smart Homepage Management**
- **Dynamic Homepage** detection and routing
- **One-Click Setup** - designate any page as homepage
- **Automatic Routing** - visitors are seamlessly redirected to homepage
- **Fallback Support** - graceful handling when no homepage is set

### 📄 **Flexible Content Architecture**
- **Hierarchical Pages** - parent/child relationships with navigation
- **Posts/Articles** - categorized content with author management
- **Livewire Rendering** - dynamic, interactive page rendering
- **SEO Optimized** - meta tags, Open Graph, Twitter Cards

### 🧭 **Advanced Navigation System**
- **Drag & Drop Menu Builder** - intuitive tree management with nested set architecture
- **Multi-Menu Support** - header, footer, sidebar menus with tab-based editing
- **SPA/Multi-Page Toggle** - site-level configuration for single-page or traditional navigation
- **Smart URL Generation** - automatic URL resolution based on site type (/ vs # prefixes)
- **Multiple Link Types** - page links, external URLs, custom URLs, headers, separators
- **Visual Preview** - see complete menu structure with hierarchy indicators

### 🎯 **Developer Experience**
- **TALL Stack Native** - leverages the full power of the stack
- **Auto-Discovery** - blocks, pages, and content automatically registered
- **Clean Architecture** - single source of truth, no duplication
- **Future-Ready** - designed for theming and extensibility

## 🚀 Quick Start

### Installation

1. **Clone and Install**
   ```bash
   git clone <repository-url> tallcms
   cd tallcms
   composer install
   npm install
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   php artisan migrate
   ```

4. **Build Assets**
   ```bash
   npm run build
   ```

5. **Create Admin User**
   ```bash
   php artisan make:filament-user
   ```

### Access Your CMS

- **Admin Panel**: `http://your-site.com/admin`
- **Frontend**: `http://your-site.com/`

## 🧱 Custom Blocks

### Creating Blocks

Use our custom Artisan command to generate blocks with the TallCMS pattern:

```bash
php artisan make:tallcms-block FeatureGrid
```

This creates:
- ✅ **Block Class**: Auto-configured with slideOver modal
- ✅ **Blade Template**: Hybrid styling (Tailwind + inline styles)
- ✅ **Auto-Discovery**: Immediately available in rich editor
- ✅ **Documentation**: Inline comments with customization guide

### Built-in Blocks

#### 🦸‍♂️ **Hero Block**
Perfect for landing page headers with:
- Dynamic heading and subheading
- Optional background image with overlay
- Call-to-action button
- Responsive gradient backgrounds

#### 📢 **Call-to-Action Block**
Conversion-optimized sections featuring:
- Multiple button styles (primary, secondary, success, warning, danger)
- Compelling title and description
- Customizable styling and colors
- Mobile-responsive design

#### 🖼️ **Image Gallery Block**
Sophisticated image galleries with:
- Multiple layouts (grid, masonry, carousel)
- Built-in lightbox with keyboard navigation
- Configurable image sizes
- Drag-and-drop reordering in admin

### Block Architecture

```php
class MyCustomBlock extends RichContentCustomBlock
{
    // Auto-discovered - no registration needed!
    
    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.my-custom-block', $config)->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.my-custom-block', $config)->render();
    }
}
```

### Hybrid Styling Approach

```blade
<div class="bg-gray-50 py-16 px-6" 
     style="background-color: #f9fafb; padding: 4rem 1.5rem;">
    <!-- Tailwind classes + inline styles = perfect compatibility -->
</div>
```

**Why hybrid?**
- **Tailwind classes**: Primary styling, responsiveness, hover states
- **Inline styles**: Guarantee admin previews always render correctly
- **Future-proof**: Theming systems can override either approach

## 📄 Content Management

### Pages
- **Hierarchical Structure** - organize content with parent/child relationships
- **SEO Optimization** - meta titles, descriptions, featured images
- **Custom Templates** - specify custom Blade templates per page
- **Publication Control** - draft/published states with scheduled publishing

### Posts/Articles
- **Category Management** - organize posts with multiple categories
- **Author Assignment** - link posts to specific users
- **Featured Articles** - highlight important content
- **Rich Content** - full custom block support

### Homepage Management
Set any page as your homepage:

1. Edit any page in admin
2. Toggle "Set as Homepage" in Settings tab
3. System automatically handles routing
4. Visitors to `/` see your designated homepage

## 🏗️ Architecture

### TALL Stack Foundation
- **Tailwind CSS**: Utility-first styling with responsive design
- **Alpine.js**: Lightweight JavaScript for interactions
- **Laravel 12**: Robust backend with modern PHP features
- **Livewire**: Dynamic frontend without complex JavaScript

### File Structure
```
app/
├── Filament/
│   ├── Forms/Components/RichEditor/RichContentCustomBlocks/  # Custom blocks
│   └── Resources/                                           # Admin resources
├── Http/Controllers/                                        # Page controllers
├── Livewire/                                               # Page renderers
├── Models/                                                 # CMS models
└── Services/                                               # Discovery & utilities

resources/views/
├── cms/blocks/                                             # Block templates
├── layouts/                                                # CMS layouts
└── livewire/                                              # Component views
```

### Key Services
- **`CustomBlockDiscoveryService`**: Auto-discovers and registers blocks
- **`MergeTagService`**: Processes dynamic content tags
- **`CmsPageRenderer`**: Livewire component for page rendering

## 🎨 Theming & Customization

### Current Approach
TallCMS uses **hybrid styling** that's ready for future theming:

- **Base styling**: Tailwind CSS utilities
- **Compatibility**: Inline styles ensure admin previews work
- **Customizable**: Override classes or styles as needed

### Future Theming
The architecture supports future theme systems that can:
- Override Tailwind classes via configuration
- Replace entire block templates
- Customize colors, fonts, and layouts
- Maintain backward compatibility

## 🔧 Advanced Features

### Merge Tags
Insert dynamic content with merge tags:

```blade
Welcome to {{site_name}}! Today is {{current_date}}.
This page: {{page_title}} by {{page_author}}.
```

Available tags:
- `{{site_name}}`, `{{current_year}}`, `{{current_date}}`
- `{{page_title}}`, `{{page_url}}`, `{{page_author}}`
- `{{post_title}}`, `{{post_author}}`, `{{post_categories}}`
- `{{contact_email}}`, `{{company_name}}`

### Auto-Discovery System
- **Zero Configuration**: Blocks work immediately after creation
- **Performance Optimized**: Results cached for speed
- **Error Resilient**: Gracefully handles invalid files
- **Development Friendly**: No forgotten registrations

### SEO Optimization
- **Meta Tags**: Title, description, keywords
- **Open Graph**: Facebook, LinkedIn sharing
- **Twitter Cards**: Twitter sharing optimization
- **Featured Images**: Social media thumbnails
- **Clean URLs**: SEO-friendly page slugs

## 🛠️ Development

### Creating Custom Blocks
1. **Generate**: `php artisan make:tallcms-block MyBlock`
2. **Customize**: Edit the generated class and template
3. **Use**: Block appears immediately in rich editor

### Extending the System
- **Custom Merge Tags**: Extend `MergeTagService`
- **New Block Types**: Follow the established patterns
- **Custom Layouts**: Create new Blade layouts
- **API Integration**: Add external data sources

### Best Practices
- **Single Responsibility**: Each block does one thing well
- **Hybrid Styling**: Always include both Tailwind classes and inline styles
- **Responsive Design**: Test blocks on all device sizes
- **Performance**: Optimize images and assets

## 📚 Documentation

- **[Custom Block Styling Guide](docs/CUSTOM_BLOCK_STYLING.md)**: Comprehensive styling documentation
- **Block Examples**: Study existing blocks for patterns
- **Inline Documentation**: Generated blocks include usage guides

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Development Setup
```bash
# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run dev
```

## 🔮 Roadmap

- [ ] **Theme System**: Complete theming architecture
- [ ] **Block Library**: Expanded collection of pre-built blocks
- [ ] **Multi-language**: Internationalization support
- [ ] **Media Manager**: Advanced file management
- [ ] **Form Builder**: Dynamic form creation blocks
- [ ] **E-commerce Blocks**: Shopping cart, product displays
- [ ] **Analytics**: Built-in performance tracking

## 🙏 Third-Party Plugins & Attribution

TallCMS is built with love and powered by these amazing open-source packages:

### 📦 **Core Dependencies**
- **[Filament v4](https://filamentphp.com/)** - Modern admin panel framework
- **[Laravel Framework](https://laravel.com/)** - The PHP framework for web artisans
- **[Livewire](https://laravel-livewire.com/)** - Dynamic Laravel frontend framework
- **[Tailwind CSS](https://tailwindcss.com/)** - Utility-first CSS framework
- **[Alpine.js](https://alpinejs.dev/)** - Lightweight JavaScript framework

### 🌲 **Navigation & Tree Management**
- **[wsmallnews/filament-nestedset](https://github.com/wsmallnews/filament-nestedset)** - Filament nested set plugin for drag-and-drop tree management
- **[kalnoy/laravel-nestedset](https://github.com/lazychaser/laravel-nestedset)** - Laravel nested set implementation (used by filament-nestedset)

### 🎨 **Rich Editor & Content**
- **[FilamentTiptapEditor](https://filamentphp.com/plugins/awcodes-tiptap-editor)** - Rich text editor for Filament
- **[TipTap](https://tiptap.dev/)** - Headless rich text editor

### 🔐 **Security & Permissions**
- **[Filament Shield](https://github.com/bezhanSalleh/filament-shield)** by Bezhan Salleh - Comprehensive role-based permission system for Filament
- **[Spatie Laravel Permission](https://github.com/spatie/laravel-permission)** - Laravel package for managing user permissions and roles (used by Shield)

### 🔧 **Development & Quality**
- **[Laravel Pint](https://github.com/laravel/pint)** - Code style fixer
- **[Laravel Sail](https://laravel.com/docs/sail)** - Docker development environment
- **[PHPUnit](https://phpunit.de/)** - Testing framework

### 🎯 **Special Thanks**
- **[wsmallnews](https://github.com/wsmallnews)** for creating the excellent Filament nested set plugin that powers our navigation system
- **[Bezhan Salleh](https://github.com/bezhanSalleh)** for developing Filament Shield, the robust permission system that secures our CMS
- **[Filament Team](https://filamentphp.com/about)** for building an incredible admin framework
- **Laravel Community** for continuous innovation and support

---

## 📄 License

TallCMS is open-sourced software licensed under the [MIT license](LICENSE).

---

**Built with ❤️ using the TALL stack**

*Tailwind CSS • Alpine.js • Laravel • Livewire • Filament*

### 🌟 **Special Recognition**
*This project demonstrates the power of the open-source ecosystem. Every plugin and package listed above contributes to making TallCMS possible.*