<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $record->name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Location: {{ ucfirst($record->location) }}</p>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $record->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $record->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
        
        @if($record->description)
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">{{ $record->description }}</p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">Menu Structure</h4>
        
        @php
            $menuItems = App\Models\TallcmsMenuItem::where('menu_id', $record->id)
                ->defaultOrder()
                ->get();
        @endphp
        
        @if($menuItems->count() > 0)
            <div class="space-y-2">
                @foreach($menuItems as $item)
                    @php
                        $depth = 0;
                        $ancestors = App\Models\TallcmsMenuItem::where('menu_id', $record->id)
                            ->where('_lft', '<', $item->_lft)
                            ->where('_rgt', '>', $item->_rgt)
                            ->count();
                    @endphp
                    
                    <div class="flex items-center space-x-2 py-2 px-3 rounded {{ $item->is_active ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}" 
                         style="margin-left: {{ $ancestors * 1.5 }}rem;">
                        
                        @if($ancestors > 0)
                            <span class="text-gray-400">├─</span>
                        @endif
                        
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $item->label }}</span>
                                
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $item->type === 'page' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $item->type === 'external' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $item->type === 'custom' ? 'bg-orange-100 text-orange-800' : '' }}
                                    {{ $item->type === 'header' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $item->type === 'separator' ? 'bg-gray-100 text-gray-600' : '' }}
                                ">
                                    {{ ucfirst($item->type) }}
                                </span>
                                
                                @if(!$item->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                            
                            @if($item->type === 'page' && $item->page)
                                <p class="text-xs text-gray-600 dark:text-gray-400">Links to: {{ $item->page->title }}</p>
                            @elseif($item->url)
                                <p class="text-xs text-gray-600 dark:text-gray-400">URL: {{ $item->url }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <div class="mb-2">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </div>
                <p>No menu items found</p>
                <p class="text-sm">Add menu items to see the structure here</p>
            </div>
        @endif
    </div>
</div>