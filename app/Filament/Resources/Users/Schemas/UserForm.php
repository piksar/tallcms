<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('User Management')
                    ->tabs([
                        Tabs\Tab::make('Profile')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('User Information')
                                    ->description('Basic user profile information')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter full name'),

                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(User::class, 'email', ignoreRecord: true)
                                            ->placeholder('user@example.com'),

                                        TextInput::make('password')
                                            ->label('Password')
                                            ->password()
                                            ->required(fn (string $context): bool => $context === 'create')
                                            ->rule(Password::default())
                                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                            ->dehydrated(fn (?string $state): bool => filled($state))
                                            ->placeholder('Enter secure password')
                                            ->helperText('Leave blank to keep current password when editing'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Roles & Permissions')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Role Assignment')
                                    ->description('Assign roles and permissions to this user')
                                    ->schema([
                                        Select::make('roles')
                                            ->label('User Roles')
                                            ->relationship('roles', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->helperText('Select one or more roles for this user')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Account Status')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Account Settings')
                                    ->description('Control account access and verification status')
                                    ->schema([
                                        Toggle::make('is_active')
                                            ->label('Account Active')
                                            ->helperText('Inactive accounts cannot access the admin panel')
                                            ->default(true),

                                        DateTimePicker::make('email_verified_at')
                                            ->label('Email Verified At')
                                            ->helperText('When the user verified their email address')
                                            ->displayFormat('M j, Y g:i A'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
}
