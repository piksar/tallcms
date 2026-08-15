<?php

namespace TallCms\Cms\Filament\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use TallCms\Cms\Models\CmsPage;
use TallCms\Cms\Models\TallcmsMenu;
use TallCms\Cms\Models\TallcmsMenuItem;
use TallCms\Cms\Services\LocaleRegistry;
use Wsmallnews\FilamentNestedset\Pages\NestedsetPage;

class MenuItemsManager extends NestedsetPage
{
    protected static ?string $model = TallcmsMenuItem::class;

    protected static string $recordTitleAttribute = 'label';

    protected static ?string $tabFieldName = 'menu_id';

    protected static ?int $level = 5; // Allow up to 5 levels of nesting

    protected static bool $shouldRegisterNavigation = false; // Hide from navigation

    /**
     * Active locale for translations (consistent with LaraZeus LocaleSwitcher)
     */
    public ?string $activeLocale = null;

    public function mount(): void
    {
        parent::mount();

        // Initialize locale from session (consistent with LaraZeus) or use default
        $this->activeLocale = $this->getStoredActiveLocale() ?? $this->getDefaultTranslatableLocale();
    }

    /**
     * Get stored locale from session (consistent with LaraZeus behavior)
     */
    protected function getStoredActiveLocale(): ?string
    {
        if (! tallcms_i18n_enabled()) {
            return null;
        }

        // Use LaraZeus session key for consistency
        $locale = session()->get('spatie_translatable_active_locale');

        if ($locale && in_array($locale, $this->getTranslatableLocales(), true)) {
            return $locale;
        }

        return null;
    }

    /**
     * Called when activeLocale property is updated (by LocaleSwitcher)
     * This hook is consistent with LaraZeus Translatable trait behavior
     */
    public function updatedActiveLocale(): void
    {
        // Validate the locale
        if (! in_array($this->activeLocale, $this->getTranslatableLocales(), true)) {
            $this->activeLocale = $this->getDefaultTranslatableLocale();
        }

        // Persist to session (consistent with LaraZeus)
        if (filament('spatie-translatable')->getPersistLocale()) {
            session()->put('spatie_translatable_active_locale', $this->activeLocale);
        }

        // Refresh the tree view
        $this->dispatch('filament-nestedset-updated');
    }

    /**
     * Get available locales for translation
     * Required by LocaleSwitcher::make()
     */
    public function getTranslatableLocales(): array
    {
        if (! tallcms_i18n_enabled()) {
            return [config('app.locale', 'en')];
        }

        return app(LocaleRegistry::class)->getLocaleCodes();
    }

    /**
     * Get the default translatable locale
     */
    public function getDefaultTranslatableLocale(): string
    {
        if (! tallcms_i18n_enabled()) {
            return config('app.locale', 'en');
        }

        return app(LocaleRegistry::class)->getDefaultLocale();
    }

    public function getRecordLabel(Model $record): HtmlString|string
    {
        $locale = $this->activeLocale ?? $this->getDefaultTranslatableLocale();

        return $record->getTranslation(static::getRecordTitleAttribute(), $locale, true) ?: ' ';
    }

    /**
     * Get locale label for display (used in form field labels)
     */
    public function getLocaleLabel(string $locale): string
    {
        // Try to get label from LaraZeus plugin first (for consistency)
        try {
            $label = filament('spatie-translatable')->getLocaleLabel($locale);
            if ($label) {
                return $label;
            }
        } catch (\Throwable) {
            // Fall through to local registry
        }

        if (! tallcms_i18n_enabled()) {
            return $locale;
        }

        $registry = app(LocaleRegistry::class);
        $locales = $registry->getLocales();

        if ($locales->has($locale)) {
            $config = $locales->get($locale);

            return $config['native'] ?? $config['label'] ?? $locale;
        }

        return $locale;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Add locale switcher if i18n is enabled (using LaraZeus LocaleSwitcher for consistency)
        if (tallcms_i18n_enabled()) {
            $actions[] = LocaleSwitcher::make();
        }

        // Add default actions from parent
        $actions[] = $this->createAction();
        $actions[] = $this->fixTreeAction();

        return $actions;
    }

    public function getTabs(): array
    {
        $menus = TallcmsMenu::all();
        $tabs = [];

        foreach ($menus as $menu) {
            $tabs[$menu->id] = Tab::make()
                ->label($menu->name.' ('.$menu->allItems()->count().')');
        }

        return $tabs;
    }

