<div>
    {{-- Form-level error (rate limiting) --}}
    @error('form')
        <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">
            {{ $message }}
        </div>
    @enderror

    @if($submitted)
        {{-- Success message --}}
        <div class="rounded-lg bg-green-50 p-6 text-center">
            <svg class="mx-auto mb-4 h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-lg font-medium text-green-800">
                {{ $config['success_message'] ?? 'Thank you for your message!' }}
            </p>
        </div>
    @endif

    @if(!$submitted)
        <form wire:submit="submit" class="space-y-6">
            @foreach($config['fields'] ?? [] as $field)
                <div>
                    <label for="{{ $field['name'] }}" class="mb-2 block text-sm font-medium" style="color: var(--block-text-color, #374151);">
                        {{ $field['label'] }}
                        @if($field['required'] ?? false)
                            <span class="text-red-500">*</span>
                        @endif
                    </label>

                    @if($field['type'] === 'textarea')
                        <textarea
                            id="{{ $field['name'] }}"
                            wire:model="formData.{{ $field['name'] }}"
                            rows="5"
                            class="w-full rounded-lg border px-4 py-3 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('formData.'.$field['name']) border-red-500 @else border-gray-300 @enderror"
                            style="background-color: white;"
                            @if($field['required'] ?? false) required @endif
                        ></textarea>
                    @elseif($field['type'] === 'select')
                        <select
                            id="{{ $field['name'] }}"
                            wire:model="formData.{{ $field['name'] }}"
                            class="w-full rounded-lg border px-4 py-3 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('formData.'.$field['name']) border-red-500 @else border-gray-300 @enderror"
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
                            id="{{ $field['name'] }}"
                            wire:model="formData.{{ $field['name'] }}"
                            class="w-full rounded-lg border px-4 py-3 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('formData.'.$field['name']) border-red-500 @else border-gray-300 @enderror"
                            style="background-color: white;"
                            @if($field['required'] ?? false) required @endif
                        >
                    @endif

                    @error('formData.'.$field['name'])
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            {{-- Honeypot field (hidden from users, visible to bots) --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" wire:model="honeypot" tabindex="-1" autocomplete="off">
            </div>

            {{-- Submit button --}}
            <div>
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-lg px-6 py-3 text-base font-semibold text-white transition-all duration-200 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    style="background-color: var(--block-button-bg, #2563eb); color: var(--block-button-text, white);"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        {{ $config['submit_button_text'] ?? 'Send Message' }}
                    </span>
                    <span wire:loading class="inline-flex items-center">
                        <svg class="-ml-1 mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sending...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
