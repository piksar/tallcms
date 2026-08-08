<?php

namespace TallCms\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeTallCmsBlock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:tallcms-block {name : The name of the custom block}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new TallCMS custom block with CSS custom properties integration';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $className = Str::studly($name).'Block';
        $kebabName = Str::kebab($name);
        $snakeName = Str::snake($name);
        $titleName = Str::headline($name);

        // Create the block class
        $this->createBlockClass($className, $kebabName, $snakeName, $titleName);

        // Create the template
        $this->createBlockTemplate($kebabName, $className);

        $this->info("TallCMS block '{$className}' created successfully!");
        $this->info("Block class: app/Filament/Blocks/{$className}.php");
        $this->info("Template: resources/views/cms/blocks/{$kebabName}.blade.php");
        $this->newLine();
        $this->info('✅ No registration required! The block will be auto-discovered and available immediately.');
        $this->newLine();
        $this->warn("⚠️  IMPORTANT: Don't forget to add CSS styles!");
        $this->line('📁 Add block styles to: resources/css/blocks.css');
        $this->line("🔧 Run 'npm run build' to compile assets");
        $this->line('🧪 Test in both admin preview and frontend');
        $this->newLine();
        $this->line('🎉 Your new block is ready to use in the rich editor!');
    }

    /**
     * Create the block class file.
     */
    protected function createBlockClass(string $className, string $kebabName, string $snakeName, string $titleName): void
    {
        $classPath = app_path("Filament/Blocks/{$className}.php");

        // Ensure directory exists
        $this->files->ensureDirectoryExists(dirname($classPath));

        $stub = $this->getBlockClassStub();
        $content = str_replace(
            ['{{CLASS_NAME}}', '{{KEBAB_NAME}}', '{{SNAKE_NAME}}', '{{BLOCK_ID}}', '{{TITLE_NAME}}'],
            [$className, $kebabName, $snakeName, $snakeName, $titleName],
            $stub
        );

        $this->files->put($classPath, $content);
    }

    /**
     * Create the block template file.
     */
    protected function createBlockTemplate(string $kebabName, string $className): void
    {
        $templatePath = resource_path("views/cms/blocks/{$kebabName}.blade.php");

        // Ensure directory exists
        $this->files->ensureDirectoryExists(dirname($templatePath));

        $stub = $this->getBlockTemplateStub();
        $content = str_replace(
            ['{{KEBAB_NAME}}', '{{CLASS_NAME}}'],
            [$kebabName, $className],
            $stub
        );

        $this->files->put($templatePath, $content);
    }

    /**
     * Get the block class stub.
     */
    protected function getBlockClassStub(): string
    {
        return <<<'EOT'
<?php

namespace App\Filament\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use TallCms\Cms\Filament\Blocks\Concerns\HasBlockMetadata;

class {{CLASS_NAME}} extends RichContentCustomBlock
{
    use HasBlockMetadata;

    public static function getId(): string
    {
        return '{{BLOCK_ID}}';
    }

    public static function getLabel(): string
    {
        return '{{TITLE_NAME}}';
    }

    /**
     * Block category for the enhanced block panel.
     * Options: content, media, social-proof, dynamic, forms, other
     */
    public static function getCategory(): string
    {
        return 'content';
    }

    /**
     * Icon displayed in the block panel.
     * Use any valid Heroicon name (e.g., heroicon-o-star, heroicon-o-photo)
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    /**
     * Brief description shown in search results.
     */
    public static function getDescription(): string
    {
        return 'A custom {{TITLE_NAME}} block';
    }

    /**
     * Additional keywords for search.
     */
    public static function getKeywords(): array
    {
        return ['custom', '{{SNAKE_NAME}}'];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('tallcms::ui.t_configure_the_title_name_block'))
            ->schema([
                TextInput::make('title')->label(__('tallcms::fields.title'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('tallcms::ui.t_enter_block_title')),

                Textarea::make('description')->label(__('tallcms::fields.description'))
                    ->maxLength(500)
                    ->placeholder(__('tallcms::ui.t_enter_block_description')),
            ])->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('cms.blocks.{{KEBAB_NAME}}', [
            'title' => $config['title'] ?? 'Sample Title',
            'description' => $config['description'] ?? 'Sample description text',
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('cms.blocks.{{KEBAB_NAME}}', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
        ])->render();
    }
}
EOT;
    }

    /**
     * Get the block template stub.
     */
    protected function getBlockTemplateStub(): string
    {
        return <<<'EOT'
{{-- {{CLASS_NAME}} - Generated by TallCMS --}}
{{-- TODO: Add CSS styles for '.{{KEBAB_NAME}}-block' in resources/css/blocks.css --}}

@php
    // Get current theme presets for consistent styling
    $textPresets = \theme_text_presets();
    $textPreset = $textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];
    
    // Build inline CSS custom properties for this block instance
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'],
    ])->join('; ') . ';';
@endphp

<section class="{{KEBAB_NAME}}-block py-8 px-4 max-w-4xl mx-auto" style="{{ $customProperties }}">
    @if($title)
        <h2 class="text-2xl font-bold mb-4">
            {{ $title }}
        </h2>
    @endif
    
    @if($description)
        <div class="text-lg leading-relaxed">
            {!! nl2br(e($description)) !!}
        </div>
    @endif
</section>

{{-- 
    TallCMS CSS Custom Properties Approach:
    - CSS styling defined in resources/css/blocks.css using .{{KEBAB_NAME}}-block selector
    - Theme colors injected via CSS custom properties (--block-heading-color, etc.)
    - Perfect consistency between admin preview and frontend
    
    To customize this block:
    1. Add more form fields in the configureEditorAction method
    2. Update both toPreviewHtml and toHtml methods with new variables
    3. Add CSS styles in resources/css/blocks.css
    4. Use CSS custom properties for theme integration: color: var(--block-heading-color);
    5. Run 'npm run build' to compile assets
--}}
EOT;
    }
}
