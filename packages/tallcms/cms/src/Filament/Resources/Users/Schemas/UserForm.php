<?php

declare(strict_types=1);

namespace TallCms\Cms\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    /**
     * Build author profile fields based on which columns exist on the user table.
     * This ensures plugin-mode compatibility — host apps may not have run all migrations.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    protected static function getAuthorProfileFields(string $model): array
    {
        try {
            $table = (new $model)->getTable();
        } catch (\Throwable) {
            $table = 'users';
        }

        $fields = [];

        if (DbSchema::hasColumn($table, 'slug')) {
            $fields[] = TextInput::make('slug')
                ->label(__('tallcms::fields.author_slug'))
                ->disabled()
                ->dehydrated(false)
                ->helperText(__('tallcms::ui.t_auto_generated_from_name_used_in_author_archive_url'));
        }

        if (DbSchema::hasColumn($table, 'bio')) {
            $fields[] = Textarea::make('bio')
                ->label(__('tallcms::fields.biography'))
                ->rows(3)
                ->maxLength(1000)
                ->helperText(__('tallcms::ui.t_short_bio_displayed_on_posts_and_author_page'));
        }

        if (DbSchema::hasColumn($table, 'twitter_handle')) {
            $fields[] = TextInput::make('twitter_handle')
                ->label(__('tallcms::fields.x_twitter_handle'))
                ->prefix('@')
                ->maxLength(50);
        }

        if (DbSchema::hasColumn($table, 'job_title')) {
            $fields[] = TextInput::make('job_title')
                ->label(__('tallcms::fields.job_title'))
                ->maxLength(255)
                ->placeholder(__('tallcms::ui.t_e_g_senior_editor'));
        }

        if (DbSchema::hasColumn($table, 'company')) {
            $fields[] = TextInput::make('company')
                ->label(__('tallcms::fields.company_organization'))
                ->maxLength(255);
        }

        if (DbSchema::hasColumn($table, 'linkedin_url')) {
            $fields[] = TextInput::make('linkedin_url')
                ->label(__('tallcms::fields.linkedin_url'))
                ->url()
                ->maxLength(500)
                ->placeholder('https://linkedin.com/in/...');
        }

        return $fields;
    }

    public static function configure(Schema $schema): Schema
    {
        $model = config('tallcms.plugin_mode.user_model', 'App\\Models\\User');

        return $schema
            ->components([
                Section::make(__('tallcms::ui.t_user_information'))
                    ->description(__('tallcms::ui.t_basic_user_profile_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('tallcms::fields.full_name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('tallcms::ui.t_enter_full_name')),

                        TextInput::make('email')
                            ->label(__('tallcms::fields.email_address'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique($model, 'email', ignoreRecord: true)
                            ->placeholder(__('tallcms::ui.t_user_example_com')),

                        TextInput::make('password')
                            ->label(__('tallcms::fields.password'))
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rule(Password::default())
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->placeholder(__('tallcms::ui.t_enter_secure_password'))
                            ->helperText(__('tallcms::ui.t_leave_blank_to_keep_current_password_when_editing')),
                    ])
                    ->columns(2),

                Section::make(__('tallcms::ui.t_author_profile'))
                    ->description(__('tallcms::ui.t_public_author_information_displayed_on_posts_and_author_archives'))
                    ->schema(static::getAuthorProfileFields($model))
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn () => ! empty(static::getAuthorProfileFields($model))),

                Section::make(__('tallcms::ui.t_role_assignment'))
                    ->description(__('tallcms::ui.t_assign_roles_to_this_user'))
                    ->schema([
                        Select::make('roles')
                            ->label(__('tallcms::fields.user_roles'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(__('tallcms::ui.t_select_one_or_more_roles_for_this_user'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
