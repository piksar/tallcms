<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected string $view = 'filament.pages.site-settings';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?string $title = 'Site Settings';
    protected static ?int $navigationSort = 99;

    public ?array $data = [];

     public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-8-tooth';
    }

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => SiteSetting::get('site_name'),
            'site_tagline' => SiteSetting::get('site_tagline'),
            'site_description' => SiteSetting::get('site_description'),
            'contact_email' => SiteSetting::get('contact_email'),
            'logo' => SiteSetting::get('logo'),
            'site_type' => SiteSetting::get('site_type', 'multi-page'),
            'maintenance_mode' => SiteSetting::get('maintenance_mode', false),
            'maintenance_message' => SiteSetting::get('maintenance_message', 'We\'re currently performing scheduled maintenance. Please check back soon!'),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('site_name')
                ->label('Site Name')
                ->required()
                ->maxLength(255)
                ->placeholder('My Amazing Site')
                ->helperText('Appears in browser tabs and throughout the site'),

            TextInput::make('site_tagline')
                ->label('Site Tagline')
                ->maxLength(255)
                ->placeholder('A brief description of what you do')
                ->helperText('Short phrase that describes your site'),

            Textarea::make('site_description')
                ->label('Site Description')
                ->maxLength(500)
                ->rows(3)
                ->placeholder('A longer description for search engines and social media')
                ->helperText('Used as fallback meta description'),

            TextInput::make('contact_email')
                ->label('Contact Email')
                ->email()
                ->required()
                ->placeholder('hello@example.com')
                ->helperText('Default email for contact forms'),

            Select::make('site_type')
                ->label('Site Type')
                ->options([
                    'multi-page' => 'Multi-Page Website',
                    'single-page' => 'Single-Page Application (SPA)',
                ])
                ->default('multi-page')
                ->required()
                ->helperText('Multi-page: Traditional website with separate pages. SPA: One-page website with anchor navigation.'),

            FileUpload::make('logo')
                ->label('Site Logo')
                ->image()
                ->directory('site-assets')
                ->disk('public')
                ->visibility('public')
                ->helperText('Upload your site logo (PNG, JPG, or SVG)')
                ->nullable(),

            Toggle::make('maintenance_mode')
                ->label('Maintenance Mode')
                ->helperText('When enabled, all visitors will see a maintenance page. Administrators can still access the admin panel.')
                ->live()
                ->columnSpanFull(),

            Textarea::make('maintenance_message')
                ->label('Maintenance Message')
                ->maxLength(500)
                ->rows(3)
                ->placeholder('We\'re currently performing scheduled maintenance. Please check back soon!')
                ->helperText('Message shown to visitors during maintenance mode')
                ->visible(fn ($get) => $get('maintenance_mode'))
                ->columnSpanFull(),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $type = match ($key) {
                    'logo' => 'file',
                    'maintenance_mode' => 'boolean',
                    default => 'text',
                };
                
                $group = match ($key) {
                    'site_name', 'site_tagline', 'site_description', 'site_type' => 'general',
                    'contact_email' => 'contact',
                    'logo' => 'branding',
                    'maintenance_mode', 'maintenance_message' => 'maintenance',
                    default => 'general',
                };

                SiteSetting::set($key, $value, $type, $group);
            }
        }

        // Clear all settings cache
        SiteSetting::clearCache();

        Notification::make()
            ->title('Settings saved successfully!')
            ->success()
            ->send();
    }

}