{{-- Mobile Menu Style (optimized for touch with larger tap targets) --}}
<ul class="space-y-1">
    @foreach($items as $item)
        @php
            $hasChildren = !empty($item['children']);
        @endphp

        @if($item['url'] && !in_array($item['type'], ['header', 'separator']))
            <li class="{{ $item['css_class'] ?? '' }}">
                <a href="{{ $item['url'] }}"
                   class="flex items-center px-4 py-3 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors duration-200"
                   @if($item['target'] === '_blank') target="_blank" rel="noopener" @endif>
                    @if($item['icon'])
                        <i class="{{ $item['icon'] }} mr-3 w-5 h-5"></i>
                    @endif
                    <span>{{ $item['label'] }}</span>
                </a>

                @if($hasChildren)
                    <ul class="ml-6 mt-1 space-y-1 border-l-2 border-gray-100 pl-4">
                        @foreach($item['children'] as $child)
                            @if($child['url'] && !in_array($child['type'], ['header', 'separator']))
                                <li>
                                    <a href="{{ $child['url'] }}"
                                       class="flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md transition-colors duration-200"
                                       @if($child['target'] === '_blank') target="_blank" rel="noopener" @endif>
                                        @if($child['icon'])
                                            <i class="{{ $child['icon'] }} mr-2 w-4 h-4"></i>
                                        @endif
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </li>
        @elseif($item['type'] === 'header')
            <li class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $item['label'] }}
            </li>
        @elseif($item['type'] === 'separator')
            <li>
                <hr class="my-2 border-gray-200" />
            </li>
        @endif
    @endforeach
</ul>
