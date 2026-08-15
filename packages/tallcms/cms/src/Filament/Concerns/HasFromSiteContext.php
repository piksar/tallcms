<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Concerns;

use Filament\Actions\Action;
use TallCms\Cms\Filament\Resources\SiteResource\SiteResource as CoreSiteResource;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\Site;

/**
 * Preserves site navigation context across the CmsPage edit/create flow.
 *
 * When a user enters the page editor from a Site relation manager (Sites
 * → Pages → Edit), the receiving page captures `?from_site=<id>` at
 * mount() and threads it through the rest of the lifecycle as a Livewire
 * property. After save / delete the user is sent back to the originating
 * Site edit page instead of the global Pages index — preserving the
 * site-context the user was working in.
 *
 * The query param is read ONCE in mount(); afterward the captured
 * property is durable across Livewire `/livewire/update` round trips
 * (which don't carry the original query string). The trait stays inert
 * on single-site installs — its first guard checks
 * CmsPage::hasSiteIdColumn(), which is false when the multisite plugin
 * isn't installed.
 */
trait HasFromSiteContext
{
    /**
     * Captured at mount() from the `from_site` query param. Null when not
     * provided, when the column doesn't exist (single-site install), or
     * when the value can't be parsed as a positive integer.
     */
    public ?int $fromSiteId = null;

    protected function captureFromSite(): void
    {
        if (! CmsPage::hasSiteIdColumn()) {
            return;
        }

        $param = request()->query('from_site');
        if (is_numeric($param) && (int) $param > 0) {
            $this->fromSiteId = (int) $param;
        }
    }

    /**
     * Returns the validated from_site id when navigation context is real,
     * else null. Validation prefers the loaded record's site_id (post-save
     * reality) and falls back to a CreateCmsPage's $ownerSiteId before the
     * record is persisted, so the create-page back-action can render
     * before the user clicks Save.
     */
    protected function pendingFromSiteId(): ?int
    {
        if ($this->fromSiteId === null) {
            return null;
        }

        // Local safe read — Filament's CreateRecord may not initialise the
        // typed $record property until after fill, so reading it directly
        // could throw "Typed property must not be accessed before
        // initialization." The null-safe coalesce sidesteps that.
        $record = $this->record ?? null;

        if ($record?->site_id !== null) {
            return (int) $record->site_id === $this->fromSiteId
                ? $this->fromSiteId
                : null;
        }

        // CreateCmsPage path before save: $record is unsaved (no site_id
        // yet) but $ownerSiteId is already populated from ?site=N. The
        // trait reaches into $this->ownerSiteId by name; for EditCmsPage
        // (no such property) the null-coalesce falls through to null,
        // which is correct because Edit always has a record.
        $ownerSiteId = $this->ownerSiteId ?? null;

        return $ownerSiteId !== null && (int) $ownerSiteId === $this->fromSiteId
            ? $this->fromSiteId
            : null;
    }

    protected function fromSiteUrl(): ?string
    {
        $id = $this->pendingFromSiteId();
        if ($id === null) {
            return null;
        }

        // class_exists alone isn't enough — the class can autoload but its
        // routes might not be registered in the active panel (e.g. a host
        // that called $plugin->withoutSiteSettings(), or didn't include
        // the multisite plugin in its panel). The try/catch is the
        // graceful-degradation safety net: if a candidate's routes aren't
        // registered, getUrl() throws and we try the next.
        $candidates = [
            CoreSiteResource::class,
            'Tallcms\\Multisite\\Filament\\Resources\\SiteResource\\SiteResource',
        ];

        foreach ($candidates as $resource) {
            if (! class_exists($resource)) {
                continue;
            }
            try {
                return $resource::getUrl('edit', ['record' => $id]);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    protected function fromSiteName(): ?string
    {
        $id = $this->pendingFromSiteId();
        if ($id === null) {
            return null;
        }

        return Site::find($id)?->name;
    }

    /**
     * Returns the "Back to site" header action when navigation context is
     * present, else null. Callers spread the return into getHeaderActions()
     * via array_filter() so a null silently drops out of the action list
     * on installs where context isn't applicable.
     */
    protected function getBackToSiteAction(): ?Action
    {
        $url = $this->fromSiteUrl();
        if ($url === null) {
            return null;
        }

        return Action::make('back_to_site')
            ->label(__('tallcms::fields.back_to_site'))
            ->icon('heroicon-m-arrow-left')
            ->color('gray')
            ->url($url);
    }
}
