<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ApiTokens extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'tallcms::filament.pages.api-tokens';

    public function getTitle(): string
    {
        return __('tallcms::pages.api_tokens.title');
    }

    public ?string $newToken = null;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-key';
    }

    public static function getNavigationLabel(): string
    {
        return __('tallcms::pages.api_tokens.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return tallcms_nav_group('system');
    }

    public static function getNavigationSort(): ?int
    {
        return 52;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('tallcms.api.enabled', false);
    }

    /**
     * Get current user's tokens.
     */
    #[Computed]
    public function tokens(): Collection
    {
        return auth()->user()->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                $data = [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->diffForHumans(),
                    'expires_at' => $token->expires_at?->format('M d, Y'),
                    'is_expired' => $token->expires_at && $token->expires_at->isPast(),
                    'created_at' => $token->created_at->format('M d, Y'),
                    'site_name' => null,
                ];

                // Show site context when multisite is active
                if (isset($token->site_id) && $token->site_id) {
                    try {
                        $data['site_name'] = \Illuminate\Support\Facades\DB::table('tallcms_sites')
                            ->where('id', $token->site_id)
                            ->value('name');
                    } catch (\Throwable) {
                    }
                }

                return $data;
            });
    }

    /**
     * Create token action.
     */
    public function createTokenAction(): Action
    {
        return Action::make('createToken')
            ->label(__('tallcms::fields.create_token'))
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->form([
                TextInput::make('name')
                    ->label(__('tallcms::fields.token_name'))
                    ->placeholder(__('tallcms::ui.t_e_g_api_client_ci_cd_mobile_app'))
                    ->required()
                    ->maxLength(255),
                CheckboxList::make('abilities')
                    ->label(__('tallcms::fields.permissions'))
                    ->options([
                        'pages:read' => 'Read Pages',
                        'pages:write' => 'Create/Update Pages',
                        'pages:delete' => 'Delete Pages',
                        'posts:read' => 'Read Posts',
                        'posts:write' => 'Create/Update Posts',
                        'posts:delete' => 'Delete Posts',
                        'categories:read' => 'Read Categories',
                        'categories:write' => 'Create/Update Categories',
                        'categories:delete' => 'Delete Categories',
                        'media:read' => 'Read Media',
                        'media:write' => 'Upload/Update Media',
                        'media:delete' => 'Delete Media',
                        'webhooks:manage' => 'Manage Webhooks',
                    ])
                    ->columns(2)
                    ->required(),
                TextInput::make('expires_in_days')
                    ->label(__('tallcms::fields.expires_in_days'))
                    ->numeric()
                    ->default(config('tallcms.api.token_expiry_days', 365))
                    ->minValue(1)
                    ->maxValue(365)
                    ->suffix('days'),
            ])
            ->action(function (array $data) {
                $expiresAt = now()->addDays((int) $data['expires_in_days']);

                $token = auth()->user()->createToken(
                    $data['name'],
                    $data['abilities'],
                    $expiresAt
                );

                // Store site context on the token when multisite is active
                if (\Illuminate\Support\Facades\Schema::hasColumn('personal_access_tokens', 'site_id')) {
                    $siteId = null;
                    $sessionValue = session('multisite_admin_site_id');
                    if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
                        $siteId = (int) $sessionValue;
                    }

                    if ($siteId) {
                        $token->accessToken->update(['site_id' => $siteId]);
                    }
                }

                $this->newToken = $token->plainTextToken;

                unset($this->tokens);

                $this->dispatch('open-modal', id: 'token-created-modal');

                Notification::make()
                    ->title(__('tallcms::ui.t_token_created'))
                    ->body(__('tallcms::ui.t_your_new_api_token_has_been_created_copy_it_now_it_won_t_be_shown_ag'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Revoke token action.
     */
    public function revokeTokenAction(): Action
    {
        return Action::make('revokeToken')
            ->label(__('tallcms::fields.revoke'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('tallcms::ui.t_revoke_token'))
            ->modalDescription(__('tallcms::ui.t_are_you_sure_you_want_to_revoke_this_token_applications_using_this_t'))
            ->modalSubmitActionLabel(__('tallcms::ui.t_yes_revoke'))
            ->action(function (array $arguments) {
                auth()->user()->tokens()->where('id', $arguments['id'])->delete();

                unset($this->tokens);

                Notification::make()
                    ->title(__('tallcms::ui.t_token_revoked'))
                    ->body(__('tallcms::ui.t_the_api_token_has_been_revoked'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Close token modal.
     */
    public function closeTokenModal(): void
    {
        $this->newToken = null;
    }

    /**
     * Get header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->createTokenAction(),
        ];
    }
}
