<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TallCMS Installer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-indicator {
            @apply w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold;
        }
        .step-indicator.active {
            @apply bg-blue-600 text-white;
        }
        .step-indicator.completed {
            @apply bg-green-600 text-white;
        }
        .step-indicator.pending {
            @apply bg-gray-300 text-gray-600;
        }
        .step-line {
            @apply flex-1 h-0.5 bg-gray-300 mx-4;
        }
        .step-line.completed {
            @apply bg-green-600;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="flex items-center">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-lg">T</span>
                        </div>
                        <div class="ml-3">
                            <h1 class="text-xl font-semibold text-gray-900">TallCMS</h1>
                            <p class="text-sm text-gray-500">Web Installer</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Progress Steps -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="flex items-center">
                    @php
                        $steps = [
                            'welcome' => 'Welcome',
                            'environment' => 'Environment',
                            'configuration' => 'Configuration',
                            'complete' => 'Complete'
                        ];
                        
                        $currentStep = $currentStep ?? 'welcome';
                        $stepKeys = array_keys($steps);
                        $currentIndex = array_search($currentStep, $stepKeys);
                    @endphp
                    
                    @foreach($steps as $step => $label)
                        @php
                            $stepIndex = array_search($step, $stepKeys);
                            $isCompleted = $stepIndex < $currentIndex;
                            $isActive = $step === $currentStep;
                            $isPending = $stepIndex > $currentIndex;
                        @endphp
                        
                        <div class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                            <div class="step-indicator {{ $isCompleted ? 'completed' : ($isActive ? 'active' : 'pending') }}">
                                @if($isCompleted)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    {{ $stepIndex + 1 }}
                                @endif
                            </div>
                            @if(!$loop->last)
                                <div class="step-line {{ $isCompleted ? 'completed' : '' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-3 flex justify-between text-xs font-medium text-gray-500">
                    @foreach($steps as $step => $label)
                        <span class="{{ $step === $currentStep ? 'text-blue-600' : '' }}">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 py-8">
            <div class="max-w-4xl mx-auto px-4">
                @if(session('error'))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <p class="mt-1 text-sm text-red-700">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">Success</h3>
                                <p class="mt-1 text-sm text-green-700">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <div>
                        <p>Built by Vibe Coding, co-developed with Claude.ai, and code reviewed by Codex.</p>
                    </div>
                    <div>
                        <p>TallCMS Installer v1.0</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @yield('scripts')
</body>
</html>