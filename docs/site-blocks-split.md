---
title: "Split Block"
slug: "block-split"
audience: "site-owner"
category: "blocks"
order: 18
---

# Split Block

> Two to four columns of images and rich text in one row on desktop, stacked on mobile.

---

## Overview

The Split block places **images and rich text side by side** — for example a circular logo beside a heading, paragraphs, and a list. It is a mid-page layout band, not a full-viewport Hero and not a page builder for nesting other blocks.

Columns stack below the large breakpoint. Use a named split (such as **33 / 67**) rather than free percentages.

---

## Fields

| Field | Description |
|-------|-------------|
| **Cells** | 2–4 columns. Default is image then rich text. |
| **Cell type** | **Image** or **Rich text** |
| **Image** | Upload (JPEG, PNG, or WebP). Alt text is required when an image is uploaded. |
| **Image shape** | None, rounded, or circle |
| **Image size** | S, M, L, or Fill. Circle defaults to M (centered); other shapes default to Fill. Below large screens S/M/L use a fraction of the column; from `lg` they fill the column up to a max width. |
| **Horizontal alignment** | Left, center, or right. Applies when the image is narrower than the cell. |
| **Image vertical alignment** | Row (inherit), top, center, or bottom. Overrides the row setting for that image. |
| **Rich text** | Headings, paragraphs, lists, and links. You cannot insert other CMS blocks inside a cell. |
| **Split** | Named ratios: 50/50, 33/67, 67/33, 25/75, 75/25, sidebar start/end. With three or four cells: equal columns or sidebar. Adding a third cell resets two-cell ratios to **equal**. |
| **Mobile stack order** | As-is or reverse |
| **Vertical alignment** | Top, center, or bottom. Default for every cell; an image can override it. |
| **Background / padding / width** | Same section chrome as other content blocks |

---

## When to use Split vs Hero

Use **Split** for a content band (logo + copy, photo + article). Use **Hero** for a page header with overlay, min-height, and call-to-action buttons.

---

## Examples

<!-- Examples will be added as blocks below -->
