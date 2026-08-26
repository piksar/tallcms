@php
    $fields = $config['fields'] ?? [];
    $submitButtonText = $config['submit_button_text'] ?? __('tallcms::frontend.send_message');
    $successMessage = $config['success_message'] ?? __('tallcms::frontend.thank_you_well_be_in_touch');
    $buttonStyle = $config['button_style'] ?? 'btn-primary';
    $formId = 'contact-form-' . uniqid();
    $sectionPadding = ($config['first_section'] ?? false) ? 'pb-16' : ($config['padding'] ?? 'py-16');

    // Animation config
    $animationType = $animation_type ?? '';
    $animationDuration = $animation_duration ?? 'anim-duration-700';

    // Generate signature for security
    $pageUrl = request()->url();
    $signature = \TallCms\Cms\Http\Controllers\ContactFormController::signConfig($config, $pageUrl);

    $jsConfig = [
        'formId' => $formId,
        'submitUrl' => route('tallcms.contact.submit'),
        'successMessage' => $successMessage,
        'config' => $config,
        'signature' => $signature,
        'pageUrl' => $pageUrl,
        'fieldNames' => array_column($fields, 'name'),
    ];
@endphp

<x-tallcms::animation-wrapper
    tag="section"
    :animation="$animationType"
    :controller="true"
    :id="$anchor_id ?? null"
    class="contact-form-block {{ $sectionPadding }} {{ $config['background'] ?? 'bg-base-100' }} {{ $css_classes ?? '' }}"
>
    <div class="{{ $contentWidthClass ?? 'max-w-2xl mx-auto' }} {{ $contentPadding ?? 'px-4 sm:px-6 lg:px-8' }}">
        <x-tallcms::animation-wrapper
            :animation="$animationType"
            :duration="$animationDuration"
            :use-parent="true"
        >
            @if($config['title'] ?? false)
                <h2 class="text-3xl font-bold mb-4 text-base-content">
                    {{ $config['title'] }}
                </h2>
            @endif

            @if($config['description'] ?? false)
                <p class="text-lg mb-8 text-base-content/70">
                    {{ $config['description'] }}
                </p>
            @endif

            <div
                id="{{ $formId }}"
                x-data="contactForm"
                data-contact-form-config='@json($jsConfig)'
                x-cloak
            >
                {{-- Error Alert --}}
                <div x-show="formError" x-cloak class="alert alert-error mb-6" role="alert">
                    <x-heroicon-o-exclamation-circle class="w-6 h-6" />
                    <span x-text="formError"></span>
                </div>

                {{-- Success Message --}}
                <div x-show="submitted" x-cloak class="alert alert-success">
                    <x-heroicon-o-check-circle class="w-12 h-12" />
                    <span class="text-lg font-medium" x-text="successMessage"></span>
                </div>

                {{-- Form --}}
                <form x-show="!submitted" x-on:submit.prevent="submit" class="space-y-6">
                    @foreach($fields as $field)
                        <x-tallcms::form.dynamic-field :field="$field" :form-id="$formId" />
                    @endforeach

                    {{-- Honeypot --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="{{ $formId }}-website">Website</label>
                        <input type="text" id="{{ $formId }}-website" x-model="formData._honeypot" tabindex="-1" autocomplete="off">
                    </div>

                    {{-- Submit Button --}}
                    <div>
                        <button
                            type="submit"
                            class="btn {{ $buttonStyle }} w-full sm:w-auto"
                            x-bind:disabled="submitting"
                        >
                            <span x-show="!submitting">{{ $submitButtonText }}</span>
                            <span x-show="submitting" x-cloak class="inline-flex items-center">
                                <span class="loading loading-spinner loading-sm mr-2"></span>
                                Sending...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </x-tallcms::animation-wrapper>
    </div>
</x-tallcms::animation-wrapper>
