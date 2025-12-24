<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class ContactFormBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'contact_form';
    }

    public static function getLabel(): string
    {
        return 'Contact Form';
    }

    public static function getDefaultFields(): array
    {
        return [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'options' => []],
            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'options' => []],
            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'options' => []],
        ];
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Configure your contact form fields and settings')
            ->schema([
                Section::make('Form Header')
                    ->description('Optional title and description shown above the form')
                    ->schema([
                        TextInput::make('title')
                            ->maxLength(255)
                            ->placeholder('e.g., Get in Touch'),

                        Textarea::make('description')
                            ->maxLength(500)
                            ->placeholder('e.g., Fill out the form below and we\'ll get back to you shortly.'),
                    ])
                    ->collapsible(),

                Section::make('Form Fields')
                    ->description('Configure which fields appear in your form')
                    ->schema([
                        Repeater::make('fields')
                            ->label('')
                            ->schema([
                                Select::make('type')
                                    ->label('Field Type')
                                    ->options([
                                        'text' => 'Text',
                                        'email' => 'Email',
                                        'tel' => 'Phone',
                                        'textarea' => 'Text Area',
                                        'select' => 'Dropdown',
                                    ])
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('name')
                                    ->label('Field Name')
                                    ->required()
                                    ->alphaNum()
                                    ->maxLength(50)
                                    ->distinct()
                                    ->helperText('Unique identifier (no spaces)')
                                    ->columnSpan(1),

                                TextInput::make('label')
                                    ->label('Display Label')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Toggle::make('required')
                                    ->label('Required')
                                    ->default(false)
                                    ->columnSpan(1),

                                TagsInput::make('options')
                                    ->label('Dropdown Options')
                                    ->visible(fn (Get $get): bool => $get('type') === 'select')
                                    ->helperText('Press Enter after each option (max 50 options, 100 chars each)')
                                    ->maxItems(50)
                                    ->nestedRecursiveRules([
                                        'string',
                                        'max:100',
                                    ])
                                    ->dehydrateStateUsing(fn (?array $state): ?array => $state
                                        ? array_values(array_filter(array_map('trim', $state), fn ($v) => $v !== ''))
                                        : null
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->default(self::getDefaultFields())
                            ->minItems(2)
                            ->maxItems(20)
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['label'] ?? 'Field') . ' (' . ($state['type'] ?? 'text') . ')')
                            ->addActionLabel('Add Field')
                            ->rules([
                                fn (): \Closure => function (string $_attribute, $value, \Closure $fail) {
                                    if (! is_array($value)) {
                                        return;
                                    }

                                    $fields = array_values($value);
                                    $fieldNames = array_column($fields, 'name');

                                    // Check for required 'name' field
                                    if (! in_array('name', $fieldNames, true)) {
                                        $fail('A "name" field is required.');
                                    }

                                    // Check for required 'email' field
                                    if (! in_array('email', $fieldNames, true)) {
                                        $fail('An "email" field is required.');
                                    }

                                    // Verify email field has correct type
                                    foreach ($fields as $field) {
                                        if (($field['name'] ?? '') === 'email' && ($field['type'] ?? '') !== 'email') {
                                            $fail('The "email" field must have type "Email".');
                                        }

                                        // Verify name and email are required
                                        if (in_array($field['name'] ?? '', ['name', 'email'], true) && ! ($field['required'] ?? false)) {
                                            $fail("The \"{$field['name']}\" field must be marked as required.");
                                        }
                                    }
                                },
                            ]),
                    ]),

                Section::make('Form Settings')
                    ->description('Customize button text and success message')
                    ->schema([
                        TextInput::make('submit_button_text')
                            ->label('Submit Button Text')
                            ->default('Send Message')
                            ->maxLength(50),

                        Textarea::make('success_message')
                            ->label('Success Message')
                            ->default('Thank you for your message! We\'ll be in touch soon.')
                            ->maxLength(500)
                            ->helperText('Shown after successful form submission'),
                    ])
                    ->collapsible(),
            ])
            ->slideOver();
    }

    public static function toPreviewHtml(array $config): string
    {
        // Normalize fields array (Filament repeater uses UUID keys)
        $fields = isset($config['fields']) && is_array($config['fields'])
            ? array_values($config['fields'])
            : self::getDefaultFields();

        // Return static preview for admin editor (no Alpine.js)
        return view('cms.blocks.contact-form', [
            'config' => array_merge([
                'title' => '',
                'description' => '',
                'fields' => self::getDefaultFields(),
                'submit_button_text' => 'Send Message',
                'success_message' => 'Thank you for your message! We\'ll be in touch soon.',
            ], $config, ['fields' => $fields]),
            'isPreview' => true,
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        // Normalize fields array (Filament repeater uses UUID keys)
        $fields = isset($config['fields']) && is_array($config['fields'])
            ? array_values($config['fields'])
            : self::getDefaultFields();

        // Return full interactive form for frontend
        return view('cms.blocks.contact-form', [
            'config' => array_merge([
                'title' => '',
                'description' => '',
                'fields' => self::getDefaultFields(),
                'submit_button_text' => 'Send Message',
                'success_message' => 'Thank you for your message! We\'ll be in touch soon.',
            ], $config, ['fields' => $fields]),
            'isPreview' => false,
        ])->render();
    }
}