    /**
     * Override createAction to handle translations
     */
    public function createAction(): CreateAction
    {
        return parent::createAction()
            ->mutateFormDataUsing(function (array $data): array {
                // Store label as translation for current locale
                if (isset($data['label']) && tallcms_i18n_enabled()) {
                    $data['_translatable_label'] = $data['label'];
                    unset($data['label']);
                }

                return $data;
            })
            ->using(function (array $data, array $arguments): Model {
                $translatableLabel = $data['_translatable_label'] ?? null;
                unset($data['_translatable_label']);

                // Get parent from form data or arguments (same as parent class)
                $parentId = $data['parent_id'] ?? ($arguments['parentId'] ?? 0);
                $parent = $parentId ? $this->getQuery()->find($parentId) : null;
                unset($data['parent_id']);

                // Add menu_id from active tab (this is the key fix)
                $data['menu_id'] = $this->activeTab;

                // Create the record with parent relationship
                $record = TallcmsMenuItem::create(
                    attributes: $data,
                    parent: $parent,
                );

                // Handle translatable label
                if ($translatableLabel !== null && tallcms_i18n_enabled()) {
                    $activeLocale = $this->activeLocale ?? $this->getDefaultTranslatableLocale();
                    $defaultLocale = $this->getDefaultTranslatableLocale();

                    // Always set the active locale translation
                    $record->setTranslation('label', $activeLocale, $translatableLabel);

                    // Also set default locale if different (ensures menu never renders blank)
                    if ($activeLocale !== $defaultLocale) {
                        $record->setTranslation('label', $defaultLocale, $translatableLabel);
                    }

                    $record->save();
                } elseif ($translatableLabel !== null) {
                    $record->label = $translatableLabel;
                    $record->save();
                }

                return $record;
            });
    }

    /**
     * Override editAction to handle translations
     */
    public function editAction(): EditAction
    {
        return parent::editAction()
            ->fillForm(function (Model $record): array {
                $data = $record->toArray();

                // Label is always stored as translatable JSON (Spatie normalizes the column).
                // Always extract a string for the text input — otherwise toArray() returns
                // the full translations array and the field renders as "[object Object]".
                $locale = tallcms_i18n_enabled()
                    ? ($this->activeLocale ?? $this->getDefaultTranslatableLocale())
                    : $this->getDefaultTranslatableLocale();

                $data['label'] = $record->getTranslation('label', $locale, true) ?: '';

                return $data;
            })
            ->mutateFormDataUsing(function (array $data): array {
                if (isset($data['label']) && tallcms_i18n_enabled()) {
                    $data['_translatable_label'] = $data['label'];
                    unset($data['label']);
                }

                return $data;
            })
            ->using(function (Model $record, array $data): Model {
                $translatableLabel = $data['_translatable_label'] ?? null;
                unset($data['_translatable_label']);

                $record->fill($data);

                if ($translatableLabel !== null && tallcms_i18n_enabled()) {
                    $activeLocale = $this->activeLocale ?? $this->getDefaultTranslatableLocale();
                    $record->setTranslation('label', $activeLocale, $translatableLabel);
                } elseif ($translatableLabel !== null) {
                    $record->label = $translatableLabel;
                }

                $record->save();

                return $record;
            });
    }

    protected function schema(array $arguments): array
    {
        return [
            Hidden::make('menu_id')
                ->default(function () use ($arguments) {
                    return $arguments['tab'] ?? request()->get('activeTab');
                }),

            TextInput::make('label')
                ->label(fn () => tallcms_i18n_enabled()
                    ? 'Menu Label ('.$this->getLocaleLabel($this->activeLocale ?? $this->getDefaultTranslatableLocale()).')'
                    : 'Menu Label')
                ->required()
                ->maxLength(255)
                ->placeholder(__('tallcms::fields.home'))
                ->helperText(fn () => tallcms_i18n_enabled()
                    ? 'Switch language using the button in the header to translate this label.'
                    : null),

            Select::make('type')
                ->label(__('tallcms::fields.link_type'))
                ->options([
                    'page' => 'Page',
                    'external' => 'External URL',
                    'custom' => 'Custom URL',
                    'header' => 'Header',
                    'separator' => 'Separator',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('page_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('url', null)),

            Select::make('page_id')
                ->label(__('tallcms::fields.select_page'))
                ->options(function () use ($arguments): array {
                    // Scope to the menu being edited, not the session site — the user
                    // may have navigated here from a site context that doesn't match.
                    $menuId = $arguments['tab'] ?? request()->get('activeTab') ?? $this->activeTab;
                    $menu = $menuId
                        ? TallcmsMenu::withoutGlobalScopes()->find($menuId)
                        : null;

                    $pagesHaveSiteId = \Illuminate\Support\Facades\Schema::hasColumn('tallcms_pages', 'site_id');

                    $query = CmsPage::withoutGlobalScopes()
                        ->where('status', 'published');

                    if ($pagesHaveSiteId) {
                        // Multisite is active — restrict to the menu's site. If the menu
                        // has no site_id (orphan), show nothing rather than leak cross-site.
                        $query->where('site_id', $menu?->site_id ?? 0);
                    }

                    // Resolve titles through the accessor (with fallback locale) so
                    // translatable JSON columns render as strings, not "[object Object]".
                    return $query->get()
                        ->mapWithKeys(fn (CmsPage $page) => [
                            $page->id => (string) ($page->title ?: __('Untitled')),
                        ])
                        ->all();
                })
                ->searchable()
                ->required()
                ->visible(fn (Get $get): bool => $get('type') === 'page'),

            TextInput::make('url')
                ->label(__('tallcms::fields.url'))
                ->required()
                ->placeholder('https://example.com or /contact')
                ->visible(fn (Get $get): bool => in_array($get('type'), ['external', 'custom'])),

            Toggle::make('is_active')
                ->label(__('tallcms::fields.active'))
                ->default(true),
        ];
    }
}
