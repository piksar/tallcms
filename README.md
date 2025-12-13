# TallCMS

A modern Content Management System built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire) with Filament v4 admin panel.

Built by Vibe Coding, co-developed with Claude.ai, and code reviewed by Codex.

## 📋 System Requirements

- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Extensions**: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo, GD
- **Composer**: Only required for dependency updates (pre-included in CodeCanyon version)
- **Node.js**: Only required for asset compilation (pre-compiled in CodeCanyon version)

## ✨ Features

- **🌐 Web Installer** with WordPress-style setup wizard
- **Rich Content Editor** with custom blocks and merge tags
- **Hierarchical Pages & Posts** with SEO optimization
- **Drag & Drop Menu Builder** with nested navigation
- **Role-Based Permissions** (Super Admin, Administrator, Editor, Author)
- **Custom Block System** with auto-discovery and hybrid styling
- **Responsive Admin Panel** with device preview modes
- **Smart Homepage Management** and maintenance mode
- **SPA/Multi-Page Toggle** for flexible site architecture

## 🚀 Installation

TallCMS offers **two installation methods** to suit different workflows:

### 🌐 Option 1: Web Installer (Recommended)

**Perfect for:** Production deployments, shared hosting, quick setup

#### 📦 CodeCanyon Version (Ready to Use)
If you downloaded from CodeCanyon, dependencies are pre-included:

1. **Extract and Launch**
   ```bash
   # Extract the downloaded zip file
   cd tallcms/
   php artisan serve
   ```
   Then visit: **http://localhost:8000** 
   
   The installer will automatically redirect you to `/install` if setup is needed.

#### 🛠️ GitHub/Development Version
If you cloned from GitHub or want fresh dependencies:

1. **Clone and Install Dependencies**
   ```bash
   git clone <repository> <folder>
   cd <folder>
   composer install
   npm install
   npm run build
   ```

2. **Launch Web Installer**
   ```bash
   php artisan serve
   ```
   Then visit: **http://localhost:8000** 
   
   The installer will automatically redirect you to `/install` if setup is needed.

3. **Follow the Setup Wizard**
   - ✅ **Environment Check**: Verifies PHP extensions and permissions
   - ✅ **Database Configuration**: Test connection and configure settings
   - ✅ **Admin User Creation**: Set up your first super admin
   - ✅ **Permissions & Roles**: Automatically configures the entire permission system

4. **Access Your CMS**
   - **Frontend**: http://your-domain.com
   - **Admin Panel**: http://your-domain.com/admin

---

### ⚡ Option 2: Command Line (Developer)

**Perfect for:** Local development, automated deployments, advanced users

1. **Clone and Install Dependencies**
   ```bash
   git clone <repository> <folder>
   cd <folder>
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   # Configure your database in .env
   php artisan migrate
   ```

4. **Build Assets**
   ```bash
   npm run build
   ```

5. **TallCMS Setup**
   ```bash
   php artisan tallcms:setup
   ```

6. **Access Your CMS**
   - **Frontend**: http://your-domain.com
   - **Admin Panel**: http://your-domain.com/admin

### 🔧 Advanced Setup Options

```bash
# Force re-run setup (useful for development)
php artisan tallcms:setup --force

# Non-interactive setup (for automation)
php artisan tallcms:setup --force --name="Admin" --email="admin@example.com" --password="password123" --no-interaction

# Generate only permissions (if needed)
php artisan shield:generate --all --panel=admin --option=policies_and_permissions
```

### 🔄 Updating Dependencies (CodeCanyon Version)

If you want to update to the latest package versions:

```bash
# Update PHP packages
composer update

# Update Node packages and rebuild assets
npm update
npm run build

# Clear caches after updates
php artisan config:clear
php artisan view:clear
```

**Note:** The CodeCanyon version includes tested dependency versions. Updates should be done carefully in a staging environment first.

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

### Web Installer Issues

**"Installation is already complete" error**
- Check if `installer.lock` file exists in project root - delete if needed
- Verify `INSTALLER_ENABLED=false` in `.env` - change to `true` if needed
- Clear cache: `php artisan config:clear`

**CodeCanyon Version: Missing dependencies error**
- Dependencies are pre-included; if missing, re-extract the original zip file
- For fresh dependencies, run: `composer install && npm install && npm run build`
- Ensure proper file permissions after extraction

**"Database connection failed"**
- Test connection manually in web installer before proceeding
- Verify database credentials and ensure database exists
- Check database server is running and accessible

**"Required field validation errors"**
- Ensure passwords match in admin user section
- Test database connection successfully before installation
- All required fields must be filled

### General Issues

**"Cannot access admin panel"**
- Complete installation via web installer at `/install`
- Or run `php artisan tallcms:setup` via command line
- Check that your user has an active role

**"No permissions showing"**  
- Re-run web installer or use `php artisan tallcms:setup --force`
- Generate permissions: `php artisan shield:generate --all --panel=admin --option=policies_and_permissions`

**"Permission denied" errors**
- Ensure web server has write permissions to project root (for installer.lock)
- Fallback: installer can use `.env` file if root directory not writable
- Check storage and cache directory permissions

**"Setup already completed"**
- Use `php artisan tallcms:setup --force` to force re-setup via command line
- Or temporarily set `INSTALLER_ENABLED=true` in `.env` to access web installer

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

**CodeCanyon Version**: Licensed under [Envato Market License](https://codecanyon.net/licenses/standard)
- Regular License: Single end product use
- Extended License: Products sold to end users

**GitHub Version**: Open-source under [MIT License](https://opensource.org/licenses/MIT)

For commercial projects, we recommend the CodeCanyon version for legal compliance and included support.

### Third-Party Licenses
TallCMS includes open-source packages (Laravel, Filament, etc.) that retain their original licenses. All third-party components are compatible with commercial use. See `vendor/` directory for individual package licenses.

**Built with ❤️ using the TALL stack**
