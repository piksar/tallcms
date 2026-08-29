<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Filament\Pages\ThemeManager;
use TallCms\Cms\Models\SiteSetting;
use Tests\TestCase;

/**
 * Display Options / default preset must follow Theme Manager's selected site,
 * not the admin request hostname.
 *
 * Repro: super_admin on 127.0.0.1.nip.io picks Ponyhof in the Theme Manager
 * dropdown and turns the theme switcher off. Ambient SiteSetting::set() on
 * the Livewire update resolved the Host site instead, so Ponyhof kept the
 * global default (on).
 */
class ThemeManagerDisplayOptionsScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('tallcms_sites')) {
            $this->markTestSkipped('tallcms_sites table is not available.');
        }

        Cache::flush();
        SiteSetting::forgetMemoizedDefaultSiteId();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    public function test_display_option_toggle_writes_to_session_site_not_hostname_site(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $hostSiteId = $this->insertSite('TallCMS', '127.0.0.1.nip.io');
        $ponyhofId = $this->insertSite('Ponyhof Club', 'ponyhof-club.127.0.0.1.nip.io');

        $this->bindHostnameResolver($hostSiteId);
        request()->attributes->set('tallcms.admin_context', false);
        session(['multisite_admin_site_id' => $ponyhofId]);

        $page = new ThemeManager;
        $page->updatedShowThemeSwitcher(false);
        $page->updatedShowSearch(false);
        $page->updatedShowLanguageDropdown(false);

        $this->assertSame('0', $this->overrideValue($ponyhofId, 'show_theme_switcher'));
        $this->assertSame('0', $this->overrideValue($ponyhofId, 'show_search'));
        $this->assertSame('0', $this->overrideValue($ponyhofId, 'show_language_dropdown'));

        $this->assertNull(
            $this->overrideValue($hostSiteId, 'show_theme_switcher'),
            'Display Options must not land on the admin hostname site.',
        );
        $this->assertNull($this->overrideValue($hostSiteId, 'show_search'));
        $this->assertNull($this->overrideValue($hostSiteId, 'show_language_dropdown'));
    }

    public function test_display_option_hydrate_reads_session_site_not_hostname_site(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $hostSiteId = $this->insertSite('TallCMS', '127.0.0.1.nip.io');
        $ponyhofId = $this->insertSite('Ponyhof Club', 'ponyhof-club.127.0.0.1.nip.io');

        DB::table('tallcms_site_setting_overrides')->insert([
            'site_id' => $ponyhofId,
            'key' => 'show_theme_switcher',
            'value' => '0',
            'type' => 'boolean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tallcms_site_settings')->insert([
            'key' => 'show_theme_switcher',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'branding',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bindHostnameResolver($hostSiteId);
        request()->attributes->set('tallcms.admin_context', false);
        session(['multisite_admin_site_id' => $ponyhofId]);

        $page = new ThemeManager;
        $read = new \ReflectionMethod($page, 'readScopedSetting');
        $read->setAccessible(true);

        $this->assertFalse(
            (bool) $read->invoke($page, 'show_theme_switcher', true),
            'Hydration must use the Theme Manager session site (Ponyhof off), not global/host on.',
        );
    }

    public function test_super_admin_without_session_site_writes_global_not_hostname_override(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $hostSiteId = $this->insertSite('TallCMS', '127.0.0.1.nip.io');

        $this->bindHostnameResolver($hostSiteId);
        request()->attributes->set('tallcms.admin_context', false);
        session()->forget('multisite_admin_site_id');

        $page = new ThemeManager;
        $page->updatedShowThemeSwitcher(false);

        $this->assertNull($this->overrideValue($hostSiteId, 'show_theme_switcher'));
        $this->assertSame(
            '0',
            DB::table('tallcms_site_settings')->where('key', 'show_theme_switcher')->value('value'),
        );
    }

    protected function insertSite(string $name, string $domain): int
    {
        return (int) DB::table('tallcms_sites')->insertGetId([
            'name' => $name,
            'domain' => $domain,
            'uuid' => (string) Str::uuid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function overrideValue(int $siteId, string $key): ?string
    {
        $value = DB::table('tallcms_site_setting_overrides')
            ->where('site_id', $siteId)
            ->where('key', $key)
            ->value('value');

        return $value === null ? null : (string) $value;
    }

    /**
     * Mimic a Livewire update whose Host matches the platform site, while
     * Theme Manager's dropdown session points at another tenant.
     */
    protected function bindHostnameResolver(int $hostSiteId): void
    {
        $this->app->instance('tallcms.multisite.resolver', new class($hostSiteId)
        {
            public function __construct(private int $hostSiteId) {}

            public function isResolved(): bool
            {
                return true;
            }

            public function id(): int
            {
                return $this->hostSiteId;
            }

            public function reset(): void {}
        });
    }
}
