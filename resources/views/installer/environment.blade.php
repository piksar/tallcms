@extends('installer.layout', ['currentStep' => 'environment'])

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Environment Check</h2>
            <p class="text-gray-600">We're checking your server to make sure it meets TallCMS requirements</p>
        </div>

        <div class="space-y-6">
            <!-- PHP Version Check -->
            <div class="border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $checks['php']['name'] }}</h3>
                    @if($checks['php']['passed'])
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">✓ Passed</span>
                    @else
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">✗ Failed</span>
                    @endif
                </div>
                <div class="text-sm text-gray-600">
                    <p><strong>Required:</strong> {{ $checks['php']['required'] }}</p>
                    <p><strong>Current:</strong> {{ $checks['php']['current'] }}</p>
                    <p class="mt-2 {{ $checks['php']['passed'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $checks['php']['message'] }}
                    </p>
                </div>
            </div>

            <!-- PHP Extensions Check -->
            <div class="border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $checks['extensions']['name'] }}</h3>
                    @if($checks['extensions']['passed'])
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">✓ Passed</span>
                    @else
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">✗ Failed</span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($checks['extensions']['extensions'] as $extension)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div>
                                <span class="font-medium text-gray-900">{{ $extension['name'] }}</span>
                                <span class="text-xs text-gray-500 block">{{ $extension['description'] }}</span>
                            </div>
                            @if($extension['installed'])
                                <span class="text-green-600 text-sm">✓</span>
                            @else
                                <span class="text-red-600 text-sm">✗</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-sm {{ $checks['extensions']['passed'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $checks['extensions']['message'] }}
                </p>
            </div>

            <!-- Directory Permissions Check -->
            <div class="border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $checks['directories']['name'] }}</h3>
                    @if($checks['directories']['passed'])
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">✓ Passed</span>
                    @else
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">✗ Failed</span>
                    @endif
                </div>
                <div class="space-y-3">
                    @foreach($checks['directories']['directories'] as $directory)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div>
                                <code class="text-sm font-mono text-gray-800">{{ $directory['path'] }}</code>
                                <span class="text-xs text-gray-500 block">{{ $directory['description'] }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($directory['exists'])
                                    <span class="text-green-600 text-xs">Exists</span>
                                @else
                                    <span class="text-red-600 text-xs">Missing</span>
                                @endif
                                @if($directory['writable'])
                                    <span class="text-green-600 text-sm">✓ Writable</span>
                                @else
                                    <span class="text-red-600 text-sm">✗ Not Writable</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-sm {{ $checks['directories']['passed'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $checks['directories']['message'] }}
                </p>
            </div>

            <!-- Overall Status -->
            <div class="border-2 {{ $checks['overall'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} rounded-lg p-6">
                <div class="flex items-center">
                    @if($checks['overall'])
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-green-800">Environment Ready!</h3>
                            <p class="text-green-700">Your server meets all requirements for TallCMS installation.</p>
                        </div>
                    @else
                        <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-red-800">Environment Issues Found</h3>
                            <p class="text-red-700">Please resolve the above issues before proceeding with installation.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-between">
            <a href="{{ route('installer.welcome') }}" 
               class="bg-gray-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-700 transition-colors inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back
            </a>
            
            <div class="flex space-x-3">
                <button onclick="window.location.reload()" 
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Recheck
                </button>
                
                @if($checks['overall'])
                    <a href="{{ route('installer.configuration') }}" 
                       class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors inline-flex items-center">
                        Continue
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                @else
                    <button disabled 
                            class="bg-gray-400 text-white px-6 py-3 rounded-lg font-medium cursor-not-allowed inline-flex items-center">
                        Continue
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection