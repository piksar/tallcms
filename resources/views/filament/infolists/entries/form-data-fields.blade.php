<div class="space-y-4">
    @foreach($getState() ?? [] as $field)
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

    @if(empty($getState()))
        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
            No form data available
        </div>
    @endif
</div>
