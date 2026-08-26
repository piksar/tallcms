---
title: "Rich Editor Reference"
slug: "rich-editor"
audience: "all"
category: "reference"
order: 40
---

# CMS Rich Editor Reference

The CMS Rich Editor is an enhanced version of Filament's `RichEditor` component, designed to make managing long structured pages with many blocks fast and discoverable.

## Features

### Block insertion
- **Search**: Multi-word AND filtering across name, description, ID, and keywords
- **Result ranking**: Matches sort by quality (exact label > startsWith > contains > id > keywords) within each category
- **Recently used**: Last 5 inserted blocks pin to the top of the picker (per browser, persisted in `localStorage`)
- **Slash commands**: Type `/` anywhere in the editor for a Notion-style floating block picker; arrow keys + Enter to insert
- **Categories**: Blocks organized into collapsible groups
- **Icons**: Each block displays its icon

### Editor surface
- **Sticky side panel**: Block panel docks to viewport on long pages (≥1024px); offset configurable via `--tallcms-editor-sticky-offset` for hosts with their own sticky topbar
- **Outline tab**: Live, drag-reorderable list of every custom block in the document; click an entry to scroll-to, drag the handle to reorder
- **Per-block hover chrome**: Floating mini-toolbar on every custom block — drag handle, ↑, ↓, duplicate, collapse — fades in on hover/focus, calm at rest
- **Per-block collapse**: Fold any block's preview into a one-line summary card (header and chrome stay clickable)

### Media + integration
- **Media Library Browser**: Insert existing images without re-uploading
- **Auto Media Saving**: Attached files are saved to the Media Library
- **Dark Mode**: Full support via Filament-native styling
- **Backwards Compatible**: Works with existing blocks and plugin blocks

---

## Editor Surface

### Sticky panel

The block panel stays docked to the viewport at `min-width: 1024px`, so long documents don't push the picker off-screen. The chosen offset can be customized:

```css
.fi-fo-rich-editor {
    --tallcms-editor-sticky-offset: 4rem;  /* dock below your topbar */
}
```

Standalone sets this default in `resources/css/filament/admin/theme.css`. Plugin-mode hosts set it themselves if they have a sticky topbar.

### Side-panel tabs

The side panel exposes two modes:

| Tab | Use |
|-----|-----|
| **Blocks** | The picker (search, recently used, categories) |
| **Outline** | Ordered list of every customBlock in the document |

The Outline tab shows each block's icon, an extracted heading title (when the block's config has a `title` / `heading` / `headline` / `heading_text` / `name` field), and the block's type label. Click an entry to scroll the editor to that block; drag the handle to reorder.

### Per-block chrome

