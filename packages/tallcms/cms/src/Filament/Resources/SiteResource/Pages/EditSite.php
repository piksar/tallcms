<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\SiteResource\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Schema;
use TallCms\Cms\Filament\Resources\SiteResource\SiteForm;
use TallCms\Cms\Filament\Resources\SiteResource\SiteResource;
use TallCms\Cms\Models\Site;
use TallCms\Cms\Models\SiteSetting;
use TallCms\Cms\Services\SiteSettingsService;

/**
 * Core Site edit page.
 *
 * In standalone mode: always edits the default site.
 * In multisite mode: the multisite plugin overrides this with
 * a proper resource edit page that works with multiple records.
 *
 * Authorization is handled by the SiteResource, not HasPageShield
 * (which is incompatible with resource pages).
 */
class EditSite extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SiteResource::class;

    protected string $view = 'tallcms::filament.pages.site-edit';

    public ?array $data = [];

    protected ?Site $siteRecord = null;

    public function getTitle(): string
    {
        return tallcms_label('site_settings', 'navigation');
    }

    /**
     * The 20 site-scoped setting keys (stored as overrides).
     */
    protected array $settingKeys = [
        // General
        'site_tagline' => 'text',
        'site_description' => 'text',
        'site_type' => 'text',
        // Contact
        'contact_email' => 'text',
        'contact_phone' => 'text',
        'company_name' => 'text',
        'company_address' => 'text',
        // Social
        'social_facebook' => 'text',
        'social_twitter' => 'text',
        'social_linkedin' => 'text',
        'social_instagram' => 'text',
        'social_youtube' => 'text',
        'social_tiktok' => 'text',
        'newsletter_signup_url' => 'text',
        // Branding
        'logo' => 'file',
        'favicon' => 'file',
        'show_powered_by' => 'boolean',
        'show_theme_switcher' => 'boolean',
        'show_search' => 'boolean',
        'show_language_dropdown' => 'boolean',
        // Publishing
        'review_workflow_enabled' => 'boolean',
        // Maintenance
        'maintenance_mode' => 'boolean',
        'maintenance_message' => 'text',
        // Languages
        'redirect_root_to_locale' => 'boolean',
        // Embed code (per-site overrides; global default writable in single-site EditSite)
        'code_head' => 'text',
        'code_body_start' => 'text',
        'code_body_end' => 'text',
    ];

    public function mount(): void
    {
        $this->siteRecord = $this->getSiteRecord();
        $service = app(SiteSettingsService::class);
        $siteId = $this->siteRecord->id;

        $formData = [
            // Site model fields
            'name' => $this->siteRecord->name,
            'domain' => $this->siteRecord->domain,
            'theme' => $this->siteRecord->theme,
            'locale' => $this->siteRecord->locale,
        ];

        // Load all 20 settings from SiteSettingsService
        foreach ($this->settingKeys as $key => $type) {
            $default = match ($key) {
                'show_powered_by',
                'show_theme_switcher',
                'show_search',
                'show_language_dropdown' => true,
                'site_type' => 'multi-page',
                'maintenance_message' => "We're currently performing scheduled maintenance. Please check back soon!",
                default => null,
            };

            $formData[$key] = $service->getForSite($siteId, $key, $default);

            if ($key === 'redirect_root_to_locale') {
                $formData[$key] = SiteForm::normalizeRedirectRootToLocaleFormValue($formData[$key]);
            }
        }

        $this->form->fill($formData);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $site = $this->getSiteRecord();
        $service = app(SiteSettingsService::class);

        // Save Site model fields
        $site->update([
            'name' => $data['name'],
            'domain' => $data['domain'] ?? $site->domain,
            'theme' => $data['theme'] ?? $site->theme,
            'locale' => $data['locale'] ?? $site->locale,
        ]);

        // Save settings overrides via explicit service.
        // - No override + matches global → skip (preserve inheritance)
        // - No override + differs from global → create override
        // - Has override + matches global → delete override (restore inheritance)
        // - Has override + differs from global → update override
        foreach ($this->settingKeys as $key => $type) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            // File fields: null could mean "unchanged" or "deleted".
            // Re-read the stored value to distinguish.
            if ($type === 'file' && $value === null) {
                $stored = $service->getForSite($site->id, $key);
                if (! $stored) {
                    // Was empty, still empty — no change
                    continue;
                }
                // Had a value, now null — user deleted it. Write empty string.
                $value = '';
            }

            $globalValue = $service->getGlobal($key);
            $matchesGlobal = static::valuesMatch($value, $globalValue, $type);
            $hasOverride = $service->hasOverride($site->id, $key);

            if ($matchesGlobal) {
                // Value matches global — remove override to restore inheritance
                if ($hasOverride) {
                    $service->resetForSite($site->id, $key);
                }

                continue;
            }

            // Value differs from global — create or update override
            $service->setForSite($site->id, $key, $value, $type);
        }

        if (! SiteForm::isRedirectRootToLocaleApplicable()
            && $service->hasOverride($site->id, 'redirect_root_to_locale')) {
            $service->resetForSite($site->id, 'redirect_root_to_locale');
        }

        // Clear caches
        SiteSetting::clearCache();

        Notification::make()
            ->title(__('tallcms::ui.t_site_settings_saved'))
            ->body(__('tallcms::ui.n_settings_saved_for_site', ['name' => $site->name]))
            ->success()
            ->send();
    }

    /**
     * Compare a submitted value with a global value, accounting for type.
     */
    protected static function valuesMatch(mixed $submitted, mixed $global, string $type): bool
    {
        if ($type === 'boolean') {
            return (bool) $submitted === (bool) $global;
        }

        return (string) ($submitted ?? '') === (string) ($global ?? '');
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return SiteForm::schema($this->getSiteRecord());
    }

    /**
     * Get the site record being edited.
     * In standalone: always the default site.
     */
    protected function getSiteRecord(): Site
    {
        if ($this->siteRecord) {
            return $this->siteRecord;
        }

        // Guard: table may not exist yet on fresh upgrades
        if (! Schema::hasTable('tallcms_sites')) {
            abort(503, 'Please run "php artisan migrate" to complete the TallCMS 4.0 upgrade.');
        }

        return $this->siteRecord = Site::getDefault() ?? Site::first() ?? $this->createDefaultSite();
    }

    protected function createDefaultSite(): Site
    {
        return Site::create([
            'name' => config('app.name', 'My Site'),
            'domain' => Site::normalizeDomain(parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost'),
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
