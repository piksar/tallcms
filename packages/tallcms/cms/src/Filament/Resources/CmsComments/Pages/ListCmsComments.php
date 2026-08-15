<?php

namespace TallCms\Cms\Filament\Resources\CmsComments\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use TallCms\Cms\Filament\Resources\CmsComments\CmsCommentResource;

class ListCmsComments extends ListRecords
{
    protected static string $resource = CmsCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - comments come from the frontend
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('tallcms::ui.filter_all')),
            'pending' => Tab::make(__('tallcms::ui.filter_pending'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getBadgeCount('pending'))
                ->badgeColor('warning'),
            'approved' => Tab::make(__('tallcms::ui.filter_approved'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make(__('tallcms::ui.filter_rejected'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            'spam' => Tab::make(__('tallcms::ui.filter_spam'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'spam')),
        ];
    }

    protected function getBadgeCount(string $status): ?int
    {
        try {
            $count = $this->getModel()::where('status', $status)->count();

            return $count > 0 ? $count : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
