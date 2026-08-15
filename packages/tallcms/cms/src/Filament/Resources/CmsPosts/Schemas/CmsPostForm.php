<?php

namespace TallCms\Cms\Filament\Resources\CmsPosts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;
use TallCms\Cms\Enums\ContentStatus;
use TallCms\Cms\Filament\Forms\Components\CmsRichEditor;
use TallCms\Cms\Livewire\RevisionHistory;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Models\CmsPost;
use TallCms\Cms\Rules\UniqueTranslatableSlug;
use TallCms\Cms\Rules\UserAwareUnique;
use TallCms\Cms\Services\CustomBlockDiscoveryService;
use TallCms\Cms\Services\LocaleRegistry;

class CmsPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('tallcms::fields.post_management'))
                    ->tabs([
                        Tabs\Tab::make(__('tallcms::fields.content'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')->label(__('tallcms::fields.title'))
                                            ->required(function ($livewire) {
                                                if (! tallcms_i18n_enabled()) {
                                                    return true;
                                                }
                                                // Require title for default locale when i18n enabled
                                                $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                                                $defaultLocale = app(LocaleRegistry::class)->getDefaultLocale();

                                                return $activeLocale === $defaultLocale;
                                            })
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))
                                            )
                                            ->columnSpan(1),

                                        TextInput::make('slug')->label(__('tallcms::fields.slug'))
                                            ->required(function ($livewire) {
                                                if (! tallcms_i18n_enabled()) {
                                                    return true;
                                                }
                                                // Require slug for default locale when i18n enabled
                                                $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                                                $defaultLocale = app(LocaleRegistry::class)->getDefaultLocale();

                                                return $activeLocale === $defaultLocale;
                                            })
                                            ->maxLength(255)
                                            ->rules(function (?CmsPost $record, $livewire) {
                                                $rules = ['alpha_dash'];

                                                if (tallcms_i18n_enabled()) {
                                                    // Block locale codes as slugs
                                                    $reserved = app(LocaleRegistry::class)->getReservedSlugs();
                                                    $rules[] = 'not_in:'.implode(',', $reserved);

                                                    // Unique per locale
                                                    $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                                                    $rules[] = new UniqueTranslatableSlug(
                                                        table: 'tallcms_posts',
                                                        column: 'slug',
                                                        locale: $activeLocale,
                                                        ignoreId: $record?->id
                                                    );
                                                } else {
                                                    // Site-aware unique constraint
                                                    $rules[] = UserAwareUnique::rule('tallcms_posts', 'slug', $record?->id);
                                                }

                                                return $rules;
                                            })
                                            ->validationMessages([
                                                'not_in' => __('tallcms::fields.slug_reserved_language_code'),
                                            ])
                                            ->helperText(__('tallcms::fields.help_used_in_the_url'))
                                            ->columnSpan(1),
                                    ]),

                                Textarea::make('excerpt')
                                    ->label(__('tallcms::fields.excerpt'))
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->helperText(__('tallcms::fields.help_brief_description_post_listings'))
                                    ->columnSpanFull(),

                                CmsRichEditor::make('content')->label(__('tallcms::fields.content'))
                                    ->columnSpanFull()
                                    ->activePanel('customBlocks')
                                    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
                                    ->extraInputAttributes([
                                        'style' => 'min-height: 40rem;',
                                    ])
                                    ->floatingToolbars([
                                        'paragraph' => [
                                            'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript',
                                        ],
                                        'heading' => [
                                            'h1', 'h2', 'h3',
                                        ],
                                        'table' => [
                                            'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                                            'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                                            'tableMergeCells', 'tableSplitCell',
                                            'tableToggleHeaderRow', 'tableToggleHeaderCell',
                                            'tableDelete',
                                        ],
                                    ])
                                    ->helperText(__('tallcms::fields.help_create_rich_post_content')),
                            ]),

                        Tabs\Tab::make(__('tallcms::fields.settings'))
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make(__('tallcms::fields.post_settings'))
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')->label(__('tallcms::fields.status'))
                                            ->options(function () {
                                                // When review workflow is disabled, all users can publish directly
                                                if (! tallcms_review_workflow_enabled()) {
                                                    return ContentStatus::directPublishOptions();
                                                }

                                                // With review workflow: approvers get all options, authors get draft only
                                                if (auth()->user()?->can('Approve:CmsPost')) {
                                                    return ContentStatus::editorOptions();
                                                }

                                                return ContentStatus::authorOptions();
                                            })
                                            ->required()
                                            ->default(ContentStatus::Draft->value)
                                            ->disabled(function (?CmsPost $record) {
                                                // Disable status change when pending and user can't approve
                                                if ($record?->isPending() && ! auth()->user()?->can('Approve:CmsPost')) {
                                                    return true;
                                                }

                                                return false;
                                            })
                                            ->helperText(function (?CmsPost $record) {
                                                if ($record?->wasRejected()) {
                                                    return __('tallcms::fields.help_content_rejected', ['reason' => $record->getRejectionReason()]);
                                                }
                                                if ($record?->isPending()) {
                                                    return __('tallcms::fields.help_content_pending_review');
                                                }

                                                return null;
                                            }),

                                        DateTimePicker::make('published_at')
                                            ->label(__('tallcms::fields.publish_date'))
                                            ->nullable()
                                            ->helperText(__('tallcms::fields.help_leave_empty_publish_schedule'))
                                            ->visible(fn () => ! tallcms_review_workflow_enabled() || auth()->user()?->can('Approve:CmsPost')),

                                        Select::make('author_id')
                                            ->label(__('tallcms::fields.author'))
                                            ->relationship(
                                                name: 'author',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('name'),
                                            )
                                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => $record->name)
                                            ->searchable()
                                            ->preload()
                                            ->default(auth()->id())
                                            ->required(),

                                        Toggle::make('is_featured')
                                            ->label(__('tallcms::fields.featured_post'))
                                            ->helperText(__('tallcms::fields.help_featured_posts_prominently')),
                                    ]),

                                Section::make(tallcms_label('categories', 'plural'))
                                    ->schema([
                                        Select::make('categories')->label(tallcms_label('categories', 'plural'))
                                            ->multiple()
                                            ->relationship('categories', 'name')
                                            ->options(function () {
                                                $query = CmsCategory::query();
                                                if (auth()->check() && ! auth()->user()->hasRole('super_admin')
                                                    && DbSchema::hasColumn('tallcms_categories', 'user_id')) {
                                                    $query->where('user_id', auth()->id());
                                                }

                                                return $query->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')->label(__('tallcms::fields.name'))
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))
                                                    ),
                                                TextInput::make('slug')->label(__('tallcms::fields.slug'))
                                                    ->required()
                                                    ->rules(function ($livewire) {
                                                        $rules = ['alpha_dash'];

                                                        if (tallcms_i18n_enabled()) {
                                                            // Block locale codes as slugs
                                                            $reserved = app(LocaleRegistry::class)->getReservedSlugs();
                                                            $rules[] = 'not_in:'.implode(',', $reserved);

                                                            // Unique per locale (use livewire's activeLocale or app locale)
                                                            $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                                                            $rules[] = new UniqueTranslatableSlug(
                                                                table: 'tallcms_categories',
                                                                column: 'slug',
                                                                locale: $activeLocale
                                                            );
                                                        } else {
                                                            // Site-aware unique constraint
                                                            $rules[] = UserAwareUnique::rule('tallcms_categories', 'slug');
                                                        }

                                                        return $rules;
                                                    })
                                                    ->validationMessages([
                                                        'not_in' => __('tallcms::fields.slug_reserved_language_code'),
                                                    ]),
                                                Textarea::make('description')->label(__('tallcms::fields.description'))
                                                    ->rows(2),
                                            ])
                                            ->helperText(__('tallcms::fields.help_select_or_create_categories_for_post', ['categories' => tallcms_label('categories', 'plural')])),
                                    ]),
                            ]),

                        Tabs\Tab::make(__('tallcms::fields.seo'))
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make(__('tallcms::fields.search_engine_optimization'))
                                    ->description(__('tallcms::fields.help_optimize_post_seo'))
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label(__('tallcms::fields.meta_title'))
                                            ->maxLength(60)
                                            ->helperText(__('tallcms::fields.help_meta_title_post')),

                                        Textarea::make('meta_description')
                                            ->label(__('tallcms::fields.meta_description'))
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText(__('tallcms::fields.help_meta_description_post')),

                                        FileUpload::make('featured_image')
                                            ->label(__('tallcms::fields.featured_image'))
                                            ->image()
                                            ->directory('cms/posts/featured-images')
                                            ->disk(\cms_media_disk())
                                            ->visibility(\cms_media_visibility())
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3',
                                                '1:1',
                                                '1.91:1', // Facebook recommended
                                                '2:1',    // Twitter header
                                            ])
                                            ->helperText(__('tallcms::fields.help_featured_image_post')),
                                    ]),
                            ]),

                        Tabs\Tab::make(__('tallcms::fields.attribution'))
                            ->icon('heroicon-o-shield-check')
                            ->visible(fn () => DbSchema::hasColumn('tallcms_posts', 'last_reviewed_at'))
                            ->schema([
                                Section::make(__('tallcms::fields.content_review'))
                                    ->description(__('tallcms::fields.help_track_content_reviewed'))
                                    ->schema([
                                        Placeholder::make('last_reviewed_display')
                                            ->label(__('tallcms::fields.last_reviewed'))
                                            ->content(fn (?CmsPost $record) => $record?->last_reviewed_at
                                                ? $record->last_reviewed_at->format('F j, Y \a\t g:i A')
                                                : __('tallcms::fields.never_reviewed')),

                                        Placeholder::make('reviewed_by_display')
                                            ->label(__('tallcms::fields.reviewed_by'))
                                            ->content(fn (?CmsPost $record) => $record?->reviewer?->name ?? __('tallcms::fields.not_yet_reviewed')),
                                    ])
                                    ->columns(2),

                                Section::make(__('tallcms::fields.expert_reviewer'))
                                    ->description(__('tallcms::fields.help_optional_expert_reviewer'))
                                    ->schema([
                                        TextInput::make('expert_reviewer_name')
                                            ->label(__('tallcms::fields.reviewer_name'))
                                            ->maxLength(255)
                                            ->placeholder(__('tallcms::fields.placeholder_expert_name')),

                                        TextInput::make('expert_reviewer_title')
                                            ->label(__('tallcms::fields.reviewer_title_credentials'))
                                            ->maxLength(255)
                                            ->placeholder(__('tallcms::fields.placeholder_expert_title')),

                                        TextInput::make('expert_reviewer_url')
                                            ->label(__('tallcms::fields.reviewer_url'))
                                            ->url()
                                            ->maxLength(500)
                                            ->placeholder('https://...'),
                                    ])
                                    ->columns(3)
                                    ->collapsible(),

                                Section::make(__('tallcms::fields.citation_sources'))
                                    ->description(__('tallcms::fields.help_citation_sources'))
                                    ->schema([
                                        Repeater::make('sources')
                                            ->label('')
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label(__('tallcms::fields.source_title'))
                                                    ->required()
                                                    ->maxLength(255),

                                                TextInput::make('url')
                                                    ->label(__('tallcms::fields.source_url'))
                                                    ->url()
                                                    ->required()
                                                    ->maxLength(500),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->maxItems(20)
                                            ->collapsible()
                                            ->reorderable()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->addActionLabel(__('tallcms::fields.add_source')),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make(__('tallcms::fields.revisions'))
                            ->icon('heroicon-o-clock')
                            ->visible(fn (?CmsPost $record) => $record !== null && auth()->user()?->can('ViewRevisions:CmsPost'))
                            ->schema([
                                Livewire::make(RevisionHistory::class)
                                    ->lazy()
                                    ->data(fn (?CmsPost $record) => ['record' => $record]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
