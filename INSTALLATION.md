# TallCMS Installation Guide

## 🚀 Quick Start

### 1. Clone and Install Dependencies
```bash
git clone <repository>
cd tall-filament-v4
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
- ✅ Create all necessary roles (Super Admin, Administrator, Editor, Author)
- ✅ Generate Filament Shield permissions for all resources  
- ✅ Assign appropriate permissions to each role automatically
- ✅ Create your first super admin user
- ✅ Configure the complete permission system

**Follow the interactive prompts to create your admin user.**

### 6. Access Your CMS
- **Frontend**: http://your-domain.com
- **Admin Panel**: http://your-domain.com/admin

---

## 🔐 First-Time Access Strategy

### The Problem
When you first install TallCMS, no users have admin permissions, creating a chicken-and-egg problem.

### The Solution
TallCMS uses a **multi-layered approach**:

1. **Automatic First User Access**: The first user created automatically gets admin panel access
2. **Setup Command**: `php artisan tallcms:setup` creates proper roles and permissions
3. **Fallback Access**: If no roles exist, the first user can always access the admin

### Setup Command Options
```bash
# Interactive setup (recommended)
php artisan tallcms:setup

# Force re-setup (if needed)
php artisan tallcms:setup --force
```

---

## 👥 User Roles & Permissions

| Role | Description | Default Permissions |
|------|-------------|---------------------|
| **Super Admin** | Complete system control | All permissions (Shield management, users, settings, content) |
| **Administrator** | Content + user management | Content, users, media, site settings (no Shield/system) |
| **Editor** | Full content management | Pages, posts, categories, menus, media (no users/settings) |
| **Author** | Basic content creation | View/create/edit content, view categories, basic media |

### 🔧 Permission Categories

**Content Management:**
- **Pages**: Create, edit, publish, delete pages
- **Posts**: Create, edit, publish, delete blog posts  
- **Categories**: Organize content with hierarchical categories
- **Menus**: Build site navigation structures

**System Management:**
- **Users**: Create and manage user accounts
- **Roles**: Modify permissions and role assignments (Super Admin only)
- **Settings**: Configure site settings and system preferences
- **Shield**: Manage security and permission system (Super Admin only)

---

## 🔧 Manual Role Assignment

After setup, you can assign roles via:

1. **Admin Panel**: Go to Users → Edit User → Roles & Permissions tab
2. **Code**: `$user->assignRole('editor');`
3. **Command Line**: Use the setup command with `--force`

---

## 🚨 Troubleshooting

### "Cannot access admin panel"
- Run `php artisan tallcms:setup` if you haven't already
- Check that your user has an active role
- Verify `is_active = true` on your user record

### "No permissions showing"
- Run `php artisan shield:generate --all`
- Check that roles have permissions assigned

### "Setup already completed"
- Use `php artisan tallcms:setup --force` to re-run setup

---

## 🎯 Next Steps

1. **Configure Site Settings**: Admin Panel → Site Settings
2. **Create Content**: Start with Pages, then Posts
3. **Set Up Navigation**: Create menus for your site
4. **Add Users**: Invite team members with appropriate roles
5. **Customize**: Modify themes, add custom blocks, etc.

---

Ready to build amazing content with TallCMS! 🎉