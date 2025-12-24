@php
    // Get current theme presets for styling
    $textPresets = theme_text_presets();
    $buttonPresets = theme_button_presets();
    $themeColors = theme_colors();

    $textPreset = $textPresets['primary'] ?? [
        'heading' => '#111827',
        'description' => '#374151'
    ];

    // Get primary button preset for the submit button
    $primaryButton = $buttonPresets['primary'] ?? [
        'bg' => $themeColors['primary'][600] ?? '#2563eb',
        'text' => '#ffffff'
    ];

    // Build CSS custom properties for this block instance
    $customProperties = collect([
        '--block-heading-color: ' . $textPreset['heading'],
        '--block-text-color: ' . $textPreset['description'],
        '--block-button-bg: ' . $primaryButton['bg'],
        '--block-button-text: ' . $primaryButton['text'],
    ])->join('; ') . ';';

    // Normalize fields
    $fields = $config['fields'] ?? [];
    $submitButtonText = $config['submit_button_text'] ?? 'Send Message';
    $successMessage = $config['success_message'] ?? 'Thank you for your message! We\'ll be in touch soon.';

    // Generate a unique ID for this form instance
    $formId = 'contact-form-' . uniqid();

    // Check if this is a preview render (admin editor) or frontend
    $isPreview = $isPreview ?? false;
@endphp

<section class="py-12 sm:py-16 px-4 sm:px-6 lg:px-8" style="{{ $customProperties }}">
    <div class="max-w-2xl mx-auto">
        @if($config['title'] ?? false)
            <h2 class="text-3xl font-bold mb-4" style="color: var(--block-heading-color);">
                {{ $config['title'] }}
            </h2>
        @endif

        @if($config['description'] ?? false)
            <p class="text-lg mb-8" style="color: var(--block-text-color);">
                {{ $config['description'] }}
            </p>
        @endif

        @if($isPreview)
            {{-- Static Preview for Admin Editor --}}
            <div class="flex flex-col gap-4">
                @foreach($fields as $field)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $field['label'] }}
                            @if($field['required'] ?? false)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if($field['type'] === 'textarea')
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-400 text-sm min-h-[80px]">
                                Text area input...
                            </div>
                        @elseif($field['type'] === 'select')
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-400 text-sm flex justify-between items-center">
                                <span>Select {{ strtolower($field['label']) }}...</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-400 text-sm">
                                @if($field['type'] === 'email')
                                    email@example.com
                                @elseif($field['type'] === 'tel')
                                    (555) 123-4567
                                @else
                                    Enter {{ strtolower($field['label']) }}...
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="pt-2">
                    <span class="inline-block px-6 py-2.5 rounded-lg font-medium text-sm bg-primary-600 text-white">
                        {{ $submitButtonText }}
                    </span>
                </div>
            </div>
        @else
            {{-- Interactive Form for Frontend --}}
            @php
                // Get current page URL for signature salt (prevents replay across pages)
                $pageUrl = request()->url();

                // Generate signature with page URL to prevent config tampering and replay attacks
                $signature = \App\Http\Controllers\ContactFormController::signConfig($config, $pageUrl);

                $jsConfig = [
                    'formId' => $formId,
                    'submitUrl' => route('contact.submit'),
                    'successMessage' => $successMessage,
                    'config' => $config,
                    'signature' => $signature,
                    'pageUrl' => $pageUrl,
                    'fieldNames' => array_column($fields, 'name'),
                ];
            @endphp

            <div
                id="{{ $formId }}"
                x-data="contactForm"
                data-contact-form-config='@json($jsConfig)'
                x-cloak
            >
                <div x-show="formError" x-cloak class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert" x-text="formError"></div>

                <div x-show="submitted" x-cloak class="rounded-lg bg-green-50 p-6 text-center">
                    <svg class="mx-auto mb-4 h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-lg font-medium text-green-800" x-text="successMessage"></p>
                </div>

                <form x-show="!submitted" x-on:submit.prevent="submit" class="space-y-6">
                    @foreach($fields as $field)
                        <div>
                            <label for="{{ $formId }}-{{ $field['name'] }}" class="mb-2 block text-sm font-medium" style="color: var(--block-text-color, #374151);">
                                {{ $field['label'] }}
                                @if($field['required'] ?? false)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>

                            @if($field['type'] === 'textarea')
                                <textarea
                                    id="{{ $formId }}-{{ $field['name'] }}"
                                    x-model="formData.{{ $field['name'] }}"
                                    rows="5"
                                    class="w-full rounded-lg border px-4 py-3 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    x-bind:class="errors.{{ $field['name'] }} ? 'border-red-500' : 'border-gray-300'"
                                    style="background-color: white;"
                                    @if($field['required'] ?? false) required @endif
                                ></textarea>
                            @elseif($field['type'] === 'select')
                                <select
                                    id="{{ $formId }}-{{ $field['name'] }}"
                                    x-model="formData.{{ $field['name'] }}"
                                    class="w-full rounded-lg border px-4 py-3 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    x-bind:class="errors.{{ $field['name'] }} ? 'border-red-500' : 'border-gray-300'"
                                    style="background-color: white;"
                                    @if($field['required'] ?? false) required @endif
                                >
                                    <option value="">Select...</option>
                                    @foreach($field['options'] ?? [] as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input
                                    type="{{ $field['type'] }}"
                                    id="{{ $formId }}-{{ $field['name'] }}"
                                    x-model="formData.{{ $field['name'] }}"
                                    class="w-full rounded-lg border px-4 py-3 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                    x-bind:class="errors.{{ $field['name'] }} ? 'border-red-500' : 'border-gray-300'"
                                    style="background-color: white;"
                                    @if($field['required'] ?? false) required @endif
                                >
                            @endif

                            <p x-show="errors.{{ $field['name'] }}" x-cloak class="mt-1 text-sm text-red-600" x-text="errors.{{ $field['name'] }} ? errors.{{ $field['name'] }}[0] : ''"></p>
                        </div>
                    @endforeach

                    <div class="hidden" aria-hidden="true">
                        <label for="{{ $formId }}-website">Website</label>
                        <input type="text" id="{{ $formId }}-website" x-model="formData._honeypot" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg px-6 py-3 text-base font-semibold text-white transition-all duration-200 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                            style="background-color: var(--block-button-bg, #2563eb); color: var(--block-button-text, white);"
                            x-bind:disabled="submitting"
                        >
                            <span x-show="!submitting">{{ $submitButtonText }}</span>
                            <span x-show="submitting" x-cloak class="inline-flex items-center">
                                <svg class="-ml-1 mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sending...
                            </span>
                        </button>
                    </div>
                </form>
            </div>

        @endif
    </div>
</section>
