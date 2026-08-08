<?php

namespace TallCms\Cms\Filament\Resources\CmsCategories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use TallCms\Cms\Models\CmsCategory;
use TallCms\Cms\Rules\UniqueTranslatableSlug;
use TallCms\Cms\Rules\UserAwareUnique;
use TallCms\Cms\Services\LocaleRegistry;

class CmsCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label(__('tallcms::fields.name'))
                    ->required(function ($livewire) {
                        if (! tallcms_i18n_enabled()) {
                            return true;
                        }
                        // Require name for default locale when i18n enabled
                        $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                        $defaultLocale = app(LocaleRegistry::class)->getDefaultLocale();

                        return $activeLocale === $defaultLocale;
                    })
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, ?string $old, callable $set) => $set('slug', Str::slug($state))
                    ),

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
                    ->rules(function (?CmsCategory $record, $livewire) {
                        $rules = ['alpha_dash'];

                        if (tallcms_i18n_enabled()) {
                            // Block locale codes as slugs
                            $reserved = app(LocaleRegistry::class)->getReservedSlugs();
                            $rules[] = 'not_in:'.implode(',', $reserved);

                            // Unique per locale
                            $activeLocale = $livewire->activeLocale ?? app()->getLocale();
                            $rules[] = new UniqueTranslatableSlug(
                                table: 'tallcms_categories',
                                column: 'slug',
                                locale: $activeLocale,
                                ignoreId: $record?->id
                            );
                        } else {
                            // Site-aware unique constraint
                            $rules[] = UserAwareUnique::rule('tallcms_categories', 'slug', $record?->id);
                        }

                        return $rules;
                    })
                    ->validationMessages([
                        'not_in' => __('tallcms::fields.slug_reserved_language_code'),
                    ])
                    ->helperText(__('tallcms::fields.help_category_slug_url')),

                Select::make('parent_id')
                    ->label(__('tallcms::fields.parent', ['resource' => tallcms_label('categories', 'singular')]))
                    ->options(function () {
                        $query = CmsCategory::query()->whereNull('parent_id');
                        if (auth()->check() && ! auth()->user()->hasRole('super_admin')
                            && \Illuminate\Support\Facades\Schema::hasColumn('tallcms_categories', 'user_id')) {
                            $query->where('user_id', auth()->id());
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->nullable(),

                ColorPicker::make('color')
                    ->label(tallcms_label('categories', 'singular').' Color')
                    ->nullable()
                    ->helperText(__('tallcms::ui.t_optional_color_for_visual_organization')),

                TextInput::make('sort_order')->label(__('tallcms::fields.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(1),

                Textarea::make('description')->label(__('tallcms::fields.description'))
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
