<?php

namespace TallCms\Cms\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use TallCms\Cms\Filament\Widgets\Concerns\HasMultisiteWidgetContext;

class ContentHealthWidget extends BaseWidget
{
    use HasMultisiteWidgetContext;

    protected static ?int $sort = 1;

    #[On('dashboard.site-changed')]
    public function onSiteChanged(): void
    {
        // Empty body — Livewire re-renders the widget on event receipt,
        // which re-runs getStats() against the new session value.
    }

    protected function getStats(): array
    {
        $siteId = $this->getMultisiteSiteId();

        $postQuery = DB::table('tallcms_posts')
            ->whereNull('deleted_at')
            ->where('status', 'published');

        if ($siteId && Schema::hasColumn('tallcms_posts', 'site_id')) {
            $postQuery->where('site_id', $siteId);
        }

        $publishedPosts = (clone $postQuery)->count();

        // Posts needing review: never reviewed OR reviewed > 6 months ago
        $staleThreshold = Carbon::now()->subMonths(6);
        $needsReview = 0;
        if (Schema::hasColumn('tallcms_posts', 'last_reviewed_at')) {
            $needsReview = (clone $postQuery)
                ->where(function ($q) use ($staleThreshold) {
                    $q->whereNull('last_reviewed_at')
                        ->orWhere('last_reviewed_at', '<', $staleThreshold);
                })
                ->count();
        }

        // Posts with missing meta description
        $missingMeta = (clone $postQuery)
            ->where(function ($q) {
                $q->whereNull('meta_description')
                    ->orWhere('meta_description', '');
            })
            ->count();

        // Posts missing featured image
        $missingImage = (clone $postQuery)
            ->where(function ($q) {
                $q->whereNull('featured_image')
                    ->orWhere('featured_image', '');
            })
            ->count();

        return [
            Stat::make(__('tallcms::widgets.content_health.published_posts'), $publishedPosts)
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make(__('tallcms::widgets.content_health.needs_review'), $needsReview)
                ->description(__('tallcms::widgets.content_health.needs_review_desc'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($needsReview > 0 ? 'danger' : 'success'),

            Stat::make(__('tallcms::widgets.content_health.missing_meta'), $missingMeta)
                ->description(__('tallcms::widgets.content_health.missing_image_also', ['count' => $missingImage]))
                ->descriptionIcon('heroicon-m-magnifying-glass')
                ->color($missingMeta > 0 ? 'warning' : 'success'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function getHeading(): ?string
    {
        $siteId = $this->getMultisiteSiteId();
        $siteName = $this->getMultisiteName($siteId);

        return $siteName
            ? __('tallcms::widgets.content_health.heading_site', ['site' => $siteName])
            : __('tallcms::widgets.content_health.heading');
    }
}

