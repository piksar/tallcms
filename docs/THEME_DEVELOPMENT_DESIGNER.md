# Theme Development (Design & Engineering Guide)

This guide is aimed at designers and frontend engineers building or refining themes for TallCMS. It explains the architecture, the design principles we use, and the practical steps to add or customize a theme while keeping admin previews and the frontend in sync.

## Core Architecture
- **Theme contract**: `App\Contracts\ThemeInterface` defines color palettes, text presets, button presets, padding presets, CSS variables, and metadata. All themes implement this.
- **Resolver**: `App\Services\ThemeResolver` binds the active theme (from `config/theme.php`) into the container. Use the global `theme()` helper to get it.
- **Presets everywhere**: Blades call `theme_colors()`, `theme_text_presets()`, `theme_button_presets()`, and `theme_padding_presets()` to stay theme-aware.
- **CSS custom properties**: Blocks consume `--block-*` variables for headings, body text, links, etc. These are set per-block inline to avoid bleed between instances.
- **Shared block styles**: `resources/css/blocks.css` (also imported by the Filament theme) centralizes typography and element styling for reusable blocks. One source for both admin preview and frontend.
- **Tailwind scanning**: The Filament theme at `resources/css/filament/admin/theme.css` imports `../../blocks.css` and sets `@source` to `app/Filament/**/*` and `resources/views/cms/blocks/**/*` so block classes are kept in the admin build. Frontend includes `blocks.css` via `resources/css/app.css`.

## Design Principles
- **Theme-aware by default**: Never hard-code colors in blocks. Use custom properties (`--block-heading-color`, `--block-text-color`, `--block-link-color`, etc.) driven by the active theme presets.
- **Scope styles**: Keep custom properties on the block root to prevent leakage between multiple blocks on a page.
- **Respect hierarchy**: Blocks should avoid multiple `<h1>` elements. Default to `h2` for sections, with configurable heading levels when needed.
- **Fluid but predictable**: Use responsive padding/spacing via Tailwind utilities or established presets. Avoid mixing inline clamp spacing with utility classes.
- **Single source of truth**: If a style is reusable, put it in `blocks.css` rather than inline. Admin and frontend will both pick it up.
- **Contrast first**: Provide light/dark text presets to remain legible on varying backgrounds. Favor semantic tokens over ad-hoc colors.

## Adding a New Theme (Designer + Engineer)
1) **Create the theme class** implementing `ThemeInterface` (e.g., `App\Themes\MyTheme`):
   - Return full color palettes.
   - Define button presets (bg/text/hover/border).
   - Define text presets (heading/description + optional link/link_hover).
   - Provide padding presets and `getCssCustomProperties()` if you expose extra CSS vars.
   - Point `getCssFilePath()` to your built CSS (relative to `public/`).
2) **Register it** in `config/theme.php` under `available` and set `active` (or use `ACTIVE_THEME` env).
3) **Bind via resolver**: `ThemeResolver::bindTheme('my-theme')` happens automatically from config in `ThemeServiceProvider`.
4) **CSS variables**: In your build, ensure any new theme-level CSS variables are emitted (e.g., root-level `--color-*` or block-level `--block-*` if you add more surface area).
5) **Build assets**: Run `npm run build` to produce the theme CSS at the path returned by `getCssFilePath()`.
6) **Verify presets**: Check blades that use presets (hero, CTA, content block) to ensure your theme returns the keys they expect (`primary`, `secondary`, `inverse`, link/link_hover, etc.).

## Styling Blocks (Content, Hero, CTA)
- **Colors**: Blocks set custom properties inline from theme presets, then rely on the shared `.content-block`, `.hero-block`, `.cta-block` styles in `blocks.css`.
- **Layout**: Use Tailwind utility classes for spacing/alignment; keep them in templates so the purger sees them (already covered by `@source` globs in admin theme and app.css for frontend).
- **Admin = Frontend**: Because both admin and frontend import the same `blocks.css`, previews match production styling. Avoid admin-only overrides unless necessary.

## Tailwind & Build Notes
- **Frontend**: `resources/css/app.css` imports `blocks.css` and defines `@source` entries that cover `resources/views/**`. Run `npm run dev` or `npm run build` to regenerate styles.
- **Admin theme**: `resources/css/filament/admin/theme.css` imports `../../blocks.css` and sets `@source` to include cms blocks. Run `php artisan filament:upgrade` (from composer post-autoload) and `npm run build` to refresh the admin CSS.
- **Purge safety**: If you add new block classes, make sure their templates live under the scanned paths. For dynamic class names, prefer explicit class strings instead of string concatenation so Tailwind can see them.

## QA Checklist
- Color audit: Switch themes and confirm headings, body text, and links follow the new palette in both admin preview and frontend.
- Typography hierarchy: Verify headings use the configured level and that only one `<h1>` exists per page.
- Spacing consistency: Compare padding/margins to presets; ensure no inline clamp spacing conflicts with utilities.
- Dark/light contrast: Check text readability on light and dark backgrounds if your theme supports both.
- Build outputs: After changes, run `npm run build` and confirm the generated CSS includes block styles for both admin and frontend.

## Common Pitfalls
- Missing Tailwind sources: If new block templates live outside `resources/views/cms/blocks`, update `@source` globs in both the frontend and admin themes.
- Inline color leakage: Do not set CSS variables in a global selector (e.g., `article { ... }`). Always scope to the block root via inline `style` or a unique class.
- Incomplete presets: If a theme omits `link`/`link_hover` in text presets, blocks will fall back to default blue. Supply those for consistent branding.
