<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => SiteSetting::get('site_name'),
            'site_tagline' => SiteSetting::get('site_tagline'),
            'site_description' => SiteSetting::get('site_description'),
            'contact_email' => SiteSetting::get('contact_email'),
            'logo' => SiteSetting::get('logo'),
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

            FileUpload::make('logo')
                ->label('Site Logo')
                ->image()
                ->directory('site-assets')
                ->disk('public')
                ->visibility('public')
                ->helperText('Upload your site logo (PNG, JPG, or SVG)')
                ->nullable(),
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
                    default => 'text',
                };
                
                $group = match ($key) {
                    'site_name', 'site_tagline', 'site_description' => 'general',
                    'contact_email' => 'contact',
                    'logo' => 'branding',
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