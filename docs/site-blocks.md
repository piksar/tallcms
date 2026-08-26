---
title: "Content Blocks"
slug: "blocks"
audience: "site-owner"
category: "site-management"
order: 20
---

# Content Blocks

> Reusable components for building rich, engaging pages.

---

## Overview

Blocks are reusable content components that you add to pages and posts. They provide structured layouts for common content patterns like heroes, pricing tables, and testimonials.

TallCMS includes 19 built-in blocks organized into categories, plus 9 additional Pro blocks available with a TallCMS Pro license.

---

## Adding Blocks

1. Edit a page or post
2. Click inside the content editor
3. Click the **+ Add Block** button (or type `/` to search)
4. Select a block from the picker
5. Fill in the block's fields in the modal
6. Click **Save** to add the block

### Block Picker

The block picker organizes blocks by category:
- **Content**: Hero, Content, Split, CTA, Features, Pricing, Divider
- **Media**: Media Gallery, Document List, Parallax
- **Social Proof**: Testimonials, Team, Logos, Stats
- **Dynamic**: Posts, FAQ, How To, Timeline
- **Forms**: Contact Form
- **Pro**: Accordion, Tabs, Table, Comparison, Before/After, Video, Counter, Code Snippet, Map

Use the search box to quickly find blocks by name or keyword.

---

## Block Reference

Click any block for detailed documentation and live examples.

### Content Blocks

| Block | Description |
|-------|-------------|
| [Hero](block-hero) | Full-width section for page headers and landing pages |
| [Content](block-content) | Rich text section with title and body |
| [Split](block-split) | Image and rich text side by side |
| [Call to Action](block-cta) | Promotional section to drive conversions |
| [Features](block-features) | Grid layout for product or service features |
| [Pricing](block-pricing) | Pricing table with plan comparison |
| [Divider](block-divider) | Visual separator between sections |

### Media Blocks

| Block | Description |
|-------|-------------|
| [Media Gallery](block-media-gallery) | Image and video gallery with lightbox |
| [Document List](block-document-list) | Downloadable documents from collections |
| [Parallax](block-parallax) | Full-width section with parallax effect |

### Social Proof Blocks

| Block | Description |
|-------|-------------|
| [Testimonials](block-testimonials) | Customer testimonials and reviews |
| [Team](block-team) | Team member profiles |
| [Logos](block-logos) | Client or partner logo showcase |
| [Stats](block-stats) | Key metrics and statistics |

### Dynamic Blocks

| Block | Description |
|-------|-------------|
| [Posts](block-posts) | Display blog posts from your site |
| [FAQ](block-faq) | Frequently asked questions accordion |
| [How To](block-how-to) | Step-by-step instructions with HowTo schema |
| [Timeline](block-timeline) | Chronological events or milestones |

### Form Blocks

| Block | Description |
|-------|-------------|
| [Contact Form](block-contact-form) | Customizable contact form |

### Pro Blocks

Pro blocks require a TallCMS Pro license.

| Block | Description |
|-------|-------------|
| [Accordion](block-pro-accordion) | Collapsible content sections for FAQs and lists |
| [Tabs](block-pro-tabs) | Tabbed content sections with rich text and icons |
| [Table](block-pro-table) | Data tables with headers, highlighting, and styling |
| [Comparison](block-pro-comparison) | Side-by-side feature comparison for plans or products |
| [Before/After](block-pro-before-after) | Interactive slider for image or text comparisons |
| [Video](block-pro-video) | Embed YouTube, Vimeo, or self-hosted videos |
| [Counter](block-pro-counter) | Animated number counters with scroll trigger |
| [Code Snippet](block-pro-code-snippet) | Syntax-highlighted code with copy button |
| [Map](block-pro-map) | Interactive maps from OpenStreetMap, Google, or Mapbox |

---

## Block Settings

Most blocks share common settings:

### Content Width

Controls how wide the block content appears:
- **Inherit from Page**: Uses the page's width setting
- **Narrow** (672px): Focused reading
- **Standard** (1152px): Default for most content
- **Wide** (1280px): Image-heavy layouts
- **Full Width**: Edge-to-edge

### Background Options

Many blocks support background customization:
- Solid colors (using theme palette)
- Images with overlay
- Gradient options

### Alignment

Control text and content alignment:
- Left, Center, Right
- Some blocks have vertical alignment too

---

## Tips for Using Blocks

**Start with Hero**
Most pages benefit from a Hero block at the top to set the tone.

**Alternate block types**
Vary your blocks to create visual interest. Don't stack multiple Content blocks without variety.

**Use consistent styling**
Stick to 2-3 background colors throughout your site for a cohesive look.

**Preview often**
Use the Preview button to see how blocks look on the frontend.

---

## Common Pitfalls

**"Block looks different in preview"**
Admin preview shows structure but may not match exact theme styling. Always check the frontend.

**"Images are blurry"**
Upload high-resolution images. Most blocks work best with images at least 1200px wide.

**"Block not saving"**
Ensure all required fields are filled. Required fields are marked with an asterisk.

---

## Next Steps

- [Block development guide](block-development) - Create custom blocks
- [Managing media](media)
- [Page settings reference](page-settings)
