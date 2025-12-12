@extends('installer.layout', ['currentStep' => 'complete'])

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Installation Complete!</h2>
            <p class="text-gray-600">TallCMS has been successfully installed and configured</p>
        </div>

        <!-- Installation Output -->
        @if(!empty($output))
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Installation Log</h3>
            <div class="bg-black text-green-400 p-4 rounded font-mono text-sm overflow-y-auto max-h-64">
                @foreach($output as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Next Steps -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Admin Panel -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-blue-900 ml-3">Access Admin Panel</h3>
                </div>
                <p class="text-blue-800 mb-4">Start managing your content through the admin interface</p>
                <a href="/admin" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors inline-flex items-center">
                    Open Admin Panel
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>

            <!-- Frontend -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9V3m0 18a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-green-900 ml-3">View Your Website</h3>
                </div>
                <p class="text-green-800 mb-4">See your TallCMS site in action</p>
                <a href="/" 
                   class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors inline-flex items-center">
                    Visit Website
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Important Information -->
        <div class="space-y-6">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Security Notice</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p class="mb-2">For security, the installer has been automatically disabled. If you need to run it again:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Delete the <code class="bg-yellow-100 px-1 rounded">storage/installer.lock</code> file</li>
                                <li>Set <code class="bg-yellow-100 px-1 rounded">INSTALLER_ENABLED=true</code> in your <code class="bg-yellow-100 px-1 rounded">.env</code> file</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">What's Next?</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                    <div class="space-y-2">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Create your first page in the admin panel</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Set up navigation menus</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Configure site settings and branding</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Add content categories</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Write your first blog post</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Customize your theme and layout</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Need Help?</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <h4 class="font-medium text-gray-900 mb-1">Documentation</h4>
                        <p class="text-gray-600">Check the CLAUDE.md file for development guidelines</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-medium text-gray-900 mb-1">Support</h4>
                        <p class="text-gray-600">Built by Vibe Coding with Claude.ai assistance</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h4 class="font-medium text-gray-900 mb-1">Performance</h4>
                        <p class="text-gray-600">TALL stack optimized for speed and developer experience</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CLI Alternative -->
        <div class="mt-8 bg-gray-900 text-white rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                CLI Alternative
            </h3>
            <p class="text-gray-300 mb-3">If you prefer command line installation in the future, you can use:</p>
            <div class="bg-black rounded p-3 font-mono text-sm text-green-400">
                <div>php artisan migrate --force</div>
                <div>php artisan key:generate</div>
                <div>php artisan storage:link</div>
                <div>php artisan make:user</div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-gray-500 text-sm">
                🎉 Congratulations! Your TallCMS installation is complete and ready to use.
            </p>
        </div>
    </div>
</div>
@endsection