@extends('installer.layout', ['currentStep' => 'welcome'])

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome to TallCMS</h2>
            <p class="text-gray-600">Let's get your content management system up and running</p>
        </div>

        <div class="space-y-6">
            @if(isset($envError))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Configuration Error</h3>
                        <p class="mt-1 text-sm text-red-700">{{ $envError }}</p>
                        <p class="mt-2 text-sm text-red-700">
                            Please ensure the web server has write permissions to the project root directory, 
                            or manually copy <code class="bg-red-100 px-1 rounded">.env.example</code> to 
                            <code class="bg-red-100 px-1 rounded">.env</code>
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Before you begin, make sure you have:</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Your database credentials (host, database name, username, password)
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        An email address and password for your admin account
                    </li>
                </ul>
            </div>

            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">What this installer will do:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="space-y-2">
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Check system requirements
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Create .env configuration
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Set up database
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Create admin user
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Configure mail settings
                        </div>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Optimize application
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Important</h3>
                        <p class="mt-1 text-sm text-yellow-700">
                            This installer will create and modify files on your server. 
                            Make sure you have backups of any existing data before proceeding.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-center">
            @if(isset($envError))
                <button disabled class="bg-gray-400 text-white px-6 py-3 rounded-lg font-medium cursor-not-allowed">
                    Fix Permissions First
                </button>
            @else
                <a href="{{ route('installer.environment') }}" 
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors inline-flex items-center">
                    Get Started
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @endif
        </div>
    </div>
</div>
@endsection