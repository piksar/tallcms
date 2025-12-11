<?php

namespace App\Filament\Resources\CmsPages\Schemas;

use App\Models\CmsPage;
use App\Services\CustomBlockDiscoveryService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Page Management')
                    ->tabs([
                        Tabs\Tab::make('Content')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $state, callable $set) => 
                                                $set('slug', Str::slug($state))
                                            )
                                            ->columnSpan(1),
                                            
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(CmsPage::class, 'slug', ignoreRecord: true)
                                            ->rules(['alpha_dash'])
                                            ->helperText('Used in the URL. Keep it simple and SEO-friendly.')
                                            ->columnSpan(1),
                                    ]),
                                    
                                RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('cms/attachments')
                                    ->mergeTags([
                                        'site_name',
                                        'current_year',
                                        'page_title',
                                    ])
                                    ->customBlocks(CustomBlockDiscoveryService::getBlocksArray())
                                    ->extraInputAttributes([
                                        'style' => 'min-height: 40rem;',
                                    ])
                                    ->helperText('Create rich page content with custom blocks, merge tags, and advanced formatting. Use merge tags to insert dynamic content like {{site_name}} or {{current_year}}.'),
                            ]),
                            
                        Tabs\Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Page Settings')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'published' => 'Published',
                                            ])
                                            ->required()
                                            ->default('draft'),
                                            
                                        DateTimePicker::make('published_at')
                                            ->label('Publish Date')
                                            ->nullable()
                                            ->default(now()),
                                            
                                        Toggle::make('is_homepage')
                                            ->label('Set as Homepage')
                                            ->helperText('Only one page can be set as homepage. This will override any existing homepage setting.')
                                            ->columnSpan(2),
                                            
                                        Select::make('parent_id')
                                            ->label('Parent Page')
                                            ->options(CmsPage::query()
                                                ->whereNull('parent_id')
                                                ->pluck('title', 'id'))
                                            ->searchable()
                                            ->nullable()
                                            ->columnSpan(1),
                                            
                                        TextInput::make('sort_order')
                                            ->numeric()
                                            ->default(0)
                                            ->columnSpan(1),
                                            
                                        TextInput::make('template')
                                            ->helperText('Optional: Custom blade template')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make('Search Engine Optimization')
                                    ->description('Optimize your page for search engines and social media sharing')
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText('Recommended: 50-60 characters. If empty, page title will be used.'),
                                            
                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText('Recommended: 150-160 characters. Brief description for search results.'),
                                            
                                        FileUpload::make('featured_image')
                                            ->label('Featured Image')
                                            ->image()
                                            ->directory('cms/pages/featured-images')
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([
                                                '16:9',
                                                '4:3', 
                                                '1:1',
                                                '1.91:1', // Facebook recommended
                                                '2:1',    // Twitter header
                                            ])
                                            ->helperText('Used for social media sharing and page headers. Recommended: 1200x630px for best compatibility.'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}