Hovering any custom block in the editor surfaces five buttons in the block header (in addition to Filament's existing edit / delete):

| Button | Action |
|--------|--------|
| Drag handle | Visual affordance for native drag-to-reorder (small predictable hit target) |
| ↑ | Move block up among customBlock siblings |
| ↓ | Move block down among customBlock siblings |
| Duplicate | Insert exact clone immediately after |
| Collapse | Toggle preview visibility — folds tall blocks into one-line summaries |

All reorder + duplicate operations transact in a single TipTap step, so undo (⌘Z) reverses them cleanly.

### Slash commands

Typing `/` after whitespace or at line start opens a floating block picker positioned at the cursor. As you type, results filter against the same `searchable` field used by the side panel. Arrow keys navigate, **Enter** or **Tab** inserts (the `/query` text is removed in the same transaction), **Escape** or click-away dismisses without inserting.

A default placeholder hints at the trigger on a fresh editor: *"Type / for blocks, or use the side panel"*. Override per field via `->placeholder()`.

---

## Block Categories

TallCMS includes 19 custom blocks in 5 categories:

| Category | Blocks | Icon |
|----------|--------|------|
| **Content** | Hero, Content, Split, CTA, Features, Pricing, Divider | `heroicon-o-document-text` |
| **Media** | Media Gallery, Document List, Parallax | `heroicon-o-photo` |
| **Social Proof** | Testimonials, Team, Logos, Stats | `heroicon-o-star` |
| **Dynamic** | Posts, FAQ, How To, Timeline | `heroicon-o-newspaper` |
| **Forms** | Contact Form | `heroicon-o-envelope` |

---

## Content Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **HeroBlock** | Full-width hero section with background | banner, header, landing |
| **ContentBlock** | Rich text content with title | article, text, prose |
| **SplitBlock** | Image and rich text side by side | split, columns, media, text |
| **CallToActionBlock** | Promotional section with buttons | cta, button, action |
| **FeaturesBlock** | Feature grid with icons | features, benefits, list |
| **PricingBlock** | Pricing table with plans | plans, pricing, tiers |
| **DividerBlock** | Decorative spacing element | separator, spacing, line |

## Media Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **ImageGalleryBlock** | Gallery with lightbox support | images, photos, gallery |
| **DocumentListBlock** | Downloadable documents from a collection | documents, files, downloads |
| **ParallaxBlock** | Parallax scrolling section | scroll, background, effect |

## Social Proof Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **TestimonialsBlock** | Customer testimonials | reviews, quotes, customers |
| **TeamBlock** | Team member profiles | team, staff, members |
| **LogosBlock** | Client/partner logos | clients, partners, brands |
| **StatsBlock** | Key metrics display | numbers, metrics, statistics |

## Dynamic Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **PostsBlock** | Blog post listing | blog, articles, posts |
| **FaqBlock** | FAQ accordion | faq, questions, answers |
| **HowToBlock** | Step-by-step instructions with HowTo schema | howto, steps, tutorial |
| **TimelineBlock** | Chronological events | history, events, chronology |

## Form Blocks

| Block | Description | Keywords |
|-------|-------------|----------|
| **ContactFormBlock** | Contact form | contact, email, form |

---

## Adding Metadata to Custom Blocks

Use the `HasBlockMetadata` trait:

```php
<?php

namespace App\Filament\Blocks;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class MyCustomBlock extends RichContentCustomBlock
{
    use HasBlockMetadata;

    public static function getId(): string
    {
        return 'my-custom-block';
    }

    public static function getLabel(): string
    {
        return 'My Custom Block';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getDescription(): string
    {
        return 'A custom block that does something amazing';
    }

    public static function getKeywords(): array
    {
        return ['custom', 'amazing', 'special'];
    }

    public static function getSortPriority(): int
    {
        return 25;  // Lower = appears first
    }
}
```

---

## Trait Methods

| Method | Return Type | Default | Description |
|--------|-------------|---------|-------------|
| `getCategory()` | `string` | `'content'` | Category key |
| `getIcon()` | `string` | `'heroicon-o-cube'` | Heroicon name |
| `getDescription()` | `string` | `''` | Brief description |
| `getKeywords()` | `array` | `[]` | Search terms |
| `getSortPriority()` | `int` | `50` | Sort order |

---

## Available Categories

| Key | Label | Use For |
|-----|-------|---------|
| `content` | Content | Text, headings, CTAs, pricing |
| `media` | Media | Images, videos, galleries |
| `social-proof` | Social Proof | Testimonials, team, logos |
| `dynamic` | Dynamic | Posts, FAQ, data-driven |
| `forms` | Forms | Contact forms, inputs |
| `other` | Other | Uncategorized blocks |

---

## Plugin Blocks

Plugin blocks are automatically discovered. Without `HasBlockMetadata`:
- Appear in "Other" category
- Use default cube icon
- Searchable by label and ID

---

## Adding a Custom Category

Edit `BlockCategoryRegistry.php`:

```php
public static function getCategories(): array
{
    return [
        // ... existing categories
        'ecommerce' => [
            'label' => 'E-Commerce',
            'icon' => 'heroicon-o-shopping-cart',
            'order' => 45,
        ],
    ];
}
```

---

## Media Library Integration

The editor integrates with the [Media Library](media) via two features:

### Insert from Media Library

Click the **photo** icon in the toolbar to open a searchable browser of existing images. Select an image, optionally edit the alt text, and insert it directly into the editor.

- Images are inserted with a reference to the Media Library record ID
- Alt text pre-fills from the media record but can be overridden
- The browser shows the 100 most recent images with client-side search

### File Attachments Saved to Media Library

When you attach a file using the **paperclip** icon, the file is automatically saved as a Media Library record:

- Alt text is inferred from the filename (e.g., `team-photo.jpg` → "Team Photo")
- Image optimization runs in the background
- The image node stores the media record ID, so URLs resolve from the Media Library at render time

### Architecture

The integration is implemented as a Filament `RichContentPlugin`:

```
src/Filament/Forms/Components/
├── Actions/
│   └── InsertMediaAction.php         # Modal action for browsing media
├── Plugins/
│   └── MediaLibraryPlugin.php        # Registers toolbar button and action
├── CmsRichEditor.php                 # Registers plugin, adds toolbar button
└── MediaLibraryFileAttachmentProvider.php  # Saves attachments as media records
```

| Class | Role |
|-------|------|
| `MediaLibraryPlugin` | Implements `RichContentPlugin`, provides the `insertMedia` tool and action |
| `InsertMediaAction` | Filament `Action` with modal schema, queries `TallcmsMedia`, runs `insertContent` editor command |
| `MediaLibraryFileAttachmentProvider` | Saves uploaded files as `TallcmsMedia` records, resolves media IDs to URLs |

---

## Using CmsRichEditor

The `CmsRichEditor` is automatically used in page and post forms. For custom resources:

```php
use TallCms\Cms\Filament\Forms\Components\CmsRichEditor;
use TallCms\Cms\Services\CustomBlockDiscoveryService;

CmsRichEditor::make('content')
    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
    ->activePanel('customBlocks')
```

---

## Architecture

```
packages/tallcms/cms/
├── src/
│   ├── Filament/
│   │   ├── Blocks/
│   │   │   └── Concerns/
│   │   │       └── HasBlockMetadata.php
│   │   └── Forms/
│   │       └── Components/
│   │           ├── Actions/
│   │           │   └── InsertMediaAction.php
│   │           ├── Plugins/
│   │           │   ├── BlockChromePlugin.php
│   │           │   └── MediaLibraryPlugin.php
│   │           ├── CmsRichEditor.php
│   │           └── MediaLibraryFileAttachmentProvider.php
│   └── Services/
│       ├── BlockCategoryRegistry.php
│       └── CustomBlockDiscoveryService.php
└── resources/
    ├── css/
    │   └── admin.css                   # editor surface styles (sticky, chrome, outline, slash)
    ├── js/
    │   └── block-chrome.js             # TipTap extension source
    ├── dist/
    │   ├── tallcms-admin.css           # built CSS shipped to plugin-mode installs
    │   └── block-chrome.js             # built TipTap extension (loaded on-request)
    └── views/
        └── filament/
            └── forms/
                └── components/
                    ├── cms-rich-editor.blade.php
                    └── media-library-picker.blade.php
```

---

## Key Classes

### CmsRichEditor

Extends Filament's `RichEditor`:
- `getGroupedBlocks()` — Returns blocks by category
- `getBlockCategories()` — Returns category definitions
- `getDefaultToolbarButtons()` — Adds `insertMedia` after `attachFiles`
- `isFilamentCompatible()` — Version check for fallback
- Registers `MediaLibraryPlugin` and `BlockChromePlugin` via `setUp()`
- Sets the default placeholder hint for slash commands

### BlockChromePlugin

Filament `RichContentPlugin` that ships the editor-surface TipTap extension via `getTipTapJsExtensions()`. The bundled JS (loaded on-request via `Js::make()->loadedOnRequest()`) registers three ProseMirror plugins inside one TipTap extension:

| Plugin | Role |
|--------|------|
| `BlockChromeView` | Injects per-block hover chrome (drag handle, ↑, ↓, duplicate, collapse) into each customBlock's header |
| `OutlineSyncView` | Emits `cms-block-outline-changed` events on doc change so the Outline tab can render; listens for `cms-block-action` for outline-driven scroll/move requests |
| `SlashCommandView` | Detects `/` triggers, renders the floating slash menu, dispatches `cms-slash-insert` on selection |

Three TipTap commands are registered on top-level customBlock nodes:

| Command | Signature | Purpose |
|---------|-----------|---------|
| `moveCustomBlockUp` | `(pos)` | Swap with previous top-level sibling |
| `moveCustomBlockDown` | `(pos)` | Swap with next top-level sibling |
| `moveCustomBlockTo` | `(fromPos, toIndex)` | Move to a specific slot among customBlock siblings (used by Outline drag-reorder) |
| `duplicateCustomBlock` | `(pos)` | Insert exact attrs+content clone immediately after |

All commands run in single transactions for clean undo, validate the node type at the position, and no-op cleanly at boundaries (top, bottom, non-customBlock positions).

### BlockCategoryRegistry

Static registry:
- Category keys, labels, icons, sort order
- Fallback icon constant

### CustomBlockDiscoveryService

Enhanced discovery:
- `getBlocksWithMetadata()` - Full metadata
- `getBlocksGroupedByCategory()` - Grouped output
- Icon validation
- Precomputed search strings (used by both the picker and slash menu)

---

## Filament Compatibility

Requires Filament v5.x. Automatic version detection:
- **Filament 5.x**: Full enhanced panel
- **Other versions**: Standard Filament block panel

---

## Next Steps

- [Block development](block-development)
- [Block styling](block-styling)
- [Using blocks](blocks)
