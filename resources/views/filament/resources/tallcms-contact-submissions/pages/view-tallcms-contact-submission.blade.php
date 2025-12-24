<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Submission Details Card --}}
        <x-filament::section>
            <x-slot name="heading">
                Form Submission
            </x-slot>

            <div class="space-y-4">
                @foreach($record->form_data ?? [] as $field)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <dt class="mb-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ $field['label'] ?? ucfirst(str_replace('_', ' ', $field['name'] ?? 'Field')) }}
                            @if($field['type'] ?? false)
                                <span class="ml-2 inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-normal normal-case text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $field['type'] }}
                                </span>
                            @endif
                        </dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            @if($field['type'] === 'textarea')
                                <div class="whitespace-pre-wrap">{{ e($field['value'] ?? '') }}</div>
                            @elseif($field['type'] === 'email' && !empty($field['value']))
                                <a href="mailto:{{ e($field['value']) }}" class="text-primary-600 hover:underline dark:text-primary-400">
                                    {{ e($field['value']) }}
                                </a>
                            @elseif($field['type'] === 'tel' && !empty($field['value']))
                                <a href="tel:{{ e($field['value']) }}" class="text-primary-600 hover:underline dark:text-primary-400">
                                    {{ e($field['value']) }}
                                </a>
                            @else
                                {{ e($field['value'] ?? '-') }}
                            @endif
                        </dd>
                    </div>
                @endforeach

                @if(empty($record->form_data))
                    <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                        No form data available
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Metadata Card --}}
        <x-filament::section>
            <x-slot name="heading">
                Submission Details
            </x-slot>

            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Submitted From</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if($record->page_url)
                            <a href="{{ $record->page_url }}" target="_blank" class="text-primary-600 hover:underline dark:text-primary-400">
                                {{ $record->page_url }}
                            </a>
                        @else
                            -
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Submitted At</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        {{ $record->created_at->format('M j, Y \a\t g:i A') }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-1">
                        @if($record->is_read)
                            <x-filament::badge color="gray">Read</x-filament::badge>
                        @else
                            <x-filament::badge color="warning">Unread</x-filament::badge>
                        @endif
                    </dd>
                </div>
            </dl>
        </x-filament::section>
    </div>
</x-filament-panels::page>
