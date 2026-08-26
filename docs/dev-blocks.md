---
title: "Block Development"
slug: "block-development"
audience: "developer"
category: "developers"
order: 30
time: 20
prerequisites:
  - "installation"
---

# Block Development Guide

This guide covers creating custom blocks for the TallCMS Rich Editor. Blocks are reusable content components that content editors can insert into pages and posts.

## Quick Start

Generate a new block using the Artisan command:

```bash
php artisan make:tallcms-block "product showcase"
```

This creates:
- **Block class**: `app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/ProductShowcaseBlock.php`
- **Template**: `resources/views/cms/blocks/product-showcase.blade.php`

The block is auto-discovered and immediately available in the Rich Editor.

## Block Architecture

### File Structure

```
app/Filament/Forms/Components/RichEditor/RichContentCustomBlocks/
└── MyCustomBlock.php           # Block class with form schema and rendering

resources/views/cms/blocks/
└── my-custom.blade.php         # Blade template for frontend display
```

### Block Class Anatomy

```php
<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class ProductShowcaseBlock extends RichContentCustomBlock
{
    use HasBlockMetadata;

    // Required: Unique identifier
    public static function getId(): string
    {
        return 'product_showcase';
    }

    // Required: Display name in block panel
    public static function getLabel(): string
    {
        return 'Product Showcase';
    }

    // Block panel metadata
    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getDescription(): string
    {
        return 'Showcase a product with image and details';
    }

    public static function getKeywords(): array
    {
        return ['product', 'showcase', 'feature'];
    }

    // Required: Configure the editor modal form
    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure the product showcase')
            ->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(500),
            ])->slideOver();
    }

    // Required: Render preview in admin editor
    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.product-showcase', [
            'title' => $config['title'] ?? 'Sample Product',
            'description' => $config['description'] ?? 'Product description',
        ])->render();
    }

    // Required: Render on frontend
    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.product-showcase', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}
```

## Block Metadata (HasBlockMetadata)

| Method | Return Type | Default | Description |
|--------|-------------|---------|-------------|
| `getCategory()` | `string` | `'content'` | Category for grouping |
| `getIcon()` | `string` | `'heroicon-o-cube'` | Heroicon name |
| `getDescription()` | `string` | `''` | Brief description |
| `getKeywords()` | `array` | `[]` | Search terms |
| `getSortPriority()` | `int` | `50` | Sort order (lower = first) |

### Categories

| Key | Label | Use For |
|-----|-------|---------|
| `content` | Content | Text, CTAs, pricing |
| `media` | Media | Images, galleries |
| `social-proof` | Social Proof | Testimonials, team |
| `dynamic` | Dynamic | Posts, FAQ |
| `forms` | Forms | Contact forms |
| `other` | Other | Uncategorized |

## Form Schema

### Basic Components

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;

public static function configureEditorAction(Action $action): Action
{
    return $action
        ->modalWidth('4xl')
        ->schema([
            TextInput::make('title')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->rows(3),

            Select::make('style')
                ->options([
                    'default' => 'Default',
                    'highlight' => 'Highlighted',
                ])
                ->default('default'),

            Toggle::make('show_button')
                ->default(true),

            FileUpload::make('image')
                ->image()
                ->disk(cms_media_disk())
                ->directory('blocks/my-block'),
        ])->slideOver();
}
```

### Repeater for Multiple Items

```php
use Filament\Forms\Components\Repeater;

Repeater::make('items')
    ->schema([
        TextInput::make('title')->required(),
        Textarea::make('description'),
        FileUpload::make('icon')->image(),
    ])
    ->defaultItems(3)
    ->minItems(1)
    ->maxItems(6)
    ->collapsible()
    ->reorderableWithButtons()
```

## Template Development

### Basic Template

```blade
{{-- resources/views/cms/blocks/product-showcase.blade.php --}}

<section class="py-12 px-4">
    <div class="max-w-4xl mx-auto">
        @if($title)
            <h2 class="text-3xl font-bold mb-4 text-base-content">
                {{ $title }}
            </h2>
        @endif

        @if($description)
            <p class="text-lg text-base-content/80">
                {{ $description }}
            </p>
        @endif
    </div>
</section>
```

### DaisyUI Integration

```blade
<a href="{{ $url }}" class="btn btn-primary btn-lg">
    {{ $button_text }}
</a>

<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">{{ $title }}</h2>
        <p>{{ $description }}</p>
    </div>
</div>
```

### Image Handling

```blade
@if($image)
    <img
        src="{{ Storage::disk(cms_media_disk())->url($image) }}"
        alt="{{ $title }}"
        class="w-full h-auto rounded-lg"
        loading="lazy"
    >
@endif
```

## Advanced Patterns

### Preview vs Frontend Rendering

```php
public static function toPreviewHtml(array $config): string
{
    // Provide sample data for preview
    return static::renderBlock(array_merge($config, [
        'title' => $config['title'] ?? 'Sample Title',
    ]), isPreview: true);
}

public static function toHtml(array $config, array $data): string
{
    // Frontend uses actual data
    return static::renderBlock($config, isPreview: false);
}

protected static function renderBlock(array $config, bool $isPreview = false): string
{
    return view('cms.blocks.my-block', [
        'isPreview' => $isPreview,
        'title' => $config['title'] ?? '',
    ])->render();
}
```

### Link Resolution

```php
use TallCms\Cms\Services\BlockLinkResolver;

$buttonUrl = BlockLinkResolver::resolveButtonUrl($config, 'button');
```

## Core Blocks Reference

TallCMS includes 19 built-in blocks:

| Block | ID | Category |
|-------|----|----------|
| Hero | `hero` | Content |
| Content | `content_block` | Content |
| Split | `split` | Content |
| Call to Action | `call_to_action` | Content |
| Features | `features` | Content |
| Pricing | `pricing` | Content |
| Divider | `divider` | Content |
| Media Gallery | `image_gallery` | Media |
| Document List | `document_list` | Media |
| Parallax | `parallax` | Media |
| Testimonials | `testimonials` | Social Proof |
| Team | `team` | Social Proof |
| Logos | `logos` | Social Proof |
| Stats | `stats` | Social Proof |
| Posts | `posts` | Dynamic |
| FAQ | `faq` | Dynamic |
| How To | `how-to` | Dynamic |
| Timeline | `timeline` | Dynamic |
| Contact Form | `contact_form` | Forms |

## Testing Blocks

### Manual Testing Checklist

1. **Admin Preview**: Insert block in editor, verify preview
2. **Form Validation**: Test required fields, max lengths
3. **Frontend Display**: Publish page, verify rendering
4. **Responsive Design**: Test mobile, tablet, desktop
5. **Dark Mode**: Verify styling in both modes

### Common Issues

| Issue | Solution |
|-------|----------|
| Block not appearing | Verify class extends `RichContentCustomBlock` |
| Preview not updating | Check `toPreviewHtml()` returns valid HTML |
| Styles not applying | Run `npm run build`, check class names |
| Images not loading | Verify `cms_media_disk()` and file path |

---

## Next Steps

- [Block styling](block-styling)
- [Theme development](themes)
- [Rich editor reference](rich-editor)
