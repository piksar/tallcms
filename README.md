# TallCMS

A modern Content Management System built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) with Filament v4 admin panel.

## ✨ Features

- **Rich Content Editor** with custom blocks and merge tags
- **Hierarchical Pages & Posts** with SEO optimization
- **Drag & Drop Menu Builder** with nested navigation
- **Role-Based Permissions** (Super Admin, Administrator, Editor, Author)
- **Custom Block System** with auto-discovery and hybrid styling
- **Responsive Admin Panel** with device preview modes
- **Smart Homepage Management** and maintenance mode
- **SPA/Multi-Page Toggle** for flexible site architecture

## 🚀 Installation

### 1. Clone and Install Dependencies
```bash
git clone <repository> <folder>
cd <folder>
composer install
npm install
```

### 2. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
# Configure your database in .env
php artisan migrate
```

### 4. Build Assets
```bash
npm run build
```

### 5. **TallCMS Setup (Required)**
```bash
php artisan tallcms:setup
```

This command will:
- ✅ Create all necessary roles and permissions
- ✅ Generate Filament Shield permissions for all resources  
- ✅ Create your first super admin user
- ✅ Configure the complete permission system

**Follow the interactive prompts to create your admin user.**

### 6. Access Your CMS
- **Frontend**: http://your-domain.com
- **Admin Panel**: http://your-domain.com/admin

---

## 👥 User Roles & Permissions

| Role | Description | Access Level |
|------|-------------|--------------|
| **Super Admin** | Complete system control | All permissions (80+) |
| **Administrator** | Content + user management | Content, users, settings (67) |
| **Editor** | Full content management | Pages, posts, categories, menus (55) |
| **Author** | Basic content creation | Create/edit own content (16) |

## 🧱 Custom Blocks

### Creating Blocks
```bash
php artisan make:tallcms-block FeatureGrid
```

### Built-in Blocks
- **Hero Block**: Landing page headers with CTAs
- **Call-to-Action Block**: Conversion-optimized sections  
- **Image Gallery Block**: Lightbox galleries with multiple layouts

### Hybrid Styling
```blade
<div class="bg-gray-50 py-16 px-6" 
     style="background-color: #f9fafb; padding: 4rem 1.5rem;">
    <!-- Tailwind classes + inline styles = perfect compatibility -->
</div>
```

## 🔧 Development Commands

```bash
# Development server with hot reload
composer dev

# Code formatting
php artisan pint

# Run tests
composer test

# Create custom blocks
php artisan make:tallcms-block BlockName
```

## 🚨 Troubleshooting

### "Cannot access admin panel"
- Run `php artisan tallcms:setup` if you haven't already
- Check that your user has an active role

### "No permissions showing"  
- Run `php artisan shield:generate --all`
- Use `php artisan tallcms:setup --force` to re-run setup

### "Setup already completed"
- Use `php artisan tallcms:setup --force` to force re-setup

## 🙏 Credits & Attribution

TallCMS is powered by these amazing open-source packages:

### Core Framework
- **[Filament v4](https://filamentphp.com/)** - Modern admin panel framework
- **[Laravel Framework](https://laravel.com/)** - The PHP framework for web artisans
- **[Livewire](https://laravel-livewire.com/)** - Dynamic Laravel frontend framework
- **[Tailwind CSS](https://tailwindcss.com/)** - Utility-first CSS framework
- **[Alpine.js](https://alpinejs.dev/)** - Lightweight JavaScript framework

### Plugins & Extensions
- **[Filament Shield](https://github.com/bezhanSalleh/filament-shield)** by Bezhan Salleh - Role-based permission system
- **[Spatie Laravel Permission](https://github.com/spatie/laravel-permission)** - Permission management
- **[wsmallnews/filament-nestedset](https://github.com/wsmallnews/filament-nestedset)** - Drag-and-drop tree management
- **[kalnoy/laravel-nestedset](https://github.com/lazychaser/laravel-nestedset)** - Laravel nested set implementation
- **[FilamentTiptapEditor](https://filamentphp.com/plugins/awcodes-tiptap-editor)** - Rich text editor for Filament

### Special Thanks
- **[Bezhan Salleh](https://github.com/bezhanSalleh)** for developing Filament Shield
- **[wsmallnews](https://github.com/wsmallnews)** for the excellent nested set plugin
- **[Filament Team](https://filamentphp.com/about)** for building an incredible admin framework
- **Laravel Community** for continuous innovation and support

---

## 📄 License

TallCMS is open-sourced software licensed under the [MIT license](LICENSE).

**Built with ❤️ using the TALL stack**