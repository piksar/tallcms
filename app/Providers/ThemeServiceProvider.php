<?php

namespace App\Providers;

use App\Services\ThemeManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register ThemeManager as singleton
        $this->app->singleton(ThemeManager::class, function ($app) {
            return new ThemeManager();
        });

        // Register theme manager alias
        $this->app->alias(ThemeManager::class, 'theme.manager');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Create theme config if it doesn't exist
        $this->ensureThemeConfig();

        // Register theme view paths
        $this->registerThemeViewPaths();

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register view composer for theme assets
        $this->registerViewComposers();

        // Bind file-based theme to ThemeInterface on boot
        $this->bindActiveFileBasedTheme();
    }

    /**
     * Register theme view paths
     */
    protected function registerThemeViewPaths(): void
    {
        $themeManager = $this->app->make(ThemeManager::class);
        $themeManager->registerThemeViewPaths();
    }

    /**
     * Register Blade directives for themes
     */
    protected function registerBladeDirectives(): void
    {
        // @themeAsset directive
        Blade::directive('themeAsset', function ($expression) {
            return "<?php echo app('theme.manager')->themeAsset({$expression}); ?>";
        });

        // @themeVite directive for including theme assets from manifest
        Blade::directive('themeVite', function ($expression) {
            return "<?php \$themeAssets = app('theme.manager')->getThemeViteAssets({$expression}); 
                     foreach(\$themeAssets as \$asset): 
                         if(\$asset['type'] === 'css'): 
                             echo '<link rel=\"stylesheet\" href=\"' . \$asset['url'] . '\">'; 
                         else: 
                             echo '<script type=\"module\" src=\"' . \$asset['url'] . '\"></script>'; 
                         endif; 
                     endforeach; ?>";
        });


        // @theme directive to get current theme info
        Blade::directive('theme', function ($property = null) {
            if ($property) {
                return "<?php echo app('theme.manager')->getActiveTheme()->{$property}; ?>";
            }
            return "<?php echo app('theme.manager')->getActiveTheme()->name; ?>";
        });
    }

    /**
     * Register view composers for theme data
     */
    protected function registerViewComposers(): void
    {
        View::composer('*', function ($view) {
            $themeManager = app(ThemeManager::class);
            $activeTheme = $themeManager->getActiveTheme();
            
            $view->with([
                'currentTheme' => $activeTheme,
                'themeAsset' => function ($path) use ($themeManager) {
                    return $themeManager->themeAsset($path);
                }
            ]);
        });
    }

    /**
     * Ensure theme configuration file exists
     */
    protected function ensureThemeConfig(): void
    {
        $configPath = config_path('theme.php');
        
        if (!file_exists($configPath)) {
            $defaultConfig = [
                'active' => 'default',
                'themes_path' => base_path('themes'),
                'cache_themes' => true,
                'auto_discover' => true,
            ];

            file_put_contents(
                $configPath, 
                "<?php\n\nreturn " . var_export($defaultConfig, true) . ";\n"
            );
        }
    }

    /**
     * Bind file-based theme to ThemeInterface on boot
     */
    protected function bindActiveFileBasedTheme(): void
    {
        $themeManager = $this->app->make(ThemeManager::class);
        $activeTheme = $themeManager->getActiveTheme();
        
        // Only bind if we have a valid file-based theme
        if ($activeTheme && file_exists($activeTheme->path . '/theme.json')) {
            $fileBasedTheme = new \App\Services\FileBasedTheme($activeTheme);
            
            // Bind the FileBasedTheme to ThemeInterface
            $this->app->bind(\App\Contracts\ThemeInterface::class, function () use ($fileBasedTheme) {
                return $fileBasedTheme;
            });
        }
    }
}