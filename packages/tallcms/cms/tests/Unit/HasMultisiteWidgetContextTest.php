<?php

declare(strict_types=1);

namespace TallCms\Cms\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use TallCms\Cms\Filament\Widgets\Concerns\HasMultisiteWidgetContext;
use TallCms\Cms\Tests\Fixtures\User;
use TallCms\Cms\Tests\TestCase;

/**
 * Anonymous-class-style consumer of the trait, exposing the protected
 * methods publicly for assertion. Keeps the test self-contained.
 */
class WidgetContextProbe
{
    use HasMultisiteWidgetContext {
        getMultisiteSiteId as public;
        getMultisiteName as public;
        isAllSitesSelected as public;
    }
}

class HasMultisiteWidgetContextTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            \Spatie\Permission\PermissionServiceProvider::class,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('tallcms_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Spatie Permission tables
        $config = $this->app['config'];
        $config->set('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
        $config->set('permission.column_names', [
            'role_pivot_key' => null,
            'permission_pivot_key' => null,
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
        ]);

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
    }

    public function test_specific_site_id_in_session_returns_that_id(): void
    {
        session(['multisite_admin_site_id' => 42]);

        $this->assertSame(42, (new WidgetContextProbe)->getMultisiteSiteId());
    }

    public function test_all_sites_sentinel_returns_null(): void
    {
        session(['multisite_admin_site_id' => '__all_sites__']);

        $this->assertNull((new WidgetContextProbe)->getMultisiteSiteId());
    }

    public function test_super_admin_falls_back_to_default_site(): void
    {
        session()->forget('multisite_admin_site_id');
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::create(['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'x']);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        \DB::table('tallcms_sites')->insert([
            ['id' => 7, 'name' => 'Main', 'is_default' => true, 'is_active' => true, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'Other', 'is_default' => false, 'is_active' => true, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertSame(7, (new WidgetContextProbe)->getMultisiteSiteId());
    }

    public function test_non_super_admin_falls_back_to_first_owned_site(): void
    {
        session()->forget('multisite_admin_site_id');
        $user = User::create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => 'x']);
        $this->actingAs($user);

        \DB::table('tallcms_sites')->insert([
            ['id' => 11, 'name' => 'A', 'is_default' => false, 'is_active' => true, 'user_id' => $user->id, 'created_at' => now()->subDay(), 'updated_at' => now()],
            ['id' => 12, 'name' => 'B', 'is_default' => false, 'is_active' => true, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'name' => 'C-others', 'is_default' => false, 'is_active' => true, 'user_id' => 99999, 'created_at' => now()->subWeek(), 'updated_at' => now()],
        ]);

        // Should pick site 11 (oldest owned by this user), NOT 13 (older but not owned).
        $this->assertSame(11, (new WidgetContextProbe)->getMultisiteSiteId());
    }

    public function test_query_exception_falls_through_to_null(): void
    {
        Schema::drop('tallcms_sites');
        session()->forget('multisite_admin_site_id');

        $this->assertNull((new WidgetContextProbe)->getMultisiteSiteId());
    }

    public function test_multisite_name_returns_all_sites_sentinel(): void
    {
        session(['multisite_admin_site_id' => '__all_sites__']);

        $this->assertSame(__('tallcms::widgets.all_sites'), (new WidgetContextProbe)->getMultisiteName(null));
    }

    public function test_multisite_name_returns_site_row_name(): void
    {
        \DB::table('tallcms_sites')->insert([
            'id' => 99, 'name' => 'My Site', 'is_default' => false, 'is_active' => true, 'user_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame('My Site', (new WidgetContextProbe)->getMultisiteName(99));
    }

    public function test_multisite_name_returns_null_for_missing_id(): void
    {
        $this->assertNull((new WidgetContextProbe)->getMultisiteName(null));
    }

    public function test_is_all_sites_selected_true_on_sentinel(): void
    {
        session(['multisite_admin_site_id' => '__all_sites__']);

        $this->assertTrue((new WidgetContextProbe)->isAllSitesSelected());
    }

    public function test_is_all_sites_selected_false_on_specific_id(): void
    {
        session(['multisite_admin_site_id' => 42]);

        $this->assertFalse((new WidgetContextProbe)->isAllSitesSelected());
    }

    public function test_is_all_sites_selected_false_when_session_unset(): void
    {
        session()->forget('multisite_admin_site_id');

        $this->assertFalse((new WidgetContextProbe)->isAllSitesSelected());
    }
}
