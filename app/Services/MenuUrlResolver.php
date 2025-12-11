<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\TallcmsMenuItem;

class MenuUrlResolver
{
    public function resolve(TallcmsMenuItem $item): ?string
    {
        return match ($item->type) {
            'page' => $this->resolvePageUrl($item),
            'external' => $item->url,
            'custom' => $item->url,
            'separator', 'header' => null,
            default => null,
        };
    }

    protected function resolvePageUrl(TallcmsMenuItem $item): ?string
    {
        if (!$item->page) {
            return null;
        }

        $siteType = SiteSetting::get('site_type', 'multi-page');
        $page = $item->page;

        if ($siteType === 'single-page') {
            // In SPA mode, everything links to anchors on homepage
            return $page->is_homepage ? '#top' : '#' . $page->slug;
        }

        // Multi-page mode - homepage goes to root, others use their slug
        return $page->is_homepage ? '/' : '/' . $page->slug;
    }

    public function shouldOpenInNewTab(TallcmsMenuItem $item): bool
    {
        // External links default to new tab
        if ($item->type === 'external') {
            return $item->meta['open_in_new_tab'] ?? true;
        }

        return $item->meta['open_in_new_tab'] ?? false;
    }

    public function getTargetAttribute(TallcmsMenuItem $item): string
    {
        return $this->shouldOpenInNewTab($item) ? '_blank' : '_self';
    }
}